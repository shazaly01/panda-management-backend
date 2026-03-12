<?php

namespace Tests\Feature\Api;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryHeader;
use App\Models\Item;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class InventoryTransactionTest extends ApiTestCase
{
    /**
     * الحالة 1: إضافة رصيد افتتاحي (Adjustment In)
     */
 public function test_adjustment_in_increases_stock()
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create();
        $unit = $item->unit1;

        $data = [
            'trx_date' => now()->toDateString(),
            'trx_type' => InventoryTransactionType::ADJUSTMENT_IN->value,
            'notes' => 'Opening Balance',

            // --- (الإضافة الهامة جداً) ---
            // يجب تحديد المخزن الذي ستتم فيه التسوية
            'from_warehouse_id' => $warehouse->id,

            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $unit->id,
                    'qty' => 50,
                    'unit_factor' => 1,
                ]
            ]
        ];

        $response = $this->postJson('/api/inventory-transactions', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('warehouse_items', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 50,
        ]);
    }

    /**
     * الحالة 2: التحويل بين المخازن (Transfer)
     */
    public function test_transfer_moves_stock_between_warehouses()
    {
        $sourceWarehouse = Warehouse::factory()->create();
        $destWarehouse = Warehouse::factory()->create();
        $item = Item::factory()->create();

        // (تعديل 2): إزالة created_at و updated_at لأن الجدول لا يحتويها
        DB::table('warehouse_items')->insert([
            'warehouse_id' => $sourceWarehouse->id,
            'item_id' => $item->id,
            'current_qty' => 100,
            // 'created_at' => now(), // تم الحذف
            // 'updated_at' => now()  // تم الحذف
        ]);

        $data = [
            'trx_date' => now()->toDateString(),
            'trx_type' => InventoryTransactionType::TRANSFER->value,
            'from_warehouse_id' => $sourceWarehouse->id,
            'to_warehouse_id' => $destWarehouse->id,
            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $item->unit1_id,
                    'qty' => 30,
                    'unit_factor' => 1, // <--- (تعديل 3) تمت الإضافة هنا أيضاً
                ]
            ]
        ];

        $response = $this->postJson('/api/inventory-transactions', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('warehouse_items', [
            'warehouse_id' => $sourceWarehouse->id,
            'item_id' => $item->id,
            'current_qty' => 70,
        ]);

        $this->assertDatabaseHas('warehouse_items', [
            'warehouse_id' => $destWarehouse->id,
            'item_id' => $item->id,
            'current_qty' => 30,
        ]);
    }

    /**
     * الحالة 3: منع التحويل إذا الرصيد لا يكفي
     */
    public function test_cannot_transfer_if_insufficient_stock()
    {
        $sourceWarehouse = Warehouse::factory()->create();
        $destWarehouse = Warehouse::factory()->create();
        $item = Item::factory()->create();

        $data = [
            'trx_date' => now()->toDateString(),
            'trx_type' => InventoryTransactionType::TRANSFER->value,
            'from_warehouse_id' => $sourceWarehouse->id,
            'to_warehouse_id' => $destWarehouse->id,
            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $item->unit1_id,
                    'qty' => 10,
                    'unit_factor' => 1, // <--- (تعديل 4) للأمان نضيفه هنا أيضاً
                ]
            ]
        ];

        $response = $this->postJson('/api/inventory-transactions', $data);

        $response->assertStatus(500);
    }
}
