<?php

namespace Tests;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\Treasury;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    // المستخدمين بأدوارهم
    protected User $superAdmin;
    protected User $adminUser;
    protected User $dataEntryUser; // هذا هو الكاشير ومدخل الفواتير
    protected User $auditorUser;

    // كيانات أساسية نحتاجها في معظم الاختبارات
    protected Warehouse $mainWarehouse;
    protected Treasury $mainTreasury;

    /**
     * إعداد بيئة الاختبار قبل كل دالة Test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. تشغيل Seeder الصلاحيات (أساسي جداً)
        $this->seed(PermissionSeeder::class);

        // 2. إنشاء المستخدمين وتعيين الأدوار
        $this->superAdmin = $this->createUserWithRole('Super Admin');
        $this->adminUser = $this->createUserWithRole('Admin');
        $this->dataEntryUser = $this->createUserWithRole('Data Entry');
        $this->auditorUser = $this->createUserWithRole('Auditor');

        // 3. تهيئة بيئة العمل الأساسية (مخزن وخزينة)
        // لأن معظم عمليات الـ ERP تتطلب وجود مخزن وخزينة مربوطين بالمستخدم
        $this->setupErpEnvironment();

        // 4. الدخول الافتراضي كـ Super Admin
        Sanctum::actingAs($this->superAdmin);
    }

    /**
     * دالة مساعدة لإنشاء مستخدم مع دور معين
     */
    protected function createUserWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $user->assignRole($roleName);
        return $user;
    }

    /**
     * تجهيز مخزن وخزينة وربطهم بالمستخدمين النشطين (Admin & Data Entry)
     * لتجنب أخطاء "User not assigned to warehouse" أثناء الاختبارات
     */
    protected function setupErpEnvironment(): void
    {
        // إنشاء مخزن رئيسي وخزينة رئيسية باستخدام المصانع Factories
        $this->mainWarehouse = Warehouse::factory()->create(['name' => 'Main Warehouse']);
        $this->mainTreasury = Treasury::factory()->create(['name' => 'Main Treasury']);

        // ربط الـ Admin بكل شيء
        $this->adminUser->warehouses()->attach($this->mainWarehouse->id, ['is_default' => true]);
        $this->adminUser->treasuries()->attach($this->mainTreasury->id, ['is_default' => true, 'can_view_balance' => true]);

        // ربط الـ Data Entry (الكاشير) بكل شيء
        $this->dataEntryUser->warehouses()->attach($this->mainWarehouse->id, ['is_default' => true]);
        $this->dataEntryUser->treasuries()->attach($this->mainTreasury->id, ['is_default' => true, 'can_view_balance' => false]);
    }
}
