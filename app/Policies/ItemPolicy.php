<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('item.view');
    }

    public function view(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('item.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('item.create');
    }

    public function update(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('item.update');
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('item.delete');
    }

    public function restore(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('item.update');
    }

    public function forceDelete(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('item.delete');
    }
}
