<?php

namespace App\Services;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    public function getAllWarehouses(bool $activeOnly = true): Collection
    {
        $query = Warehouse::query();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('code')->get();
    }

    public function createWarehouse(array $data): Warehouse
    {
        return DB::transaction(function () use ($data) {
            return Warehouse::create($data);
        });
    }

    public function updateWarehouse(Warehouse $warehouse, array $data): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data) {
            $warehouse->update($data);
            return $warehouse->fresh();
        });
    }

    /**
     * تعيين المخازن المسموح بها للمستخدم
     * @param int $userId
     * @param array $warehouseIds مصفوفة بأرقام المخازن [1, 2, 5]
     * @param int|null $defaultWarehouseId المخزن الافتراضي
     */
    public function assignWarehousesToUser(int $userId, array $warehouseIds, ?int $defaultWarehouseId = null): void
    {
        DB::transaction(function () use ($userId, $warehouseIds, $defaultWarehouseId) {
            // تجهيز البيانات لجدول الـ Pivot
            $syncData = [];
            foreach ($warehouseIds as $wId) {
                $syncData[$wId] = ['is_default' => ($wId == $defaultWarehouseId)];
            }

            // استخدام sync لتحديث العلاقات (حذف القديم وإضافة الجديد)
            $user = \App\Models\User::findOrFail($userId);
            $user->warehouses()->sync($syncData);
        });
    }
}
