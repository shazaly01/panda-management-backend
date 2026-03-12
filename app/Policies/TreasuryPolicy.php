<?php

namespace App\Policies;

use App\Models\Treasury;
use App\Models\User;

class TreasuryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('treasury.view');
    }

    public function view(User $user, Treasury $treasury): bool
    {
        return $user->hasPermissionTo('treasury.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('treasury.create');
    }

    public function update(User $user, Treasury $treasury): bool
    {
        return $user->hasPermissionTo('treasury.update');
    }

    public function delete(User $user, Treasury $treasury): bool
    {
        return $user->hasPermissionTo('treasury.delete');
    }
}
