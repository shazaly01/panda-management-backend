<?php

namespace Tests\Feature\Api;

use App\Models\Item;
use App\Models\Partner;
use App\Models\Treasury;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class SalesReturnTransactionTest extends ApiTestCase
{
    /**
     * الحالة 1: مرتجع مبيعات نقدي (Cash Sales Return)
     * التوقعات: البضاعة تزيد في المخزن + الخزينة تدفع (تنقص)
     */
    public function test_cash_sales_return_increases_stock_and_decreases_treasury()
    {
        // 1. تجهيز البيانات
        $warehouse = Warehouse::factory()->create();
        $treasury = Treasury::factory()->create(['current_balance' => 2000]); // رصيد كافٍ للإرجاع
        $partner = Partner::factory()->create(); // عميل
        $item = Item::factory()->create(['price1' => 100]);

        // نفترض أن المخزن كان فيه 10 قطع
        DB::table('warehouse_items')->insert([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 10,
        ]);

        $data = [
            'trx_date' => now()->toDateString(),
            'partner_id' => $partner->id,
            'warehouse_id' => $warehouse->id,
            'treasury_id' => $treasury->id,
            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $item->unit1_id,
                    'qty' => 2, // العميل يرجع قطعتين
                    'price' => 100,
                    'unit_factor' => 1,
                ]
            ],
            'paid_amount' => 200, // نرجع له كامل المبلغ نقداً
        ];

        // 2. التنفيذ (سنستخدم Endpoint جديد للمرتجعات)
        $response = $this->postJson('/api/sales-returns', $data);

        // 3. التحقق
        $response->assertStatus(201);

        // أ) المخزون يجب أن يزيد (10 + 2 = 12)
        $this->assertDatabaseHas('warehouse_items', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 12,
        ]);

        // ب) الخزينة يجب أن تنقص (2000 - 200 = 1800)
        $this->assertDatabaseHas('treasuries', [
            'id' => $treasury->id,
            'current_balance' => 1800,
        ]);
    }

    /**
     * الحالة 2: مرتجع مبيعات آجل (Credit Sales Return)
     * التوقعات: البضاعة تزيد + رصيد العميل ينقص (ننقص من دينه)
     */
    public function test_credit_sales_return_decreases_customer_balance()
    {
        $warehouse = Warehouse::factory()->create();
        // عميل عليه دين سابق 500
        $partner = Partner::factory()->create(['current_balance' => 500]);
        $item = Item::factory()->create(['price1' => 100]);

        $data = [
            'trx_date' => now()->toDateString(),
            'partner_id' => $partner->id,
            'warehouse_id' => $warehouse->id,
            'treasury_id' => null, // آجل
            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $item->unit1_id,
                    'qty' => 1, // إرجاع قطعة واحدة
                    'price' => 100,
                    'unit_factor' => 1,
                ]
            ],
            'paid_amount' => 0,
        ];

        $response = $this->postJson('/api/sales-returns', $data);

        $response->assertStatus(201);

        // رصيد العميل يجب أن ينقص (500 - 100 = 400)
        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'current_balance' => 400,
        ]);
    }
}
