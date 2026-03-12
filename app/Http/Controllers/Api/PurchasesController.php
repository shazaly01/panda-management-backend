<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\PurchasesHeaderResource;
use App\Models\PurchasesHeader;
use App\Services\PurchasesService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PurchasesController extends Controller
{
    protected PurchasesService $purchasesService;

    public function __construct(PurchasesService $purchasesService)
    {
        $this->purchasesService = $purchasesService;

        // 1. التعديل هنا: مطابقة اسم المسار ليعمل الـ Policy بشكل سليم
        $this->authorizeResource(PurchasesHeader::class, 'purchase');
    }

    public function index(Request $request)
    {
        $query = PurchasesHeader::with(['partner', 'warehouse']);
        return PurchasesHeaderResource::collection($query->latest()->paginate(15));
    }

    public function store(StoreTransactionRequest $request)
    {
        $data = $request->validated();

        $headerData = Arr::except($data, ['items']);
        $headerData['invoice_date'] = $data['trx_date'];

        // تحويل السعر إلى تكلفة ليتوافق مع سيرفيس المشتريات
        $items = array_map(function ($item) {
            $item['unit_cost'] = $item['price'];
            return $item;
        }, $data['items']);

        $headerData['created_by'] = auth()->id();

        // 2. التعديل هنا: توليد رقم تسلسلي صحيح يتوافق مع حقل DECIMAL(18, 0)

        $headerData['supplier_invoice_no'] = $request->input('supplier_invoice_no', 'SUP-000');

        $purchase = $this->purchasesService->createPurchase($headerData, $items);

        return new PurchasesHeaderResource($purchase->load('details'));
    }

    // 3. التعديل هنا: اسم المتغير يجب أن يكون $purchase
    public function show(PurchasesHeader $purchase)
    {
        return new PurchasesHeaderResource($purchase->load(['details.item', 'partner', 'warehouse']));
    }

    // --- الإضافات الجديدة ---

    // 4. إضافة دالة التعديل (Update)
    public function update(StoreTransactionRequest $request, PurchasesHeader $purchase)
    {
        $data = $request->validated();

        $headerData = Arr::except($data, ['items']);
        $headerData['invoice_date'] = $data['trx_date'];

        $items = array_map(function ($item) {
            $item['unit_cost'] = $item['price'];
            return $item;
        }, $data['items']);

        // الحفاظ على رقم فاتورة المورد القديم إذا لم يتم إرسال جديد
        $headerData['supplier_invoice_no'] = $request->input('supplier_invoice_no', $purchase->supplier_invoice_no);

        $updatedPurchase = $this->purchasesService->updatePurchase($purchase, $headerData, $items);

        return new PurchasesHeaderResource($updatedPurchase->load(['details.item', 'partner', 'warehouse']));
    }

    // 5. إضافة دالة الحذف (Destroy)
    public function destroy(PurchasesHeader $purchase)
    {
        $this->purchasesService->deletePurchase($purchase);

        return response()->json(['message' => 'تم حذف فاتورة المشتريات بنجاح']);
    }
}
