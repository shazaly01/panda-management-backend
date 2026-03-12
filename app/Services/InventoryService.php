<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryHeader;
use App\Models\WarehouseItem;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    protected StockMovementService $stockMovementService;

    // حقن الخدمة المركزية لحركة الأصناف
    public function __construct(StockMovementService $stockMovementService)
    {
        $this->stockMovementService = $stockMovementService;
    }

    /**
     * 1. إنشاء حركة تسوية (جرد بالزيادة، عجز، إتلاف)
     */
    public function createAdjustment(array $headerData, array $items): InventoryHeader
    {
        return DB::transaction(function () use ($headerData, $items) {
            if (isset($headerData['warehouse_id'])) {
                $headerData['from_warehouse_id'] = $headerData['warehouse_id'];
            }

            // توليد رقم الحركة تلقائياً
            $lastTrx = InventoryHeader::max('trx_no');
            $headerData['trx_no'] = $lastTrx ? $lastTrx + 1 : 1;

            $header = InventoryHeader::create($headerData);

            foreach ($items as $item) {
                $header->details()->create($item);
                $qty = $item['qty'] * ($item['unit_factor'] ?? 1);

                $this->processAdjustmentStock($header, $item['item_id'], $qty);
            }

            return $header;
        });
    }

    /**
     * 2. إنشاء حركة تحويل بين مخزنين
     */
    public function createTransfer(array $headerData, array $items): InventoryHeader
    {
        return DB::transaction(function () use ($headerData, $items) {
            $headerData['trx_type'] = InventoryTransactionType::TRANSFER;

            $lastTrx = InventoryHeader::max('trx_no');
            $headerData['trx_no'] = $lastTrx ? $lastTrx + 1 : 1;

            $header = InventoryHeader::create($headerData);

            foreach ($items as $item) {
                $baseQty = $item['qty'] * ($item['unit_factor'] ?? 1);

                // التأكد من توفر الرصيد في المخزن المصدر قبل التحويل
                $this->checkStockAvailability($header->from_warehouse_id, $item['item_id'], $baseQty);

                $header->details()->create($item);

                // حركة صرف (سالبة) من المخزن المحول منه
                $this->stockMovementService->recordMovement(
                    $header->from_warehouse_id, $item['item_id'], -$baseQty, InventoryTransactionType::TRANSFER, $header, $header->trx_no
                );

                // حركة إضافة (موجبة) للمخزن المحول إليه
                $this->stockMovementService->recordMovement(
                    $header->to_warehouse_id, $item['item_id'], $baseQty, InventoryTransactionType::TRANSFER, $header, $header->trx_no
                );
            }

            return $header;
        });
    }

    /**
     * 3. تعديل حركة مخزنية (نظام المسح وإعادة الحساب - Wipe & Recalculate)
     */
    public function updateInventoryTransaction(InventoryHeader $header, array $headerData, array $items): InventoryHeader
    {
        return DB::transaction(function () use ($header, $headerData, $items) {
            // 1. جمع السجلات (مخزن + صنف) المتأثرة بالحركة القديمة
            $affectedLedgers = $this->getAffectedLedgers($header, $header->details);

            // 2. التدمير الشامل للحركات القديمة وتفاصيلها
            $this->stockMovementService->deleteMovementsByReference($header);
            $header->details()->delete();

            // 3. تحديث رأس الحركة
            if (isset($headerData['warehouse_id'])) {
                $headerData['from_warehouse_id'] = $headerData['warehouse_id'];
            }
            $header->update($headerData);

            // 4. إدخال التفاصيل الجديدة وتطبيق الحركات
            foreach ($items as $item) {
                $header->details()->create($item);
                $baseQty = $item['qty'] * ($item['unit_factor'] ?? 1);

                // تجميع السجلات المتأثرة بالحركة الجديدة أيضاً
                $affectedLedgers[] = $header->from_warehouse_id . '_' . $item['item_id'];

                if ($header->trx_type === InventoryTransactionType::TRANSFER) {
                    $affectedLedgers[] = $header->to_warehouse_id . '_' . $item['item_id'];

                    // تطبيق النقل الجديد
                    $this->checkStockAvailability($header->from_warehouse_id, $item['item_id'], $baseQty);

                    $this->stockMovementService->recordMovement(
                        $header->from_warehouse_id, $item['item_id'], -$baseQty, $header->trx_type, $header, $header->trx_no
                    );
                    $this->stockMovementService->recordMovement(
                        $header->to_warehouse_id, $item['item_id'], $baseQty, $header->trx_type, $header, $header->trx_no
                    );
                } else {
                    // تطبيق التسوية الجديدة
                    $this->processAdjustmentStock($header, $item['item_id'], $baseQty);
                }
            }

            // 5. السحر: إعادة بناء الدفتر لكل (مخزن + صنف) تأثر بالتعديل القديم أو الجديد
            $uniqueLedgers = array_unique($affectedLedgers);
            foreach ($uniqueLedgers as $ledgerStr) {
                list($warehouseId, $itemId) = explode('_', $ledgerStr);
                $this->stockMovementService->recalculateItemLedger((int)$warehouseId, (int)$itemId);
            }

            return $header;
        });
    }

    /**
     * 4. حذف حركة مخزنية بالكامل
     */
    public function deleteInventoryTransaction(InventoryHeader $header): void
    {
        DB::transaction(function () use ($header) {
            // 1. جمع السجلات (مخزن + صنف) المتأثرة قبل الحذف
            $affectedLedgers = array_unique($this->getAffectedLedgers($header, $header->details));

            // 2. مسح الحركات وتفاصيل الحركة ورأسها
            $this->stockMovementService->deleteMovementsByReference($header);
            $header->details()->delete();
            $header->delete();

            // 3. إعادة الحساب لضبط الأرصدة بعد مسح الحركة من المنتصف
            foreach ($affectedLedgers as $ledgerStr) {
                list($warehouseId, $itemId) = explode('_', $ledgerStr);
                $this->stockMovementService->recalculateItemLedger((int)$warehouseId, (int)$itemId);
            }
        });
    }

    // =========================================================================
    // دوال مساعدة (Private & Protected Methods)
    // =========================================================================

    /**
     * دالة مساعدة لتسجيل حركات التسويات (موجبة أو سالبة حسب النوع)
     */
    protected function processAdjustmentStock(InventoryHeader $header, int $itemId, float $qty): void
    {
        $warehouseId = $header->from_warehouse_id;
        $type = $header->trx_type;

        $isDeduction = in_array($type, [
            InventoryTransactionType::ADJUSTMENT_OUT,
            InventoryTransactionType::DAMAGE
        ]);

        if ($isDeduction) {
            // فحص الرصيد وتسجيل حركة سالبة
            $this->checkStockAvailability($warehouseId, $itemId, $qty);
            $this->stockMovementService->recordMovement($warehouseId, $itemId, -$qty, $type, $header, $header->trx_no);
        } else {
            // تسجيل حركة موجبة (مثل تسوية جردية بالزيادة)
            $this->stockMovementService->recordMovement($warehouseId, $itemId, $qty, $type, $header, $header->trx_no);
        }
    }

    /**
     * دالة لجمع المفاتيح (مخزن_صنف) للأصناف المتأثرة، مفيدة جداً في التحويلات
     */
    private function getAffectedLedgers(InventoryHeader $header, $details): array
    {
        $ledgers = [];
        foreach ($details as $detail) {
            // إضافة المخزن المصدر
            $ledgers[] = $header->from_warehouse_id . '_' . $detail->item_id;

            // إذا كان تحويلاً، يجب إضافة المخزن الوجهة أيضاً لإعادة حسابه
            if ($header->trx_type === InventoryTransactionType::TRANSFER) {
                $ledgers[] = $header->to_warehouse_id . '_' . $detail->item_id;
            }
        }
        return $ledgers;
    }

    /**
     * الحارس الصارم: التحقق من توفر الرصيد قبل الصرف أو التحويل
     */
    public function checkStockAvailability(int $warehouseId, int $itemId, float $requiredQty): void
    {
        $stock = WarehouseItem::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->first();

        $currentQty = $stock ? $stock->current_qty : 0;

        if ($currentQty < $requiredQty) {
            $item = Item::find($itemId);
            $itemName = $item ? $item->name : $itemId;
            throw new Exception("الرصيد غير كافٍ للصنف ({$itemName}). المتوفر: {$currentQty}, المطلوب: {$requiredQty}");
        }
    }
}
