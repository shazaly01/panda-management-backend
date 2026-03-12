<?php

namespace Tests\Feature\Api;

use App\Enums\PartnerType; // <-- تأكد من استيراد الـ Enum
use App\Models\Item;
use App\Models\Partner;
use App\Models\Treasury;
use App\Models\Warehouse;
use Tests\ApiTestCase;

class PurchaseTransactionTest extends ApiTestCase
{
    public function test_cash_purchase_increases_stock_and_decreases_treasury()
    {
        $warehouse = Warehouse::factory()->create();
        $treasury = Treasury::factory()->create(['current_balance' => 1000]);

        // --- التعديل هنا ---
        // استخدام type بدلاً من is_supplier
        $supplier = Partner::factory()->create(['type' => PartnerType::SUPPLIER->value]);

        $item = Item::factory()->create(['base_cost' => 50]);

        $data = [
            'trx_date' => now()->toDateString(),
            'partner_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'treasury_id' => $treasury->id,
            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $item->unit1_id,
                    'qty' => 10,
                    'price' => 50,
                    'unit_factor' => 1,
                ]
            ],
            'paid_amount' => 500,
        ];

        $response = $this->postJson('/api/purchases', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('warehouse_items', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 10,
        ]);

        $this->assertDatabaseHas('treasuries', [
            'id' => $treasury->id,
            'current_balance' => 500,
        ]);
    }

    public function test_credit_purchase_increases_supplier_balance()
    {
        $warehouse = Warehouse::factory()->create();

        // --- التعديل هنا أيضاً ---
        $supplier = Partner::factory()->create(['type' => PartnerType::SUPPLIER->value]);

        $item = Item::factory()->create(['base_cost' => 80]);

        $data = [
            'trx_date' => now()->toDateString(),
            'partner_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'treasury_id' => null,
            'items' => [
                [
                    'item_id' => $item->id,
                    'unit_id' => $item->unit1_id,
                    'qty' => 5,
                    'price' => 80,
                    'unit_factor' => 1,
                ]
            ],
            'paid_amount' => 0,
        ];

        $response = $this->postJson('/api/purchases', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('warehouse_items', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_qty' => 5,
        ]);

        $this->assertDatabaseHas('partners', [
            'id' => $supplier->id,
            'current_balance' => -400,
        ]);
    }
}
