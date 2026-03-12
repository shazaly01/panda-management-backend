<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesHeader;
use App\Models\SalesDetail;
use App\Models\PurchasesHeader;
use App\Models\Treasury;
use App\Models\Partner;
use App\Models\User;
use App\Enums\PartnerType;
use App\Enums\TransactionStatus;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * جلب جميع إحصائيات لوحة التحكم المتقدمة والمعدلة
     */
    public function index(): JsonResponse
    {
        try {
            $today = Carbon::today();
            $startOfMonth = Carbon::now()->startOfMonth();

            // 1. حساب البطاقات السريعة (KPIs)
            $stats = [
                'sales_today' => (float) SalesHeader::whereDate('invoice_date', $today)
                    ->where('status', TransactionStatus::CONFIRMED)
                    ->sum('net_amount'),

                'purchases_today' => (float) PurchasesHeader::whereDate('invoice_date', $today)
                    ->where('status', TransactionStatus::CONFIRMED)
                    ->sum('net_amount'),

                // 🌟 صافي الربح اليومي: (إجمالي السطر - (الكمية المباعة * التكلفة))
                'profit_today' => (float) SalesDetail::whereHas('header', function($q) use ($today) {
                        $q->whereDate('invoice_date', $today)->where('status', TransactionStatus::CONFIRMED);
                    })->sum(DB::raw('total_row - (qty * cost)')),

                'cash_balance' => (float) Treasury::where('is_active', true)
                    ->sum('current_balance'),

                'receivables' => (float) Partner::whereIn('type', [PartnerType::CUSTOMER, PartnerType::BOTH])
                    ->where('is_active', true)
                    ->sum('current_balance'),

                'payables' => (float) Partner::whereIn('type', [PartnerType::SUPPLIER, PartnerType::BOTH])
                    ->where('is_active', true)
                    ->sum('current_balance'),

                'low_stock_count' => DB::table('warehouse_items')
                    ->whereColumn('current_qty', '<=', 'alert_qty')
                    ->count(),

                // 🌟 جديد: قيمة رأس المال المخزني (إجمالي بضاعة الرفوف بسعر التكلفة)
                'inventory_value' => (float) DB::table('warehouse_items')
                    ->join('items', 'warehouse_items.item_id', '=', 'items.id')
                    ->sum(DB::raw('warehouse_items.current_qty * items.base_cost')),
            ];

            // 2. بيانات الرسم البياني (آخر 7 أيام)
            $charts = $this->getWeeklyComparisonData();

            // 3. أفضل 5 أصناف مبيعاً هذا الشهر
            $top_items = SalesDetail::whereHas('header', function($q) use ($startOfMonth) {
                    $q->where('status', TransactionStatus::CONFIRMED)
                      ->where('invoice_date', '>=', $startOfMonth);
                })
                ->select('item_id', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(total_row) as total_revenue'))
                ->with('item:id,name')
                ->groupBy('item_id')
                ->orderByDesc('total_revenue')
                ->take(5)
                ->get();

            // 4. أداء المصممين (تم التصحيح باستخدام full_name بدلاً من name)
            $top_designers = SalesHeader::where('status', TransactionStatus::CONFIRMED)
                ->where('invoice_date', '>=', $startOfMonth)
                ->whereNotNull('designer_id')
                ->select('designer_id',
                    DB::raw('SUM(net_amount) as total_sales'),
                    DB::raw('SUM(design_commission) as total_commissions'))
                ->with('designer:id,full_name') // ✅ تم التصحيح هنا
                ->groupBy('designer_id')
                ->orderByDesc('total_sales')
                ->get();

            // 5. توزيع السيولة (خزائن نقدية vs أرصدة بنوك)
            $liquidity_dist = Treasury::where('is_active', true)
                ->select(DB::raw('CASE WHEN is_bank = 1 THEN "البنوك" ELSE "الخزائن النقدية" END as type_name'),
                        DB::raw('SUM(current_balance) as balance'))
                ->groupBy('is_bank')
                ->get();

            // 6. آخر الحركات المضافة
            $recent_activity = [
                'sales' => SalesHeader::with('partner:id,name')
                    ->latest()
                    ->take(5)
                    ->get(['id', 'trx_no', 'partner_id', 'net_amount', 'invoice_date', 'status']),

                'purchases' => PurchasesHeader::with('partner:id,name')
                    ->latest()
                    ->take(5)
                    ->get(['id', 'trx_no', 'partner_id', 'net_amount', 'invoice_date', 'status']),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'cards' => $stats,
                    'charts' => $charts,
                    'top_items' => $top_items,
                    'top_designers' => $top_designers,
                    'liquidity' => $liquidity_dist,
                    'recent' => $recent_activity
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في معالجة بيانات الداشبورد المتقدمة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * منطق جلب بيانات الرسم البياني لمقارنة المبيعات والمشتريات
     */
    private function getWeeklyComparisonData(): array
    {
        $labels = [];
        $salesData = [];
        $purchasesData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d M');

            $salesData[] = (float) SalesHeader::whereDate('invoice_date', $date->toDateString())
                ->where('status', TransactionStatus::CONFIRMED)
                ->sum('net_amount');

            $purchasesData[] = (float) PurchasesHeader::whereDate('invoice_date', $date->toDateString())
                ->where('status', TransactionStatus::CONFIRMED)
                ->sum('net_amount');
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'المبيعات',
                    'data' => $salesData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'المشتريات',
                    'data' => $purchasesData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ]
            ]
        ];
    }
}
