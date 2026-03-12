<?php

namespace App\Policies;

use App\Models\PurchasesHeader;
use App\Models\User;

class PurchasesHeaderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('purchase.view');
    }

    public function view(User $user, PurchasesHeader $purchasesHeader): bool
    {
        return $user->hasPermissionTo('purchase.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('purchase.create');
    }

    public function update(User $user, PurchasesHeader $purchasesHeader): bool
    {
        return $user->hasPermissionTo('purchase.update');
    }

    public function delete(User $user, PurchasesHeader $purchasesHeader): bool
    {
        return $user->hasPermissionTo('purchase.delete');
    }
}
