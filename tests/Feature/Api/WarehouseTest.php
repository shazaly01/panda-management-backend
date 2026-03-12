<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Warehouse;
use Tests\ApiTestCase;

class WarehouseTest extends ApiTestCase
{
    /**
     * الحالة 1: إنشاء مخزن جديد
     */
    public function test_admin_can_create_warehouse()
    {
        $data = [
            'name' => 'North Branch',
            'code' => 'WH-NORTH',
            'location' => 'Khartoum North',
            'keeper_name' => 'Ahmed Ali',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/warehouses', $data);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'North Branch');

        $this->assertDatabaseHas('warehouses', ['code' => 'WH-NORTH']);
    }

    /**
     * الحالة 2: منع تكرار كود المخزن
     */
    public function test_cannot_duplicate_warehouse_code()
    {
        Warehouse::factory()->create(['code' => 'WH-001']);

        $response = $this->postJson('/api/warehouses', [
            'name' => 'Another Warehouse',
            'code' => 'WH-001', // مكرر
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['code']);
    }

    /**
     * الحالة 3: اختبار إسناد المخازن للمستخدم (هام جداً)
     * هذا يختبر دالة assignToUser في الكونترولر
     */
    public function test_admin_can_assign_warehouses_to_user()
    {
        // 1. إنشاء مستخدم ومخزنين
        $user = User::factory()->create();
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();

        // 2. إرسال طلب الإسناد
        $response = $this->postJson('/api/warehouses/assign-user', [
            'user_id' => $user->id,
            'warehouses' => [$warehouse1->id, $warehouse2->id],
            'default_warehouse_id' => $warehouse1->id,
        ]);

        $response->assertStatus(200);

        // 3. التحقق من الجدول الوسيط (Pivot Table)
        $this->assertDatabaseHas('user_warehouses', [
            'user_id' => $user->id,
            'warehouse_id' => $warehouse1->id,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('user_warehouses', [
            'user_id' => $user->id,
            'warehouse_id' => $warehouse2->id,
            'is_default' => false,
        ]);
    }

    /**
     * الحالة 4: حماية عملية الإسناد
     * لا يحق للمراجع (Auditor) إسناد المخازن
     */
    public function test_auditor_cannot_assign_warehouses()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->auditorUser);

        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $response = $this->postJson('/api/warehouses/assign-user', [
            'user_id' => $user->id,
            'warehouses' => [$warehouse->id],
        ]);

        $response->assertStatus(403); // Forbidden
    }
}
