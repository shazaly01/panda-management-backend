<?php

namespace App\Services;

use App\Models\Treasury;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TreasuryService
{
    public function getAllTreasuries(bool $activeOnly = true): Collection
    {
        $query = Treasury::query();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('code')->get();
    }

    public function createTreasury(array $data): Treasury
    {
        return DB::transaction(function () use ($data) {
            return Treasury::create($data);
        });
    }

    public function updateTreasury(Treasury $treasury, array $data): Treasury
    {
        return DB::transaction(function () use ($treasury, $data) {
            $treasury->update($data);
            return $treasury->fresh();
        });
    }

    /**
     * تحديد الخزائن المسموح للمستخدم التعامل معها
     * @param int $userId
     * @param array $permissions مصفوفة تحتوي ID الخزينة وصلاحياتها
     * مثال للبيانات القادمة:
     * [
     * ['treasury_id' => 1, 'can_view_balance' => true, 'is_default' => true],
     * ['treasury_id' => 2, 'can_view_balance' => false, 'is_default' => false]
     * ]
     */
    public function assignTreasuriesToUser(int $userId, array $treasuriesData): void
    {
        DB::transaction(function () use ($userId, $treasuriesData) {
            $user = User::findOrFail($userId);

            // تجهيز البيانات لـ sync
            $syncData = [];
            foreach ($treasuriesData as $item) {
                $tId = $item['treasury_id'];
                $syncData[$tId] = [
                    'can_view_balance' => $item['can_view_balance'] ?? false,
                    'is_default' => $item['is_default'] ?? false,
                ];
            }

            $user->treasuries()->sync($syncData);
        });
    }
}
