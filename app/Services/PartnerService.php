<?php

namespace App\Services;

use App\Enums\PartnerType;
use App\Models\Partner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PartnerService
{
    /**
     * جلب الشركاء مع الفلترة (بحث، نوع)
     */
    public function getPartners(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Partner::query();

        // بحث بالاسم، الكود، الهاتف، أو الرقم الضريبي
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('tax_number', 'like', "%{$search}%");
            });
        }

        // فلترة بالنوع (1: عميل، 2: مورد، 3: كلاهما)
        if (!empty($filters['type'])) {
            $type = (int)$filters['type'];
            // إذا بحثنا عن عملاء (1)، نجلب النوع 1 والنوع 3 (لأنه عميل أيضاً)
            // إذا بحثنا عن موردين (2)، نجلب النوع 2 والنوع 3
            $query->where(function ($q) use ($type) {
                $q->where('type', $type)
                  ->orWhere('type', PartnerType::BOTH->value);
            });
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function createPartner(array $data): Partner
    {
        return DB::transaction(function () use ($data) {
            return Partner::create($data);
        });
    }

    public function updatePartner(Partner $partner, array $data): Partner
    {
        return DB::transaction(function () use ($partner, $data) {
            $partner->update($data);
            return $partner->fresh();
        });
    }

    public function deletePartner(Partner $partner): bool
    {
        // يمكن إضافة فحص: هل للعميل رصيد لا يساوي صفر؟ نمنع الحذف
        if ($partner->current_balance != 0) {
            throw new \Exception("لا يمكن حذف شريك لديه رصيد مالي.");
        }

        return $partner->delete();
    }
}
