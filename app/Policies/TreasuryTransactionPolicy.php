<?php

namespace App\Policies;

use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TreasuryTransactionPolicy
{
    use HandlesAuthorization;

    /**
     * هل يمكن للمستخدم عرض قائمة السندات؟
     */
    public function viewAny(User $user): bool
    {
        return $user->can('treasury_transaction.view');
    }

    /**
     * هل يمكن للمستخدم عرض تفاصيل سند محدد؟
     */
    public function view(User $user, TreasuryTransaction $treasuryTransaction): bool
    {
        return $user->can('treasury_transaction.view');
    }

    /**
     * هل يمكن للمستخدم إنشاء سند جديد؟
     */
    public function create(User $user): bool
    {
        return $user->can('treasury_transaction.create');
    }

    /**
     * هل يمكن للمستخدم تعديل السند؟
     */
    public function update(User $user, TreasuryTransaction $treasuryTransaction): bool
    {
        return $user->can('treasury_transaction.update');
    }

    /**
     * هل يمكن للمستخدم حذف السند (Soft Delete)؟
     */
    public function delete(User $user, TreasuryTransaction $treasuryTransaction): bool
    {
        return $user->can('treasury_transaction.delete');
    }

    /**
     * هل يمكن للمستخدم استعادة السند المحذوف؟
     */
    public function restore(User $user, TreasuryTransaction $treasuryTransaction): bool
    {
        // نربطها بصلاحية الحذف أو يمكنك إنشاء صلاحية خاصة للاستعادة لاحقاً
        return $user->can('treasury_transaction.delete');
    }

    /**
     * هل يمكن للمستخدم حذف السند نهائياً؟
     */
    public function forceDelete(User $user, TreasuryTransaction $treasuryTransaction): bool
    {
        return $user->can('treasury_transaction.delete');
    }
}
