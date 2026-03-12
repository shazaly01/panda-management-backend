<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Item;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\ApiTestCase;

class ItemTest extends ApiTestCase
{
    // نستخدم ApiTestCase بدلاً من TestCase لأننا جهزنا فيه المستخدمين والصلاحيات

    /** * الحالة 1: المسار السعيد (إنشاء صنف ببيانات صحيحة كاملة)
     */
    public function test_admin_can_create_item_with_full_details()
    {
        // 1. تجهيز البيانات
        $category = Category::factory()->create();
        $unit1 = Unit::factory()->create(); // حبة
        $unit2 = Unit::factory()->create(); // كرتونة

        $data = [
            'category_id' => $category->id,
            'name'        => 'iPhone 15 Pro',
            'code'        => 'ITM-001',
            'barcode'     => '123456789',
            'type'        => 'store', // Enum value
            'unit1_id'    => $unit1->id,
            'price1'      => 1000,

            // إضافة وحدة ثانية
            'unit2_id'    => $unit2->id,
            'factor2'     => 12, // الكرتونة فيها 12 حبة
            'price2'      => 11500, // سعر جملة للكرتونة

            'base_cost'   => 800,
            'is_active'   => true,
        ];

        // 2. تنفيذ الطلب
        $response = $this->postJson('/api/items', $data);

        // 3. التحقق (Assertions)
        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'iPhone 15 Pro')
                 ->assertJsonPath('data.units.medium.factor', 12);

        // التحقق من قاعدة البيانات
        $this->assertDatabaseHas('items', [
            'code' => 'ITM-001',
            'factor2' => 12
        ]);
    }

    /** * الحالة 2: التحقق من منع تكرار الكود والباركود
     */
    public function test_cannot_create_item_with_duplicate_code_or_barcode()
    {
        // إنشاء صنف أول
        Item::factory()->create(['code' => 'CODE-123', 'barcode' => 'BAR-123']);

        // محاولة إنشاء صنف ثاني بنفس الكود
        $response = $this->postJson('/api/items', [
            'category_id' => Category::factory()->create()->id,
            'unit1_id'    => Unit::factory()->create()->id,
            'name'        => 'New Item',
            'code'        => 'CODE-123', // مكرر
            'barcode'     => 'BAR-999',
            'type'        => 'store',
            'price1'      => 100,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['code']);
    }

    /** * الحالة 3: المنطق الرياضي للوحدات (هام جداً)
     * يجب أن يكون معامل الوحدة الثانية أكبر من 1
     */
    public function test_factor_logic_validation()
    {
        $unit1 = Unit::factory()->create();
        $unit2 = Unit::factory()->create();

        $data = [
            'category_id' => Category::factory()->create()->id,
            'name'        => 'Test Logic',
            'code'        => 'LOGIC-01',
            'type'        => 'store',
            'unit1_id'    => $unit1->id,
            'price1'      => 10,

            'unit2_id'    => $unit2->id,
            'factor2'     => 0.5, // خطأ! لا يمكن أن تكون الوحدة الكبرى أصغر من الصغرى
        ];

        $response = $this->postJson('/api/items', $data);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['factor2']);
    }

    /** * الحالة 4: الصلاحيات
     * المراجع (Auditor) لا يحق له إنشاء أصناف
     */
    public function test_auditor_cannot_create_items()
    {
        // تغيير المستخدم الحالي إلى "Auditor"
        \Laravel\Sanctum\Sanctum::actingAs($this->auditorUser);

        $data = Item::factory()->make()->toArray();

        $response = $this->postJson('/api/items', $data);

        $response->assertStatus(403); // Forbidden
    }
}
