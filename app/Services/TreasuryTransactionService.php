<?php

namespace App\Services;

use App\Enums\PartnerTransactionType;
use App\Enums\TreasuryTransactionType;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Illuminate\Support\Facades\DB;

class TreasuryTransactionService
{
    protected PartnerLedgerService $partnerLedgerService;

    // حقن الخدمة الجديدة هنا (Dependency Injection)
    public function __construct(PartnerLedgerService $partnerLedgerService)
    {
        $this->partnerLedgerService = $partnerLedgerService;
    }

    public function createTransaction(array $data): TreasuryTransaction
    {
        return DB::transaction(function () use ($data) {
            $lastTrx = TreasuryTransaction::withTrashed()
            ->lockForUpdate()
            ->max('trx_no');

            $data['trx_no'] = $lastTrx ? (int)$lastTrx + 1 : 1;
            $data['created_by'] = auth()->id() ?? null;

            $transaction = TreasuryTransaction::create($data);

            // 1. تطبيق الأثر على الخزينة فقط
            $this->applyTreasuryImpact($transaction);

            // 2. تسجيل الحركة في كشف حساب الشريك (إن وجد)
            $this->recordPartnerLedger($transaction);

            return $transaction;
        });
    }

    public function updateTransaction(TreasuryTransaction $transaction, array $data): TreasuryTransaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // 1. عكس الأثر القديم للخزينة
            $this->revertTreasuryImpact($transaction);

            // 2. حذف الحركة القديمة من كشف حساب الشريك
            if ($transaction->partner_id) {
                $type = $transaction->type === TreasuryTransactionType::RECEIPT
                        ? PartnerTransactionType::RECEIPT
                        : PartnerTransactionType::PAYMENT;
                $this->partnerLedgerService->deleteMovement($type, $transaction->id);
            }

            // 3. تحديث السند
            $transaction->update($data);
            $transaction = $transaction->fresh();

            // 4. تطبيق الأثر الجديد
            $this->applyTreasuryImpact($transaction);
            $this->recordPartnerLedger($transaction);

            return $transaction;
        });
    }

    public function deleteTransaction(TreasuryTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // 1. عكس أثر الخزينة
            $this->revertTreasuryImpact($transaction);

            // 2. حذف الحركة من كشف حساب الشريك لتعود أرصدته كما كانت
            if ($transaction->partner_id) {
                $type = $transaction->type === TreasuryTransactionType::RECEIPT
                        ? PartnerTransactionType::RECEIPT
                        : PartnerTransactionType::PAYMENT;
                $this->partnerLedgerService->deleteMovement($type, $transaction->id);
            }

            // 3. حذف السند المالي
            $transaction->delete();
        });
    }

    // =========================================================================
    // دوال مساعدة للخزينة (Treasury)
    // =========================================================================
    private function applyTreasuryImpact(TreasuryTransaction $transaction): void
    {
        $amount = $transaction->amount;
        $isReceipt = $transaction->type === TreasuryTransactionType::RECEIPT;
        $treasuryValue = $isReceipt ? $amount : -$amount;

        Treasury::where('id', $transaction->treasury_id)->increment('current_balance', $treasuryValue);
    }

    private function revertTreasuryImpact(TreasuryTransaction $transaction): void
    {
        $amount = $transaction->amount;
        $isReceipt = $transaction->type === TreasuryTransactionType::RECEIPT;
        $treasuryValue = $isReceipt ? -$amount : $amount;

        Treasury::where('id', $transaction->treasury_id)->increment('current_balance', $treasuryValue);
    }

    // =========================================================================
    // دالة مساعدة لدفتر الشركاء (Partner Ledger)
    // =========================================================================
    private function recordPartnerLedger(TreasuryTransaction $transaction): void
    {
        if (!$transaction->partner_id) {
            return; // إذا كان السند لا يخص شريكاً (مثلاً مصاريف نثرية)، نتجاهل الأمر
        }

        $isReceipt = $transaction->type === TreasuryTransactionType::RECEIPT;

        // إذا كان قبض (العميل سدد): دائن (له)
        // إذا كان صرف (دفعنا للمورد): مدين (عليه)
        $debit = $isReceipt ? 0 : $transaction->amount;
        $credit = $isReceipt ? $transaction->amount : 0;

        $transactionType = $isReceipt ? PartnerTransactionType::RECEIPT : PartnerTransactionType::PAYMENT;

        $this->partnerLedgerService->recordMovement([
            'partner_id' => $transaction->partner_id,
            'transaction_no' => $transaction->trx_no,
            'transaction_type' => $transactionType->value,
            'reference_id' => $transaction->id,
            'debit' => $debit,
            'credit' => $credit,
            'notes' => 'حركة مالية رقم: ' . $transaction->trx_no,
        ]);
    }
}
