<?php

namespace App\Policies;

use App\Models\SalesHeader;
use App\Models\User;

class SalesHeaderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('sale.view');
    }

    public function view(User $user, SalesHeader $salesHeader): bool
    {
        return $user->hasPermissionTo('sale.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('sale.create');
    }

    public function update(User $user, SalesHeader $salesHeader): bool
    {
        // يمكن إضافة شرط إضافي هنا: مثلاً منع التعديل إذا كانت الفاتورة معتمدة "confirmed"
        return $user->hasPermissionTo('sale.update');
    }

    public function delete(User $user, SalesHeader $salesHeader): bool
    {
        return $user->hasPermissionTo('sale.delete');
    }

    public function changeStatus(User $user, SalesHeader $salesHeader): bool
    {
        return $user->hasPermissionTo('sale.change_status');
    }
}
