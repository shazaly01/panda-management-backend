<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Enums\ShortageStatus;
use App\Enums\TransactionStatus;
use App\Enums\TreasuryTransactionType;
use App\Enums\PartnerTransactionType;
use App\Models\Item;
use App\Models\Partner;
use App\Models\SalesHeader;
use App\Models\Shortage;
use App\Models\TreasuryTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class SalesService
{
    protected TreasuryTransactionService $treasuryTransactionService;
    protected StockMovementService $stockMovementService;
    protected PartnerLedgerService $partnerLedgerService;

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
     * إنشاء فاتورة مبيعات جديدة
     */
   /**
     * إنشاء فاتورة مبيعات جديدة
     */
    public function createSale(array $headerData, array $items): SalesHeader
    {
        return DB::transaction(function () use ($headerData, $items) {

            $lastTrxNo = SalesHeader::max('trx_no');
            $headerData['trx_no'] = $lastTrxNo ? $lastTrxNo + 1 : 1;

            // 1. جلب الحالة من الطلب، أو تعيينها كمسودة افتراضياً
            $status = $headerData['status'] ?? TransactionStatus::DRAFT->value;
            $totalAmount = 0;

            $salesHeader = SalesHeader::create(array_merge($headerData, [
                'status' => $status,
                'total_amount' => 0,
                'net_amount' => 0,
            ]));

            foreach ($items as $itemData) {
                $product = Item::findOrFail($itemData['item_id']);
                $qty = $itemData['qty'];
                $price = $itemData['price'];
                $length = $itemData['length'] ?? null;
                $width = $itemData['width'] ?? null;

                $area = (!empty($length) && !empty($width)) ? ($length * $width) : null;
                $rowTotal = $area ? ($area * $qty * $price) : ($qty * $price);

                $totalAmount += $rowTotal;
                $unitCost = $product->base_cost * ($itemData['unit_factor'] ?? 1);

                // 2. [السحر هنا]: خصم المخزون وتسجيل الحركة العادية *فقط* إذا كانت معتمدة
                if ($status === TransactionStatus::CONFIRMED->value) {
                    $this->processStockDeduction($salesHeader, $product, $qty, $itemData['unit_factor'] ?? 1);
                }

                $salesHeader->details()->create([
                    'item_id' => $itemData['item_id'],
                    'unit_id' => $itemData['unit_id'],
                    'qty' => $qty,
                    'unit_factor' => $itemData['unit_factor'] ?? 1,
                    'price' => $price,
                    'cost' => $unitCost,
                    'total_row' => $rowTotal,
                    'description' => $itemData['description'] ?? null,
                    'length' => $length,
                    'width' => $width,
                    'area' => $area,
                ]);
            }

            $discount = $headerData['discount_amount'] ?? 0;
            $tax = $headerData['tax_amount'] ?? 0;
            $netAmount = ($totalAmount - $discount) + $tax;

            $salesHeader->update([
                'total_amount' => $totalAmount,
                'net_amount' => $netAmount,
                'remaining_amount' => $netAmount - ($headerData['paid_amount'] ?? 0),
            ]);

            // 3. [السحر هنا]: تطبيق الأثر المالي (خزينة وعميل) *فقط* إذا كانت معتمدة
            if ($status === TransactionStatus::CONFIRMED->value) {
                $this->handleFinancialImpact($salesHeader);
            }

            return $salesHeader;
        });
    }

   /**
     * تحديث فاتورة مبيعات (نظام المسح وإعادة الحساب)
     */
    public function updateSale(SalesHeader $header, array $headerData, array $items): SalesHeader
    {
        return DB::transaction(function () use ($header, $headerData, $items) {
            $warehouseId = $header->warehouse_id;

            // 1. تحديد الحالة القديمة والجديدة
            $oldStatus = $header->status->value;
            $newStatus = $headerData['status'] ?? $oldStatus;

            // 2. الاحتفاظ بمعرفات الأصناف القديمة لإعادة حسابها لاحقاً
            $oldItemIds = $header->details()->pluck('item_id')->toArray();

            // 3. [التعديل الجوهري]: مسح الأثر المالي والمخزني القديم *فقط* إذا كانت الفاتورة القديمة معتمدة
            if ($oldStatus === TransactionStatus::CONFIRMED->value) {
                $this->reverseFinancialImpact($header);
                $this->stockMovementService->deleteMovementsByReference($header);
                Shortage::where('sales_header_id', $header->id)->delete();
            }

            // 4. التدمير الشامل (Wipe) للتفاصيل القديمة
            $header->details()->delete();

            // 5. تحديث الرأس مؤقتاً
            $header->update(array_merge($headerData, [
                'status' => $newStatus,
                'total_amount' => 0,
                'net_amount' => 0,
            ]));

            // 6. إدخال الأصناف الجديدة
            $totalAmount = 0;
            $newItemIds = [];

            foreach ($items as $itemData) {
                $product = Item::findOrFail($itemData['item_id']);
                $newItemIds[] = $product->id;

                $qty = $itemData['qty'];
                $price = $itemData['price'];
                $length = $itemData['length'] ?? null;
                $width = $itemData['width'] ?? null;

                $area = (!empty($length) && !empty($width)) ? ($length * $width) : null;
                $rowTotal = $area ? ($area * $qty * $price) : ($qty * $price);

                $totalAmount += $rowTotal;
                $unitCost = $product->base_cost * ($itemData['unit_factor'] ?? 1);

                // إنشاء الحركات الجديدة للفاتورة *فقط* إذا كانت الحالة الجديدة معتمدة
                if ($newStatus === TransactionStatus::CONFIRMED->value) {
                    $this->processStockDeduction($header, $product, $qty, $itemData['unit_factor'] ?? 1);
                }

                $header->details()->create([
                    'item_id' => $itemData['item_id'],
                    'unit_id' => $itemData['unit_id'],
                    'qty' => $qty,
                    'unit_factor' => $itemData['unit_factor'] ?? 1,
                    'price' => $price,
                    'cost' => $unitCost,
                    'total_row' => $rowTotal,
                    'description' => $itemData['description'] ?? null,
                    'length' => $length,
                    'width' => $width,
                    'area' => $area,
                ]);
            }

            // 7. الحسابات الختامية للفاتورة
            $discount = $headerData['discount_amount'] ?? 0;
            $tax = $headerData['tax_amount'] ?? 0;
            $netAmount = ($totalAmount - $discount) + $tax;

            $header->update([
                'total_amount' => $totalAmount,
                'net_amount' => $netAmount,
                'remaining_amount' => $netAmount - ($headerData['paid_amount'] ?? 0),
            ]);

            // 8. تطبيق الأثر المالي الجديد *فقط* إذا كانت الحالة الجديدة معتمدة
            if ($newStatus === TransactionStatus::CONFIRMED->value) {
                $this->handleFinancialImpact($header);
            }

            // 9. السحر الحقيقي: إعادة بناء دفتر الحركات للأصناف المتأثرة
            // لا نحتاج لإعادة الحساب إلا إذا كان هناك أثر مخزني (أي أن إحدى الحالتين معتمدة)
            if ($oldStatus === TransactionStatus::CONFIRMED->value || $newStatus === TransactionStatus::CONFIRMED->value) {
                $affectedItems = array_unique(array_merge($oldItemIds, $newItemIds));
                foreach ($affectedItems as $itemId) {
                    $this->stockMovementService->recalculateItemLedger($warehouseId, $itemId);
                }
            }

            return $header;
        });
    }


    /**
     * حذف فاتورة مبيعات بالكامل
     */
    public function deleteSale(SalesHeader $header): void
    {
        DB::transaction(function () use ($header) {
            $warehouseId = $header->warehouse_id;
            $affectedItemIds = $header->details()->pluck('item_id')->toArray();

            // 1. مسح الأثر المالي القديم
            $this->reverseFinancialImpact($header);

            // 2. مسح الحركات والعجز المرتبط بالفاتورة
            $this->stockMovementService->deleteMovementsByReference($header);
            Shortage::where('sales_header_id', $header->id)->delete();

            // 3. حذف الفاتورة وتفاصيلها (Cascade سيحذف التفاصيل إذا كان مهيأ في قاعدة البيانات، وإلا نحذفها يدوياً)
            $header->details()->delete();
            $header->delete();

            // 4. إعادة بناء الدفتر للأصناف التي تم حذف حركاتها
            $affectedItems = array_unique($affectedItemIds);
            foreach ($affectedItems as $itemId) {
                $this->stockMovementService->recalculateItemLedger($warehouseId, $itemId);
            }
        });
    }

    // =========================================================================
    // الدوال المساعدة للماليات
    // =========================================================================

   private function handleFinancialImpact(SalesHeader $header): void
    {
        // 1. تسجيل إجمالي الفاتورة على العميل (مدين) في كشف الحساب
        // العميل الآن مطالب بدفع الصافي (net_amount) بالكامل
        if ($header->partner_id) {
            $this->partnerLedgerService->recordMovement([
                'partner_id' => $header->partner_id,
                'transaction_no' => $header->trx_no,
                'transaction_type' => PartnerTransactionType::SALES_INVOICE->value,
                'reference_id' => $header->id,
                'debit' => $header->net_amount, // العميل مدين بصافي الفاتورة
                'credit' => 0,                  // الدائن صفر هنا
                'notes' => 'فاتورة مبيعات رقم: ' . $header->trx_no,
            ]);
        }

        // 2. تسجيل الدفعة (إن وجدت)
        // دالة الخزينة التي عدلناها في الخطوة السابقة ستقوم تلقائياً بخصم هذا المبلغ من كشف حساب العميل!
        if (($header->paid_amount ?? 0) > 0 && $header->treasury_id) {
            $this->treasuryTransactionService->createTransaction([
                'trx_date' => $header->invoice_date ?? now(),
                'type' => TreasuryTransactionType::RECEIPT, // سند قبض
                'treasury_id' => $header->treasury_id,
                'partner_id' => $header->partner_id,
                'amount' => $header->paid_amount,
                'sales_header_id' => $header->id,
                'notes' => 'سداد آلي - فاتورة مبيعات رقم: ' . $header->trx_no,
            ]);
        }
    }


   private function reverseFinancialImpact(SalesHeader $header): void
    {
        // 1. حذف حركة الفاتورة من كشف حساب العميل
        // هذه الدالة ستعكس الأثر المالي (تطرح مبلغ الفاتورة من رصيده) تلقائياً
        if ($header->partner_id) {
            $this->partnerLedgerService->deleteMovement(
                PartnerTransactionType::SALES_INVOICE,
                $header->id
            );
        }

        // 2. حذف سند القبض المرتبط بالفاتورة (إن وجد)
        // دالة حذف الخزينة ستقوم بحذف حركتها من كشف الحساب وترد الرصيد تلقائياً أيضاً!
        $transaction = TreasuryTransaction::where('sales_header_id', $header->id)->first();
        if ($transaction) {
            $this->treasuryTransactionService->deleteTransaction($transaction);
        }
    }

    // =========================================================================
    // الدالة المساعدة للمخزون
    // =========================================================================

    private function processStockDeduction(SalesHeader $header, Item $item, float $qty, float $factor): void
    {
        $warehouseId = $header->warehouse_id;
        $requiredQtyInBase = $qty * $factor;

        $warehouseItem = DB::table('warehouse_items')
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $item->id)
            ->first();

        $currentQty = $warehouseItem ? $warehouseItem->current_qty : 0;

        if ($currentQty >= $requiredQtyInBase) {
            $this->stockMovementService->recordMovement(
                $warehouseId,
                $item->id,
                -$requiredQtyInBase,
                InventoryTransactionType::SALES,
                $header,
                $header->trx_no
            );
        } else {
            if ($currentQty > 0) {
                $this->stockMovementService->recordMovement(
                    $warehouseId,
                    $item->id,
                    -$currentQty,
                    InventoryTransactionType::SALES,
                    $header,
                    $header->trx_no
                );
            }

            $shortageQty = $requiredQtyInBase - ($currentQty > 0 ? $currentQty : 0);

            Shortage::create([
                'sales_header_id' => $header->id,
                'item_id' => $item->id,
                'warehouse_id' => $warehouseId,
                'shortage_qty' => $shortageQty,
                'status' => ShortageStatus::PENDING,
            ]);
        }
    }



    /**
     * تغيير حالة الفاتورة وتطبيق أو عكس الأثر المالي والمخزني
     */
    public function changeSaleStatus(SalesHeader $header, string $newStatus): SalesHeader
    {
        return DB::transaction(function () use ($header, $newStatus) {
            // إذا كانت الحالة الحالية مطابقة للجديدة، لا تفعل شيئاً
            if ($header->status->value === $newStatus) {
                return $header;
            }

            $warehouseId = $header->warehouse_id;

            if ($newStatus === TransactionStatus::CONFIRMED->value) {
                // --- تحويل من مسودة إلى معتمدة ---
                // 1. تطبيق الخصم المخزني لكل صنف
                foreach ($header->details as $detail) {
                    $this->processStockDeduction($header, $detail->item, $detail->qty, $detail->unit_factor);
                }
                // 2. تطبيق الأثر المالي (خزينة وعميل)
                $this->handleFinancialImpact($header);

            } elseif ($newStatus === TransactionStatus::DRAFT->value) {
                // --- تحويل من معتمدة إلى مسودة (تراجع) ---
                // 1. عكس الأثر المالي
                $this->reverseFinancialImpact($header);

                // 2. مسح الحركات والعجز المرتبط بهذه الفاتورة
                $this->stockMovementService->deleteMovementsByReference($header);
                Shortage::where('sales_header_id', $header->id)->delete();

                // 3. إعادة بناء دفتر الحركات للأصناف المتأثرة
                $affectedItemIds = $header->details()->pluck('item_id')->unique();
                foreach ($affectedItemIds as $itemId) {
                    $this->stockMovementService->recalculateItemLedger($warehouseId, $itemId);
                }
            }

            // تحديث الحالة في قاعدة البيانات
            $header->update(['status' => $newStatus]);

            return $header;
        });
    }
}
