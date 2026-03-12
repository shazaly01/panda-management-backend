<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTreasuryTransactionRequest;
use App\Http\Requests\UpdateTreasuryTransactionRequest;
use App\Http\Resources\TreasuryTransactionResource;
use App\Models\TreasuryTransaction;
use App\Services\TreasuryTransactionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TreasuryTransactionController extends Controller
{
    public function __construct(private TreasuryTransactionService $transactionService)
    {
        // ربط الصلاحيات (Policy) بدوال الـ Controller تلقائياً
        $this->authorizeResource(TreasuryTransaction::class, 'treasury_transaction');
    }

    /**
     * عرض قائمة السندات المالية (مع التصفح والبحث إن لزم)
     */
    public function index(): AnonymousResourceCollection
    {
        $transactions = TreasuryTransaction::with(['treasury', 'partner', 'createdBy'])
            ->latest('trx_no')
            ->paginate(15);

        return TreasuryTransactionResource::collection($transactions);
    }

    /**
     * إنشاء وحفظ سند مالي جديد
     */
    public function store(StoreTreasuryTransactionRequest $request): TreasuryTransactionResource
    {
        // تمرير البيانات الموثوقة للخدمة لإنشاء السند وتعديل الأرصدة
        $transaction = $this->transactionService->createTransaction($request->validated());

        // إعادة السند الجديد بعد تحميل العلاقات
        return new TreasuryTransactionResource($transaction->load(['treasury', 'partner', 'createdBy']));
    }

    /**
     * عرض تفاصيل سند مالي محدد
     */
    public function show(TreasuryTransaction $treasuryTransaction): TreasuryTransactionResource
    {
        return new TreasuryTransactionResource($treasuryTransaction->load(['treasury', 'partner', 'createdBy']));
    }

    /**
     * تعديل سند مالي (عكس الأثر القديم وتطبيق الجديد)
     */
    public function update(UpdateTreasuryTransactionRequest $request, TreasuryTransaction $treasuryTransaction): TreasuryTransactionResource
    {
        $updatedTransaction = $this->transactionService->updateTransaction($treasuryTransaction, $request->validated());

        return new TreasuryTransactionResource($updatedTransaction->load(['treasury', 'partner', 'createdBy']));
    }

    /**
     * حذف السند المالي وعكس أثره المالي
     */
    public function destroy(TreasuryTransaction $treasuryTransaction): Response
    {
        $this->transactionService->deleteTransaction($treasuryTransaction);

        return response()->noContent();
    }
}
