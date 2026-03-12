<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class UnitService
{
    /**
     * جلب جميع الوحدات (يمكن استخدامها في القوائم المنسدلة)
     */
    public function getAllUnits(bool $activeOnly = true): Collection
    {
        $query = Unit::query();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * إنشاء وحدة جديدة
     */
    public function createUnit(array $data): Unit
    {
        return DB::transaction(function () use ($data) {
            // يمكن هنا إضافة منطق لتوليد الكود تلقائياً إذا لم يرسله المستخدم
            // لكن سنفترض أنه مرسل حالياً
            return Unit::create($data);
        });
    }

    /**
     * تحديث وحدة
     */
    public function updateUnit(Unit $unit, array $data): Unit
    {
        return DB::transaction(function () use ($unit, $data) {
            $unit->update($data);
            return $unit->fresh();
        });
    }

    /**
     * حذف وحدة
     * نتحقق أولاً هل هي مرتبطة بأصناف أم لا
     */
    public function deleteUnit(Unit $unit): bool
    {
        // التحقق من الارتباطات (اختياري لأن قاعدة البيانات تمنع الحذف لوجود Restrict)
        // ولكن هذا مثال للمنطق الذي يوضع في السرفيس

        return $unit->delete();
    }
}
