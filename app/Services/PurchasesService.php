<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Enums\ShortageStatus;
use App\Enums\TransactionStatus;
use App\Enums\TreasuryTransactionType;
use App\Enums\PartnerTransactionType;
use App\Models\Item;
use App\Models\Partner;
use App\Models\PurchasesHeader;
use App\Models\Shortage;
use App\Models\TreasuryTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchasesService
{
    protected TreasuryTransactionService $treasuryTransactionService;
    protected StockMovementService $stockMovementService;
    protected PartnerLedgerService $partnerLedgerService;

    // حقن خدمة السندات وحركة الأصناف (بدلاً من خدمة المخزون القديمة)
    public function __construct(
        TreasuryTransactionService $treasuryTransactionService,
        StockMovementService $stockMovementService,
        PartnerLedgerService $partnerLedgerService
    ) {
        $this->treasuryTransactionService = $treasuryTransactionService;
        $this->stockMovementService = $stockMovementService;
        $this->partnerLedgerService = $partnerLedgerService;
    }

    /**
     * إنشاء فاتورة مشتريات
     */
    public function createPurchase(array $headerData, array $items): PurchasesHeader
    {
        return DB::transaction(function () use ($headerData, $items) {
             $lastTrxNo = PurchasesHeader::max('trx_no');
        $headerData['trx_no'] = $lastTrxNo ? $lastTrxNo + 1 : 1;

            $totalAmount = 0;

            // 1. إنشاء رأس الفاتورة
            $purchaseHeader = PurchasesHeader::create(array_merge($headerData, [
                'status' => TransactionStatus::CONFIRMED,
                'total_amount' => 0,
                'net_amount' => 0,
            ]));

            foreach ($items as $itemData) {
                $qty = $itemData['qty'];
                $cost = $itemData['unit_cost'] ?? $itemData['price'];
                $length = $itemData['length'] ?? null;
                $width = $itemData['width'] ?? null;

                $area = (!empty($length) && !empty($width)) ? ($length * $width) : null;
                $rowTotal = $area ? ($area * $qty * $cost) : ($qty * $cost);

                $totalAmount += $rowTotal;
                $qtyInBase = $qty * ($itemData['unit_factor'] ?? 1);

                // 2. تحديث التكلفة
                $this->updateItemCost($itemData['item_id'], $qtyInBase, $cost / ($itemData['unit_factor'] ?? 1));

                // 3. إضافة الرصيد للمخزن وتسجيل الحركة (موجبة)
                $this->stockMovementService->recordMovement(
                    $purchaseHeader->warehouse_id,
                    $itemData['item_id'],
                    $qtyInBase,
                    InventoryTransactionType::PURCHASES,
                    $purchaseHeader,
                    $purchaseHeader->trx_no
                );

                // 4. تسوية العجز (إن وجد) بتمرير الفاتورة كمرجع
                $this->resolveShortages($purchaseHeader, $itemData['item_id'], $qtyInBase);

                $purchaseHeader->details()->create([
                    'item_id' => $itemData['item_id'],
                    'unit_id' => $itemData['unit_id'],
                    'qty' => $qty,
                    'unit_cost' => $cost,
                    'total_row' => $rowTotal,
                    'unit_factor' => $itemData['unit_factor'] ?? 1,
                    'description' => $itemData['description'] ?? null,
                    'length' => $length,
                    'width' => $width,
                    'area' => $area,
                    'production_date' => $itemData['production_date'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                ]);
            }

            // 5. تحديث ماليات الفاتورة
            $discount = $headerData['discount_amount'] ?? 0;
            $tax = $headerData['tax_amount'] ?? 0;
            $netAmount = ($totalAmount - $discount) + $tax;

            $purchaseHeader->update([
                'total_amount' => $totalAmount,
                'net_amount' => $netAmount,
                'remaining_amount' => $netAmount - ($headerData['paid_amount'] ?? 0),
            ]);

            // 6. تحديث رصيد المورد وإصدار سند الصرف آلياً
            $this->handleFinancialImpact($purchaseHeader);

            return $purchaseHeader;
        });
    }

    /**
     * تعديل فاتورة مشتريات (نظام المسح وإعادة الحساب)
     */
    public function updatePurchase(PurchasesHeader $header, array $headerData, array $items): PurchasesHeader
    {
        return DB::transaction(function () use ($header, $headerData, $items) {
            $warehouseId = $header->warehouse_id;

            // 1. الاحتفاظ بمعرفات الأصناف القديمة لإعادة حساب دفاترها
            $oldItemIds = $header->details()->pluck('item_id')->toArray();

            // 2. عكس الأثر المالي القديم
            $this->reverseFinancialImpact($header);

            // 3. مسح تاريخ الفاتورة المخزني بالكامل (التدمير الشامل)
            // هذا سيمسح حركات الدخول (PURCHASES) وحركات سداد العجز المرتبطة بهذه الفاتورة
            $this->stockMovementService->deleteMovementsByReference($header);
            $header->details()->delete();

            // 4. تحديث الرأس
            $header->update(array_merge($headerData, [
                'total_amount' => 0,
                'net_amount' => 0,
            ]));

            // 5. إدخال التفاصيل الجديدة وتطبيق التأثيرات
            $totalAmount = 0;
            $newItemIds = [];

            foreach ($items as $itemData) {
                $newItemIds[] = $itemData['item_id'];

                $qty = $itemData['qty'];
                $cost = $itemData['unit_cost'] ?? $itemData['price'];
                $length = $itemData['length'] ?? null;
                $width = $itemData['width'] ?? null;

                $area = (!empty($length) && !empty($width)) ? ($length * $width) : null;
                $rowTotal = $area ? ($area * $qty * $cost) : ($qty * $cost);

                $totalAmount += $rowTotal;
                $qtyInBase = $qty * ($itemData['unit_factor'] ?? 1);

                $this->updateItemCost($itemData['item_id'], $qtyInBase, $cost / ($itemData['unit_factor'] ?? 1));

                // تسجيل حركة الدخول الجديدة
                $this->stockMovementService->recordMovement(
                    $warehouseId,
                    $itemData['item_id'],
                    $qtyInBase,
                    InventoryTransactionType::PURCHASES,
                    $header,
                    $header->trx_no
                );

                // تسوية العجز إن وجد
                $this->resolveShortages($header, $itemData['item_id'], $qtyInBase);

                $header->details()->create([
                    'item_id' => $itemData['item_id'],
                    'unit_id' => $itemData['unit_id'],
                    'qty' => $qty,
                    'unit_cost' => $cost,
                    'total_row' => $rowTotal,
                    'unit_factor' => $itemData['unit_factor'] ?? 1,
                    'description' => $itemData['description'] ?? null,
                    'length' => $length,
                    'width' => $width,
                    'area' => $area,
                    'production_date' => $itemData['production_date'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                ]);
            }

            $discount = $headerData['discount_amount'] ?? 0;
            $tax = $headerData['tax_amount'] ?? 0;
            $netAmount = ($totalAmount - $discount) + $tax;

            $header->update([
                'total_amount' => $totalAmount,
                'net_amount' => $netAmount,
                'remaining_amount' => $netAmount - ($headerData['paid_amount'] ?? 0),
            ]);

            // تطبيق التأثير المالي الجديد وإصدار سند جديد
            $this->handleFinancialImpact($header);

            // 6. إعادة بناء دفتر الحركات للأصناف المتأثرة
            $affectedItems = array_unique(array_merge($oldItemIds, $newItemIds));
            foreach ($affectedItems as $itemId) {
                $this->stockMovementService->recalculateItemLedger($warehouseId, $itemId);
            }

            return $header;
        });
    }

    /**
     * حذف فاتورة مشتريات
     */
    public function deletePurchase(PurchasesHeader $header): void
    {
        DB::transaction(function () use ($header) {
            $warehouseId = $header->warehouse_id;
            $affectedItemIds = $header->details()->pluck('item_id')->toArray();

            // 1. عكس الأثر المالي
            $this->reverseFinancialImpact($header);

            // 2. مسح تاريخ الحركات بالكامل
            $this->stockMovementService->deleteMovementsByReference($header);

            // 3. حذف الفاتورة
            $header->details()->delete();
            $header->delete();

            // 4. إعادة بناء دفتر الأستاذ للأصناف لضبط الرصيد
            $affectedItems = array_unique($affectedItemIds);
            foreach ($affectedItems as $itemId) {
                $this->stockMovementService->recalculateItemLedger($warehouseId, $itemId);
            }
        });
    }

    // =========================================================================
    // دوال مساعدة (Private Methods)
    // =========================================================================

  private function handleFinancialImpact(PurchasesHeader $header): void
    {
        // 1. تسجيل إجمالي الفاتورة كالتزام لصالح المورد (دائن) في كشف الحساب
        // نحن مطالبون بدفع الصافي (net_amount) بالكامل للمورد
        if ($header->partner_id) {
            $this->partnerLedgerService->recordMovement([
                'partner_id' => $header->partner_id,
                'transaction_no' => $header->trx_no,
                'transaction_type' => PartnerTransactionType::PURCHASE_INVOICE->value,
                'reference_id' => $header->id,
                'debit' => 0,                    // المدين صفر هنا
                'credit' => $header->net_amount, // المورد دائن (له فلوس) بصافي الفاتورة
                'notes' => 'فاتورة مشتريات رقم: ' . $header->trx_no,
            ]);
        }

        // 2. تسجيل الدفعة التي دفعناها للمورد (إن وجدت)
        // دالة الخزينة ستقوم بإنشاء "سند صرف" وهذا السند سيُسجل كـ "مدين" على المورد في كشف حسابه تلقائياً
        if (($header->paid_amount ?? 0) > 0 && $header->treasury_id) {
            $this->treasuryTransactionService->createTransaction([
                'trx_date' => $header->invoice_date ?? now(),
                'type' => TreasuryTransactionType::PAYMENT, // سند صرف
                'treasury_id' => $header->treasury_id,
                'partner_id' => $header->partner_id,
                'amount' => $header->paid_amount,
                'purchases_header_id' => $header->id,
                'notes' => 'صرف آلي - فاتورة مشتريات رقم: ' . $header->trx_no,
            ]);
        }
    }


   private function reverseFinancialImpact(PurchasesHeader $header): void
    {
        // 1. حذف حركة الفاتورة من كشف حساب المورد
        // الخدمة ستعكس الأثر المالي تلقائياً (تطرح مبلغ الفاتورة من الجانب الدائن)
        if ($header->partner_id) {
            $this->partnerLedgerService->deleteMovement(
                PartnerTransactionType::PURCHASE_INVOICE,
                $header->id
            );
        }

        // 2. حذف سند الصرف المرتبط بالفاتورة (إن وجد)
        // دالة الخزينة ستتكفل بحذف حركته من كشف الحساب وعكس رصيده
        $transaction = TreasuryTransaction::where('purchases_header_id', $header->id)->first();
        if ($transaction) {
            $this->treasuryTransactionService->deleteTransaction($transaction);
        }
    }

    private function updateItemCost(int $itemId, float $newQty, float $newCost): void
    {
        $item = Item::find($itemId);
        $currentCost = $item->base_cost;

        if ($currentCost == 0) {
            $item->update(['base_cost' => $newCost]);
        } else {
            $totalCurrentStock = DB::table('warehouse_items')->where('item_id', $itemId)->sum('current_qty');
            if ($totalCurrentStock + $newQty > 0) {
                $avgCost = (($totalCurrentStock * $currentCost) + ($newQty * $newCost)) / ($totalCurrentStock + $newQty);
                $item->update(['base_cost' => $avgCost]);
            }
        }
    }

    /**
     * تسوية العجز (تم تمرير $header لتوثيق الحركة)
     */
    private function resolveShortages(PurchasesHeader $header, int $itemId, float $addedQty): void
    {
        $warehouseId = $header->warehouse_id;

        $shortages = Shortage::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('status', ShortageStatus::PENDING)
            ->orderBy('created_at')
            ->get();

        $remainingQty = $addedQty;

        foreach ($shortages as $shortage) {
            if ($remainingQty <= 0) break;

            $needed = $shortage->shortage_qty;

            if ($remainingQty >= $needed) {
                // تسجيل حركة خصم (سالبة) لتعويض العجز، وربطها بالفاتورة
                $this->stockMovementService->recordMovement(
                    $warehouseId, $itemId, -$needed, InventoryTransactionType::SALES, $header, $header->trx_no, 'تسوية عجز سابقة آلياً'
                );

                $shortage->update(['status' => ShortageStatus::RESOLVED]);
                $remainingQty -= $needed;
            } else {
                $this->stockMovementService->recordMovement(
                    $warehouseId, $itemId, -$remainingQty, InventoryTransactionType::SALES, $header, $header->trx_no, 'تسوية عجز جزئية آلياً'
                );

                $shortage->update(['shortage_qty' => $needed - $remainingQty]);
                $remainingQty = 0;
            }
        }
    }
}
