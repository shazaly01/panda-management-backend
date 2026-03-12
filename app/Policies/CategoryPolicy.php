<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('category.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('category.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('category.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('category.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('category.delete');
    }

    // استعادة المحذوف تعتبر نوعاً من التحديث
    public function restore(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('category.update');
    }

    // الحذف النهائي يتطلب صلاحية الحذف
    public function forceDelete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('category.delete');
    }
}
