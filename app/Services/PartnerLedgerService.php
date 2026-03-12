<?php

namespace App\Services;

use App\Enums\PartnerTransactionType;
use App\Models\Partner;
use App\Models\PartnerLedger;
use Illuminate\Support\Facades\DB;

class PartnerLedgerService
{
    /**
     * تسجيل حركة جديدة في كشف حساب الشريك وتحديث رصيده
     */
    public function recordMovement(array $data): PartnerLedger
    {
        return DB::transaction(function () use ($data) {
            // 1. إضافة معرف المستخدم الحالي إذا كان مسجلاً للدخول
            $data['created_by'] = auth()->id() ?? null;

            // 2. تسجيل الحركة في دفتر الأستاذ (كشف الحساب)
            $ledger = PartnerLedger::create($data);

            // 3. تحديث الرصيد التجميعي في جدول الشريك (Partner)
            // القاعدة المحاسبية: الرصيد = المدين (عليه) - الدائن (له)
            $debit = $data['debit'] ?? 0;
            $credit = $data['credit'] ?? 0;
            $balanceImpact = $debit - $credit;

            if ($balanceImpact != 0) {
                Partner::where('id', $data['partner_id'])
                    ->increment('current_balance', $balanceImpact);
            }

            return $ledger;
        });
    }

    /**
     * حذف حركة من كشف الحساب (تُستخدم عند حذف فاتورة أو سند مالي)
     * وتقوم بعكس تأثيرها على رصيد الشريك تلقائياً
     */
    public function deleteMovement(PartnerTransactionType $type, int $referenceId): void
    {
        DB::transaction(function () use ($type, $referenceId) {
            // البحث عن كل الحركات المرتبطة بهذا المستند (فاتورة أو سند)
            $ledgers = PartnerLedger::where('transaction_type', $type)
                ->where('reference_id', $referenceId)
                ->get();

            foreach ($ledgers as $ledger) {
                // عكس التأثير المالي: نطرح (المدين - الدائن) من الرصيد الحالي
                $balanceImpact = $ledger->debit - $ledger->credit;

                if ($balanceImpact != 0) {
                    Partner::where('id', $ledger->partner_id)->decrement('current_balance', $balanceImpact);
                }

                // حذف السجل من كشف الحساب
                $ledger->delete();
            }
        });
    }
}
