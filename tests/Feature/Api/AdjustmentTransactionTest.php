<?php

namespace Tests\Feature\Api;

use App\Enums\InventoryTransactionType;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class AdjustmentTransactionTest extends ApiTestCase
{
    /**
     * اختبار تسوية بالنقص (إخراج تالف مثلاً)
     */
    public function test_adjustment_out_decreases_stock()
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create();

        // رصيد مبدئي 10
        DB::table('warehouse_items')->insert([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 10,
        ]);

        $data = [
            'trx_date' => now()->toDateString(),
            'warehouse_id' => $warehouse->id,
            'trx_type' => InventoryTransactionType::ADJUSTMENT_OUT->value, // نوع الحركة: إخراج
            'notes' => 'Damaged items',
            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $item->unit1_id,
                    'qty' => 2, // خصم 2
                    'unit_factor' => 1,
                ]
            ]
        ];

        $response = $this->postJson('/api/adjustments', $data);

        $response->assertStatus(201);

        // الرصيد يجب أن ينقص (10 - 2 = 8)
        $this->assertDatabaseHas('warehouse_items', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 8,
        ]);
    }

    /**
     * اختبار تسوية بالزيادة (زيادة جردية)
     */
    public function test_adjustment_in_increases_stock()
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create();

        // رصيد مبدئي 5
        DB::table('warehouse_items')->insert([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 5,
        ]);

        $data = [
            'trx_date' => now()->toDateString(),
            'warehouse_id' => $warehouse->id,
            'trx_type' => InventoryTransactionType::ADJUSTMENT_IN->value, // نوع الحركة: إدخال
            'notes' => 'Found extra items',
            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $item->unit1_id,
                    'qty' => 3, // إضافة 3
                    'unit_factor' => 1,
                ]
            ]
        ];

        $response = $this->postJson('/api/adjustments', $data);

        $response->assertStatus(201);

        // الرصيد يجب أن يزيد (5 + 3 = 8)
        $this->assertDatabaseHas('warehouse_items', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 8,
        ]);
    }
}
