<?php

namespace Tests\Feature\Api;

use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class TransferTransactionTest extends ApiTestCase
{
    /**
     * اختبار نجاح التحويل عند توفر الرصيد
     */
    public function test_transfer_moves_stock_between_warehouses()
    {
        // 1. تجهيز البيانات
        $sourceWarehouse = Warehouse::factory()->create(['name' => 'Source']);
        $targetWarehouse = Warehouse::factory()->create(['name' => 'Target']);
        $item = Item::factory()->create();

        // وضع رصيد في المخزن المصدر (10 قطع)
        DB::table('warehouse_items')->insert([
            'warehouse_id' => $sourceWarehouse->id,
            'item_id' => $item->id,
            'current_qty' => 10,
        ]);

        $data = [
            'trx_date' => now()->toDateString(),
            'from_warehouse_id' => $sourceWarehouse->id,
            'to_warehouse_id' => $targetWarehouse->id,
            'notes' => 'Transfer for testing',
            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $item->unit1_id,
                    'qty' => 4, // تحويل 4 قطع
                    'unit_factor' => 1,
                ]
            ]
        ];

        // 2. التنفيذ
        $response = $this->postJson('/api/transfers', $data);

        // 3. التحقق
        $response->assertStatus(201);

        // أ) المصدر: نقص (10 - 4 = 6)
        $this->assertDatabaseHas('warehouse_items', [
            'warehouse_id' => $sourceWarehouse->id,
            'item_id' => $item->id,
            'current_qty' => 6,
        ]);

        // ب) المستقبل: زاد (0 + 4 = 4)
        $this->assertDatabaseHas('warehouse_items', [
            'warehouse_id' => $targetWarehouse->id,
            'item_id' => $item->id,
            'current_qty' => 4,
        ]);
    }

    /**
     * اختبار فشل التحويل عند عدم توفر الرصيد
     * (التحويلات عادة صارمة ولا تسمح بالسالب أو العجز)
     */
    public function test_cannot_transfer_if_insufficient_stock()
    {
        $sourceWarehouse = Warehouse::factory()->create();
        $targetWarehouse = Warehouse::factory()->create();
        $item = Item::factory()->create();

        // وضع رصيد قليل (2 فقط)
        DB::table('warehouse_items')->insert([
            'warehouse_id' => $sourceWarehouse->id,
            'item_id' => $item->id,
            'current_qty' => 2,
        ]);

        $data = [
            'trx_date' => now()->toDateString(),
            'from_warehouse_id' => $sourceWarehouse->id,
            'to_warehouse_id' => $targetWarehouse->id,
            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $item->unit1_id,
                    'qty' => 5, // محاولة تحويل 5
                    'unit_factor' => 1,
                ]
            ]
        ];

        $response = $this->postJson('/api/transfers', $data);

        // نتوقع خطأ 500 (بسبب Exception الرصيد غير كافي) أو 422
        // حسب تصميمنا الحالي في InventoryService، هو يرمي Exception لذا نتوقع 500
        $response->assertStatus(500);
    }
}
