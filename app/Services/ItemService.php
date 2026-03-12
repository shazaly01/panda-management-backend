<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ItemService
{
    /**
     * جلب قائمة الأصناف مع الفلترة والترقيم (Pagination)
     */
    public function getItems(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Item::query()->with(['category', 'unit1', 'unit2', 'unit3']);

        // بحث بالاسم أو الباركود أو الكود
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // فلترة بالتصنيف
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // فلترة بالحالة (نشط/غير نشط)
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool)$filters['is_active']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء صنف جديد
     */
    public function createItem(array $data): Item
    {
        return DB::transaction(function () use ($data) {
            // هنا يمكن إضافة منطق للتحقق من معاملات التحويل
            // مثلاً: التأكد من أن factor2 > 1 إذا وجدت وحدة ثانية

            return Item::create($data);
        });
    }

    /**
     * تحديث بيانات الصنف
     */
    public function updateItem(Item $item, array $data): Item
    {
        return DB::transaction(function () use ($item, $data) {
            $item->update($data);
            return $item->fresh(['category', 'unit1', 'unit2', 'unit3']);
        });
    }

    /**
     * حذف صنف
     */
    public function deleteItem(Item $item): bool
    {
        // التحقق قبل الحذف: هل الصنف موجود في أي فاتورة؟
        // (قاعدة البيانات ستقوم بالمنع عبر FK، ولكن يمكننا إضافة تحقق إضافي لرسالة أوضح)

        return $item->delete();
    }
}
