<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('unit.view');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('unit.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('unit.create');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('unit.update');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('unit.delete');
    }

    public function restore(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('unit.update');
    }

    public function forceDelete(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('unit.delete');
    }
}
