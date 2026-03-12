<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\SalesHeaderResource;
use App\Models\SalesHeader;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SalesController extends Controller
{
    protected SalesService $salesService;

    public function __construct(SalesService $salesService)
    {
        $this->salesService = $salesService;

        // 1. التعديل هنا: تغيير 'sales_header' إلى 'sale' ليتطابق مع مسار لارافيل الافتراضي
        $this->authorizeResource(SalesHeader::class, 'sale');
    }

   public function index(Request $request)
    {
        $query = SalesHeader::with(['partner', 'warehouse', 'designer']);


        $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = $request->search;
            $q->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('trx_no', 'like', "%$searchTerm%")
                         ->orWhere('walk_in_customer_name', 'like', "%$searchTerm%")
                         ->orWhereHas('partner', function ($partnerQuery) use ($searchTerm) {
                             $partnerQuery->where('name', 'like', "%$searchTerm%");
                         });
            });
        });
        // 1. فلاتر النصوص والأرقام المرجعية (بحث مباشر)
        $query->when($request->filled('trx_no'), function ($q) use ($request) {
            $q->where('trx_no', $request->trx_no); // رقم الفاتورة (غالباً تطابق تام)
        });

        $query->when($request->filled('walk_in_customer_name'), function ($q) use ($request) {
            $q->where('walk_in_customer_name', 'like', '%' . $request->walk_in_customer_name . '%');
        });

        $query->when($request->filled('notes'), function ($q) use ($request) {
            $q->where('notes', 'like', '%' . $request->notes . '%'); // البحث داخل الملاحظات
        });

        // 2. فلاتر العلاقات (Foreign Keys)
        $query->when($request->filled('partner_id'), function ($q) use ($request) {
            $q->where('partner_id', $request->partner_id);
        });

        $query->when($request->filled('warehouse_id'), function ($q) use ($request) {
            $q->where('warehouse_id', $request->warehouse_id);
        });

        $query->when($request->filled('designer_id'), function ($q) use ($request) {
            $q->where('designer_id', $request->designer_id);
        });

        $query->when($request->filled('created_by'), function ($q) use ($request) {
            $q->where('created_by', $request->created_by); // منشئ الفاتورة
        });

        // 3. فلاتر الحالة (Status)
        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status); // مسودة، معتمدة، إلخ
        });

        // 4. فلاتر التواريخ (Date Range) - مفيدة جداً للتقارير
        $query->when($request->filled('date_from'), function ($q) use ($request) {
            $q->whereDate('trx_date', '>=', $request->date_from);
        });

        $query->when($request->filled('date_to'), function ($q) use ($request) {
            $q->whereDate('trx_date', '<=', $request->date_to);
        });

        // 5. فلاتر مالية (حسب قيمة الفاتورة)
        $query->when($request->filled('amount_min'), function ($q) use ($request) {
            $q->where('net_amount', '>=', $request->amount_min);
        });

        $query->when($request->filled('amount_max'), function ($q) use ($request) {
            $q->where('net_amount', '<=', $request->amount_max);
        });

        // 6. فلتر حالة الدفع (Payment Status) - سحري ومفيد جداً للمحاسبين!
        $query->when($request->filled('payment_status'), function ($q) use ($request) {
            if ($request->payment_status === 'paid') {
                // مدفوعة بالكامل (المتبقي صفر أو أقل)
                $q->where('remaining_amount', '<=', 0);
            } elseif ($request->payment_status === 'unpaid') {
                // غير مدفوعة (المتبقي يساوي الصافي)
                $q->whereColumn('remaining_amount', 'net_amount');
            } elseif ($request->payment_status === 'partial') {
                // مدفوعة جزئياً (المتبقي أكبر من صفر وأصغر من الصافي)
                $q->where('remaining_amount', '>', 0)->whereColumn('remaining_amount', '<', 'net_amount');
            }
        });

        // الترتيب والإرجاع
        return SalesHeaderResource::collection($query->latest()->paginate(15));
    }

    public function store(StoreTransactionRequest $request)
    {
        $data = $request->validated();

        $headerData = Arr::except($data, ['items']);
        $items = $data['items'];
        $headerData['invoice_date'] = $data['trx_date'];

        $headerData['created_by'] = auth()->id();

        $sale = $this->salesService->createSale($headerData, $items);

        return new SalesHeaderResource($sale->load(['details.item', 'designer']));
    }

    // 2. التعديل هنا: تغيير اسم المتغير إلى $sale ليعمل الـ Route Model Binding
    public function show(SalesHeader $sale)
    {
        return new SalesHeaderResource($sale->load(['details.item', 'partner', 'warehouse', 'designer']));
    }

    // --- الإضافات الجديدة ---

    // 3. إضافة دالة التعديل (Update)
    public function update(StoreTransactionRequest $request, SalesHeader $sale)
    {
        $data = $request->validated();

        $headerData = Arr::except($data, ['items']);
        $items = $data['items'];
        $headerData['invoice_date'] = $data['trx_date'];

        // ملاحظة: نفترض أن لديك دالة updateSale في SalesService
        $updatedSale = $this->salesService->updateSale($sale, $headerData, $items);

        return new SalesHeaderResource($updatedSale->load(['details.item', 'designer']));
    }

    // 4. إضافة دالة الحذف (Destroy)
    public function destroy(SalesHeader $sale)
    {
        // ملاحظة: نفترض أن لديك دالة deleteSale في SalesService
        $this->salesService->deleteSale($sale);

        return response()->json(['message' => 'تم حذف الفاتورة بنجاح']);
    }



    // إضافة دالة تغيير الحالة المستقلة
    public function changeStatus(Request $request, SalesHeader $sale)
    {
        // 1. التحقق من الصلاحية عبر الـ Policy
        $this->authorize('changeStatus', $sale);

        // 2. التحقق من صحة الحالة المرسلة
        $validated = $request->validate([
            'status' => ['required', new \Illuminate\Validation\Rules\Enum(\App\Enums\TransactionStatus::class)]
        ]);

        // 3. التمرير للـ Service لتنفيذ المنطق
        $updatedSale = $this->salesService->changeSaleStatus($sale, $validated['status']);

        return new SalesHeaderResource($updatedSale->load(['details.item', 'designer']));
    }
}
