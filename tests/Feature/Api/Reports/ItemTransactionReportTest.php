<?php

namespace Tests\Feature\Api\Reports;

use App\Enums\InventoryTransactionType;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class ItemTransactionReportTest extends ApiTestCase
{
    public function test_stock_card_shows_correct_movements_and_running_balance()
    {
        // 1. تجهيز المسرح
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create();
        $user = \App\Models\User::factory()->create(); // أنشأنا مستخدماً

        // أ) التاريخ 2026-02-01: رصيد افتتاحي / شراء (وارد 10)
       $purchaseHeader = \App\Models\PurchasesHeader::create([
            'trx_date' => '2026-02-01',
            'invoice_date' => '2026-02-01',
            'warehouse_id' => $warehouse->id,
            'partner_id' => \App\Models\Partner::factory()->create()->id,
            'trx_no' => 'PUR-100',
            'status' => 'confirmed',
            'created_by' => $user->id
        ]);
        $purchaseHeader->details()->create([
            'item_id' => $item->id,
            'unit_id' => $item->unit1_id,
            'qty' => 10,
            'price' => 50,      // السعر
            'unit_cost' => 50,  // التكلفة (الحقل الذي تسبب في الخطأ)
            'total_row' => 500
        ]);

        // ب) المبيعات
        // تأكد أن تفاصيل المبيعات لا تطلب حقولاً إضافية، إذا طلبت 'cost' أضفه هنا أيضاً
        $salesHeader = \App\Models\SalesHeader::create([
            'invoice_date' => '2026-02-03',
            'warehouse_id' => $warehouse->id,
            'partner_id' => \App\Models\Partner::factory()->create()->id,
            'trx_no' => 'SAL-500',
            'type' => 'sale',
            'status' => 'confirmed',
            'created_by' => $user->id
        ]);
        $salesHeader->details()->create([
            'item_id' => $item->id,
            'unit_id' => $item->unit1_id,
            'qty' => 3,
            'price' => 100,
            'cost' => 50, // أضفته احتياطاً إذا كان جدول المبيعات يطلبه
            'total_row' => 300
        ]);

        // ج) التاريخ 2026-02-05: تحويل صادر (صادر 2)
        $transferHeader = \App\Models\InventoryHeader::create([
            'trx_date' => '2026-02-05',
            'from_warehouse_id' => $warehouse->id,
            'to_warehouse_id' => Warehouse::factory()->create()->id,
            'trx_type' => InventoryTransactionType::TRANSFER,
            'trx_no' => 'TRF-900',
            'status' => 'confirmed',
            'created_by' => $user->id // <--- تمت الإضافة
        ]);
        $transferHeader->details()->create([
            'item_id' => $item->id,
            'unit_id' => $item->unit1_id,
            'qty' => 2,
            'unit_factor' => 1
        ]);

        // 3. طلب التقرير
        $response = $this->getJson("/api/reports/stock-card?warehouse_id={$warehouse->id}&item_id={$item->id}");

        // 4. التحقق
        $response->assertStatus(200);

        // ... بقية التحقق كما هو
        $response->assertJsonStructure([
            'data' => [
                '*' => ['date', 'doc_no', 'type', 'in_qty', 'out_qty', 'balance']
            ]
        ]);

        $data = $response->json('data');

        $this->assertCount(3, $data);
        $this->assertEquals(10, $data[0]['in_qty']);
        $this->assertEquals(3, $data[1]['out_qty']);
        $this->assertEquals(2, $data[2]['out_qty']);
        $this->assertEquals(5, $data[2]['balance']);
    }}
