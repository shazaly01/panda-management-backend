<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesHeader;
use App\Models\PurchasesHeader;
use App\Models\Treasury;
use App\Models\Partner;
use App\Enums\PartnerType;
use App\Enums\TransactionStatus;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * جلب جميع إحصائيات لوحة التحكم
     */
    public function index(): JsonResponse
    {
        try {
            $today = Carbon::today();

            // 1. حساب البطاقات السريعة (KPIs)
            $stats = [
                // مبيعات اليوم (الفواتير المعتمدة فقط)
                'sales_today' => (float) SalesHeader::whereDate('invoice_date', $today)
                    ->where('status', TransactionStatus::CONFIRMED)
                    ->sum('net_amount'),

                // مشتريات اليوم (الفواتير المعتمدة فقط)
                'purchases_today' => (float) PurchasesHeader::whereDate('invoice_date', $today)
                    ->where('status', TransactionStatus::CONFIRMED)
                    ->sum('net_amount'),

                // إجمالي السيولة في الخزائن والبنوك النشطة
                'cash_balance' => (float) Treasury::where('is_active', true)
                    ->sum('current_balance'),

                // إجمالي مديونيات العملاء (بما في ذلك من نوع "كلاهما")
                'receivables' => (float) Partner::whereIn('type', [PartnerType::CUSTOMER, PartnerType::BOTH])
                    ->where('is_active', true)
                    ->sum('current_balance'),

                // إجمالي مديونيات الموردين (بما في ذلك من نوع "كلاهما")
                'payables' => (float) Partner::whereIn('type', [PartnerType::SUPPLIER, PartnerType::BOTH])
                    ->where('is_active', true)
                    ->sum('current_balance'),

                // تنبيهات المخزون (الأصناف التي ساوت أو نزلت عن حد الطلب)
                'low_stock_count' => DB::table('warehouse_items')
                    ->whereColumn('current_qty', '<=', 'alert_qty')
                    ->count(),
            ];

            // 2. بيانات الرسم البياني (آخر 7 أيام)
            $charts = $this->getWeeklyComparisonData();

            // 3. آخر الحركات المضافة للنظام
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
                    'recent' => $recent_activity
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في معالجة بيانات الداشبورد',
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

            // المبيعات المعتمدة لهذا اليوم
            $salesData[] = (float) SalesHeader::whereDate('invoice_date', $date->toDateString())
                ->where('status', TransactionStatus::CONFIRMED)
                ->sum('net_amount');

            // المشتريات المعتمدة لهذا اليوم
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
