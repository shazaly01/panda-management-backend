<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ItemMovement;

class ReportController extends Controller
{
    /**
     * تقرير الأصناف والمخزون الحالي بأقل وحدة
     */
    public function inventoryReport(Request $request): JsonResponse
    {
        $warehouseId = $request->input('warehouse_id');

        // بناء الاستعلام مع تحميل العلاقات المطلوبة فقط
        $query = Item::with([
            'unit1',
            'unit2',
            'unit3',
            // فلترة الكميات المحملة لتكون للمخزن المحدد فقط (إن وُجد)
            'warehouses' => function ($q) use ($warehouseId) {
                if ($warehouseId) {
                    $q->where('warehouses.id', $warehouseId);
                }
            }
        ]);

        // إذا تم تمرير warehouse_id، نجلب فقط الأصناف الموجودة في هذا المخزن
        if ($warehouseId) {
            $query->whereHas('warehouses', function ($q) use ($warehouseId) {
                $q->where('warehouses.id', $warehouseId);
            });
        }

        $items = $query->get();

        // تشكيل البيانات وتحديد أقل وحدة
        $reportData = $items->map(function ($item) {
            // 1. جمع الكميات من الـ Pivot
            $totalQty = (float) $item->warehouses->sum('pivot.current_qty');

            // 2. تحديد أقل وحدة متاحة ومعامل التحويل (Factor)
            if ($item->unit3_id) {
                $lowestUnitName = $item->unit3->name ?? 'غير محدد';
                $factor = (float) ($item->factor3 ?? 1);
            } elseif ($item->unit2_id) {
                $lowestUnitName = $item->unit2->name ?? 'غير محدد';
                $factor = (float) ($item->factor2 ?? 1);
            } else {
                $lowestUnitName = $item->unit1->name ?? 'غير محدد';
                $factor = 1;
            }

            // 3. حساب الكمية بأقل وحدة
            $qtyInLowestUnit = $totalQty * $factor;

            return [
                'item_id'          => $item->id,
                'item_code'        => $item->code,
                'item_name'        => $item->name,
                'barcode'          => $item->barcode,
                'lowest_unit_name' => $lowestUnitName,
                // --- التعديل هنا: توحيد الدقة العشرية لـ 4 أرقام كما اتفقنا في الهيكلة الجديدة ---
                'quantity'         => number_format($qtyInLowestUnit, 4, '.', ''),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $reportData
        ]);
    }

    public function itemMovementReport(Request $request): JsonResponse
    {
        // 1. التحقق من المدخلات
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $itemId = $request->input('item_id');
        $warehouseId = $request->input('warehouse_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // 2. جلب بيانات الصنف الأساسية
        $item = Item::findOrFail($itemId);

        // 3. الاستعلام من جدول حركة الأصناف (Stock Ledger) الموحد
        $query = ItemMovement::with(['warehouse', 'reference'])
            ->where('item_id', $itemId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // 4. ترتيب الحركات زمنياً بشكل دقيق
        $movementsData = $query->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get();

        $movements = [];
        $companyRunningBalance = 0; // يُستخدم فقط إذا طلبنا تقرير الشركة ككل (بدون تحديد مخزن)

        // 5. تشكيل المخرجات
        foreach ($movementsData as $movement) {
            $trxQty = (float) $movement->trx_qty;
            $type = $movement->trx_type->value;
            $label = $movement->trx_type->label();

            $inQty = 0;
            $outQty = 0;
            $balance = 0;
            $description = $movement->notes ?? $label; // استخدام الملاحظات المحفوظة أو اسم الحركة

            // أ. في حالة طلب التقرير لمخزن محدد
            if ($warehouseId) {
                if ($trxQty > 0) {
                    $inQty = $trxQty;
                    $description = 'وارد - ' . $description;
                } elseif ($trxQty < 0) {
                    $outQty = abs($trxQty);
                    $description = 'منصرف - ' . $description;
                }

                // الرصيد التراكمي جاهز ومحسوب مسبقاً في الدفتر!
                $balance = $movement->current_qty;
            }
            // ب. في حالة طلب التقرير للشركة ككل (جميع المخازن)
            else {
                // التحويلات الداخلية لا تؤثر على إجمالي رصيد الشركة، لذا نصفرها في التقرير العام
                if ($type === \App\Enums\InventoryTransactionType::TRANSFER->value) {
                    $inQty = 0;
                    $outQty = 0;
                    $description = 'تحويل داخلي (' . ($movement->warehouse->name ?? '') . ') - ' . $description;
                } else {
                    if ($trxQty > 0) {
                        $inQty = $trxQty;
                    } elseif ($trxQty < 0) {
                        $outQty = abs($trxQty);
                    }
                    $companyRunningBalance += $trxQty; // حساب تراكمي على مستوى الشركة
                }

                $description .= ' | مخزن: ' . ($movement->warehouse->name ?? '---');
                $balance = $companyRunningBalance;
            }

            // إضافة السطر للمصفوفة
            $movements[] = [
                'id'             => $movement->id,
                'trx_no'         => (string) $movement->transaction_no, // DECIMAL(18, 0)
                'trx_date'       => $movement->created_at->format('Y-m-d H:i:s'),
                'type_label'     => $label,
                'description'    => $description,
                'in_qty'         => number_format($inQty, 4, '.', ''),
                'out_qty'        => number_format($outQty, 4, '.', ''),
                'balance'        => number_format($balance, 4, '.', ''),
            ];
        }

        return response()->json([
            'success' => true,
            'item' => [
                'id'      => $item->id,
                'code'    => $item->code,
                'name'    => $item->name,
                'barcode' => $item->barcode,
            ],
            'data' => $movements
        ]);
    }



    /**
     * تقرير أرصدة العملاء والموردين الحالية
     */
    public function partnerBalancesReport(Request $request): JsonResponse
    {
        $query = \App\Models\Partner::query();

        // 1. الفلترة حسب النوع (عميل / مورد)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // 2. الفلترة حسب حالة الرصيد (مدين / دائن / صفري)
        if ($request->filled('balance_status')) {
            if ($request->balance_status === 'debit') {
                $query->where('current_balance', '>', 0);
            } elseif ($request->balance_status === 'credit') {
                $query->where('current_balance', '<', 0);
            } elseif ($request->balance_status === 'zero') {
                $query->where('current_balance', 0);
            }
        }

        // 3. البحث بالاسم أو الكود
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('code', 'like', '%' . $searchTerm . '%');
            });
        }

        $partners = $query->orderBy('current_balance', 'desc')->get();

        $reportData = $partners->map(function ($partner) {
            return [
                'id'              => $partner->id,
                'code'            => (string) $partner->code, // DECIMAL(18, 0)
                'name'            => $partner->name,
                'type'            => $partner->type,
                'type_label'      => $partner->type->label(),
                // توحيد التنسيق لـ 4 أرقام عشرية كما في التقارير السابقة
                'current_balance' => number_format($partner->current_balance, 4, '.', ''),
                'phone'           => $partner->phone,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $reportData,
            'totals'  => [
                'total_debit'  => number_format($partners->where('current_balance', '>', 0)->sum('current_balance'), 4, '.', ''),
                'total_credit' => number_format(abs($partners->where('current_balance', '<', 0)->sum('current_balance')), 4, '.', ''),
            ]
        ]);
    }


    /**
     * تقرير كشف حساب تفصيلي (لجميع الأنواع)
     */
    public function accountStatementReport(Request $request): JsonResponse
    {
        $request->validate([
            'account_type' => 'required|in:partner,treasury', // نوع الحساب
            'account_id'   => 'required',
            'date_from'    => 'nullable|date',
            'date_to'      => 'nullable|date|after_or_equal:date_from',
        ]);

        $type = $request->account_type;
        $id = $request->account_id;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        $movements = [];
        $openingBalance = 0;
        $runningBalance = 0;
        $accountName = '';

        if ($type === 'partner') {
            $partner = \App\Models\Partner::findOrFail($id);
            $accountName = $partner->name;

            // 1. حساب الرصيد الافتتاحي قبل الفترة المحددة
            if ($dateFrom) {
                $openingBalance = \App\Models\PartnerLedger::where('partner_id', $id)
                    ->where('created_at', '<', $dateFrom)
                    ->selectRaw('SUM(credit) - SUM(debit) as balance')
                    ->value('balance') ?? 0;
            }

            // 2. جلب الحركات خلال الفترة
            $query = \App\Models\PartnerLedger::where('partner_id', $id);
            if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
            if ($dateTo) $query->whereDate('created_at', '<=', $dateTo);

            $data = $query->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get();

            $runningBalance = $openingBalance;
            foreach ($data as $row) {
                $debit = (float)$row->debit;
                $credit = (float)$row->credit;
                $runningBalance += ($credit - $debit);

                $movements[] = [
                    'date'        => $row->created_at->format('Y-m-d H:i'),
                    'trx_no'      => (string)$row->transaction_no,
                    'description' => $row->transaction_type->label() . ($row->notes ? ' - ' . $row->notes : ''),
                    'debit'       => number_format($debit, 4, '.', ''),
                    'credit'      => number_format($credit, 4, '.', ''),
                    'balance'     => number_format($runningBalance, 4, '.', ''),
                ];
            }
        } else {
            // كشف حساب خزينة أو بنك
            $treasury = \App\Models\Treasury::findOrFail($id);
            $accountName = $treasury->name . ($treasury->is_bank ? " (رقم حساب: {$treasury->bank_account_no})" : "");

            // 1. الرصيد الافتتاحي للنقدية
            if ($dateFrom) {
                $openingBalance = \App\Models\TreasuryTransaction::where('treasury_id', $id)
                    ->where('trx_date', '<', $dateFrom)
                    ->sum('amount'); // هنا अमाउंट يكون موجب للقبض وسالب للصرف
            }

            // 2. الحركات
            $query = \App\Models\TreasuryTransaction::where('treasury_id', $id);
            if ($dateFrom) $query->whereDate('trx_date', '>=', $dateFrom);
            if ($dateTo) $query->whereDate('trx_date', '<=', $dateTo);

            $data = $query->orderBy('trx_date', 'asc')->orderBy('id', 'asc')->get();

            $runningBalance = $openingBalance;
            foreach ($data as $row) {
                $amount = (float)$row->amount;
                $runningBalance += $amount;

                $movements[] = [
                    'date'        => $row->trx_date->format('Y-m-d'),
                    'trx_no'      => (string)$row->trx_no,
                    'description' => $row->type->label() . ($row->notes ? ' - ' . $row->notes : ''),
                    'debit'       => $amount < 0 ? number_format(abs($amount), 4, '.', '') : '0.0000', // صرف
                    'credit'      => $amount > 0 ? number_format($amount, 4, '.', '') : '0.0000',      // قبض
                    'balance'     => number_format($runningBalance, 4, '.', ''),
                ];
            }
        }

        return response()->json([
            'success'         => true,
            'account_name'    => $accountName,
            'opening_balance' => number_format($openingBalance, 4, '.', ''),
            'data'            => $movements,
            'final_balance'   => number_format($runningBalance, 4, '.', ''),
        ]);
    }



   public function designersReport(Request $request): JsonResponse
{
    $request->validate([
        'designer_id' => 'nullable|exists:users,id', // الفلترة بالـ ID الآن
        'date_from'   => 'nullable|date',
        'date_to'     => 'nullable|date',
    ]);

    $designerId = $request->designer_id;

    // جلب الفواتير مع علاقة المصمم لضمان وجود الاسم
    $query = \App\Models\SalesHeader::with('designer')->whereNotNull('designer_id');

    if ($designerId) {
        $query->where('designer_id', $designerId);
    }

    if ($request->date_from) $query->whereDate('invoice_date', '>=', $request->date_from);
    if ($request->date_to) $query->whereDate('invoice_date', '<=', $request->date_to);

    $sales = $query->get();

    // حالة الكشف التفصيلي لمصمم واحد
    if ($designerId) {
        $data = $sales->map(function ($inv) {
            return [
                'date'           => $inv->invoice_date,
                'trx_no'         => (string)$inv->trx_no,
                'partner_name'   => $inv->partner->name ?? 'عميل نقدي',
                'net_amount'     => number_format($inv->net_amount, 4, '.', ''),
                'commission_val' => number_format(($inv->net_amount * 10) / 100, 4, '.', ''),
            ];
        });

        return response()->json([
            'success' => true,
            'is_detailed' => true,
            'data'    => $data,
            'totals'  => [
                'total_sales' => number_format($sales->sum('net_amount'), 4, '.', ''),
                'total_commissions' => number_format(($sales->sum('net_amount') * 10) / 100, 4, '.', ''),
            ]
        ]);
    }

    // الحالة العامة: تجميع حسب designer_id
    $reportData = $sales->groupBy('designer_id')->map(function ($items, $id) {
        $designer = $items->first()->designer;
        $totalSales = $items->sum('net_amount');

        return [
            'designer_id'   => $id,
            'designer_name' => $designer->full_name ?? 'غير معرف',
            'invoices_count'=> $items->count(),
            'total_sales'   => number_format($totalSales, 4, '.', ''),
            'commission_rate' => 0,
            'commission_val'=> number_format(($totalSales * 10) / 100, 4, '.', ''),
        ];
    })->values();

    return response()->json([
        'success' => true,
        'is_detailed' => false,
        'data'    => $reportData,
        'totals'  => [
            'total_sales' => number_format($sales->sum('net_amount'), 4, '.', ''),
            'total_commissions' => number_format(($sales->sum('net_amount') * 10) / 100, 4, '.', ''),
        ]
    ]);
}
    }
