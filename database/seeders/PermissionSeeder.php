<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إعادة تعيين الأدوار والصلاحيات المخزنة مؤقتاً (cache)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // --- تعريف الحارس ---
        // نستخدم 'api' لأننا نبني Backend API
        $guardName = 'api';

        // --- قائمة الصلاحيات الجديدة للمشروع (Sales & Inventory ERP) ---
        $permissions = [
            // لوحة التحكم والتقارير
            'dashboard.view',
            'reports.financial', // تقارير مالية
            'reports.inventory', // تقارير مخزنية

            // إدارة المستخدمين والصلاحيات
            'user.view', 'user.create', 'user.update', 'user.delete',
            'role.view', 'role.create', 'role.update', 'role.delete',

            // البيانات الأساسية للمنتجات (Product Catalog)
            'category.view', 'category.create', 'category.update', 'category.delete',
            'unit.view', 'unit.create', 'unit.update', 'unit.delete',
            'item.view', 'item.create', 'item.update', 'item.delete',

            // الكيانات الإدارية (Entities)
            'warehouse.view', 'warehouse.create', 'warehouse.update', 'warehouse.delete',
            'partner.view', 'partner.create', 'partner.update', 'partner.delete', // عملاء وموردين
            'treasury.view', 'treasury.create', 'treasury.update', 'treasury.delete', // خزائن وبنوك

            // العمليات المالية والمخزنية (Transactions)
            'sale.view', 'sale.create', 'sale.update', 'sale.delete','sale.change_status', // المبيعات
            'purchase.view', 'purchase.create', 'purchase.update', 'purchase.delete', // المشتريات

            'treasury_transaction.view', 'treasury_transaction.create', 'treasury_transaction.update', 'treasury_transaction.delete',

            'transfer.view', 'transfer.create', 'transfer.update', 'transfer.delete', // التحويلات المخزنية
            'adjustment.view', 'adjustment.create', 'adjustment.update', 'adjustment.delete', // التسويات الجردية

            // الورديات
            'shift.view', 'shift.create', 'shift.update', 'shift.delete', // فتح وإغلاق الورديات
        ];

        // إنشاء الصلاحيات في قاعدة البيانات
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]);
        }

        // --- إنشاء الأدوار وتوزيع الصلاحيات ---

        // 1. إنشاء دور "Super Admin"
        // هذا الدور عادة ما يتم تجاوزه في AuthServiceProvider ليأخذ كل الصلاحيات
        Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => $guardName,
        ]);

        // 2. إنشاء دور "Admin" (مدير النظام)
        $adminRole = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => $guardName,
        ]);
        // إعطاء المدير كل الصلاحيات المسجلة
        $adminRole->givePermissionTo(Permission::where('guard_name', $guardName)->get());


        // 3. إنشاء دور "Data Entry" (مدخل بيانات / كاشير متقدم)
        $dataEntryRole = Role::firstOrCreate([
            'name' => 'Data Entry',
            'guard_name' => $guardName,
        ]);
        // إعطاء هذا الدور صلاحيات (العرض، الإنشاء، التحديث) فقط
        // ومنعه من الحذف (Delete) لحماية البيانات المالية
        $dataEntryPermissions = Permission::where('guard_name', $guardName)
            ->where(function ($query) {
                $query->where('name', 'like', '%.view')
                      ->orWhere('name', 'like', '%.create')
                      ->orWhere('name', 'like', '%.update');
            })
            // استثناء بعض الصلاحيات الحساسة جداً من مدخل البيانات
            ->whereNotIn('name', ['role.create', 'role.update', 'user.create', 'user.update'])
            ->pluck('name');

        $dataEntryRole->syncPermissions($dataEntryPermissions);


        // 4. إنشاء دور "Auditor" (مراجع حسابات / مشاهد فقط)
        $auditorRole = Role::firstOrCreate([
            'name' => 'Auditor',
            'guard_name' => $guardName,
        ]);
        // إعطاء المراجع صلاحيات العرض فقط (View Only)
        $auditorPermissions = Permission::where('guard_name', $guardName)
            ->where('name', 'like', '%.view')
            ->pluck('name');

        $auditorRole->syncPermissions($auditorPermissions);


        // 5. إنشاء دور "Designer" (مصمم)
$designerRole = Role::firstOrCreate([
    'name' => 'Designer',
    'guard_name' => $guardName,
]);
// إعطاؤه صلاحيات العرض والإنشاء للمبيعات فقط (كمثال)
$designerRole->givePermissionTo(['sale.view', 'sale.create', 'dashboard.view']);
    }
}
