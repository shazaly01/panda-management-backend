<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    /**
     * جلب شجرة التصنيفات كاملة (للواجهة الأمامية)
     * تعيد الأب وبداخله الأبناء (children)
     */
    public function getCategoryTree(): Collection
    {
        return Category::with('children')
            ->whereNull('parent_id') // نجلب فقط الآباء الرئيسيين
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    /**
     * جلب قائمة مسطحة (للقوائم المنسدلة البسيطة)
     */
    public function getFlatList(bool $activeOnly = true): Collection
    {
        $query = Category::query();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->select(['id', 'name', 'code', 'parent_id'])->orderBy('code')->get();
    }

    public function createCategory(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            return Category::create($data);
        });
    }

    public function updateCategory(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $category->update($data);
            return $category->fresh();
        });
    }

    public function deleteCategory(Category $category): bool
    {
        // منطق إضافي: لا تحذف تصنيفاً إذا كان لديه أبناء
        if ($category->children()->count() > 0) {
            throw new \Exception("لا يمكن حذف تصنيف يحتوي على تصنيفات فرعية.");
        }

        // لا تحذف تصنيفاً إذا كان مرتبطاً بأصناف (يتم التعامل معه بـ Exception من قاعدة البيانات)
        return $category->delete();
    }
}
