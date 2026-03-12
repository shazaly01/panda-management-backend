<?php

namespace App\Policies;

use App\Models\InventoryHeader;
use App\Models\User;

class InventoryHeaderPolicy
{
    public function viewAny(User $user): bool
    {
        // يُسمح له بالدخول إذا كان يملك صلاحية عرض التسويات أو التحويلات
        return $user->hasPermissionTo('adjustment.view') || $user->hasPermissionTo('transfer.view');
    }

    public function view(User $user, InventoryHeader $inventoryHeader): bool
    {
        $prefix = $this->getPermissionPrefix($inventoryHeader);
        return $user->hasPermissionTo("{$prefix}.view");
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('adjustment.create') || $user->hasPermissionTo('transfer.create');
    }

    public function update(User $user, InventoryHeader $inventoryHeader): bool
    {
        $prefix = $this->getPermissionPrefix($inventoryHeader);
        return $user->hasPermissionTo("{$prefix}.update");
    }

    public function delete(User $user, InventoryHeader $inventoryHeader): bool
    {
        $prefix = $this->getPermissionPrefix($inventoryHeader);
        return $user->hasPermissionTo("{$prefix}.delete");
    }

    /**
     * دالة مساعدة لتحديد نوع الحركة لإرجاع بادئة الصلاحية الصحيحة
     */
    private function getPermissionPrefix(InventoryHeader $inventoryHeader): string
    {
        // استخراج قيمة الـ enum (إذا كانت كائن enum) أو النص المباشر
        $typeValue = $inventoryHeader->trx_type->value ?? $inventoryHeader->trx_type;

        // إذا كان نوع الحركة يحتوي على كلمة adjustment (مثل adjustment_in أو adjustment_out)
        if (str_contains($typeValue, 'adjustment')) {
            return 'adjustment';
        }

        // افتراضياً إرجاع transfer
        return 'transfer';
    }
}
