<?php

namespace Tests\Feature\Api;

use App\Models\Item;
use App\Models\Partner;
use App\Models\Treasury;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class SalesTransactionTest extends ApiTestCase
{
    /**
     * الحالة 1: بيع نقدي (Cash Sale)
     * التوقعات: خصم مخزون + زيادة خزينة + رصيد العميل لا يتأثر
     */
    public function test_cash_sale_deducts_inventory_and_increases_treasury()
    {
        // 1. تجهيز البيانات
        $warehouse = Warehouse::factory()->create();
        $treasury = Treasury::factory()->create();
        $partner = Partner::factory()->create();
        $item = Item::factory()->create(['price1' => 100]);

        // إضافة رصيد للمخزن (50 قطعة)
        DB::table('warehouse_items')->insert([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 50,
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
                    'qty' => 5,
                    'price' => 100,
                    'unit_factor' => 1,
                ]
            ],
            'paid_amount' => 500,
        ];

        // 2. التنفيذ
        $response = $this->postJson('/api/sales', $data);

        // 3. التحقق
        $response->assertStatus(201);

        // المخزون نقص (50 - 5 = 45)
        $this->assertDatabaseHas('warehouse_items', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 45,
        ]);

        // الخزينة زادت
        $this->assertDatabaseHas('treasuries', [
            'id' => $treasury->id,
            'current_balance' => 500,
        ]);
    }

    /**
     * الحالة 2: بيع آجل (Credit Sale)
     */
    public function test_credit_sale_increases_partner_balance()
    {
        $warehouse = Warehouse::factory()->create();
        $partner = Partner::factory()->create();
        $item = Item::factory()->create(['price1' => 200]);

        DB::table('warehouse_items')->insert([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 10,
        ]);

        $data = [
            'trx_date' => now()->toDateString(),
            'partner_id' => $partner->id,
            'warehouse_id' => $warehouse->id,
            'treasury_id' => null,
            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $item->unit1_id,
                    'qty' => 2,
                    'price' => 200,
                    'unit_factor' => 1,
                ]
            ],
            'paid_amount' => 0,
        ];

        $response = $this->postJson('/api/sales', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'current_balance' => 400,
        ]);
    }

    /**
     * الحالة 3 (المعدلة): البيع عند عدم كفاية الرصيد
     * التوقعات: العملية تنجح، المخزون يصفر، ويتم تسجيل عجز
     */
    public function test_sales_creates_shortage_record_when_stock_is_insufficient()
    {
        $warehouse = Warehouse::factory()->create();
        $partner = Partner::factory()->create();
        $item = Item::factory()->create();

        // سنضع في المخزون قطعتين فقط (2)
        DB::table('warehouse_items')->insert([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 2,
        ]);

        $data = [
            'trx_date' => now()->toDateString(),
            'partner_id' => $partner->id,
            'warehouse_id' => $warehouse->id,
            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $item->unit1_id,
                    'qty' => 5, // سنبيع 5 قطع (المطلوب أكثر من الموجود بـ 3)
                    'price' => 100,
                    'unit_factor' => 1,
                ]
            ]
        ];

        $response = $this->postJson('/api/sales', $data);

        // 1. يجب أن تنجح العملية
        $response->assertStatus(201);

        // 2. المخزون يجب أن يصبح 0 (تم سحب القطعتين الموجودتين)
        $this->assertDatabaseHas('warehouse_items', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 0,
        ]);

        // 3. يجب تسجيل عجز بمقدار الفرق (5 - 2 = 3)
        $this->assertDatabaseHas('shortages', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'shortage_qty' => 3,
            'status' => 'pending' // أو pending حسب الـ Enum
        ]);
    }
}
