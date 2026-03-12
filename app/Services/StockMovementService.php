<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Models\ItemMovement;
use App\Models\WarehouseItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    /**
     * تسجيل حركة الصنف وتحديث رصيده (تُستخدم للحركات الجديدة والإضافات)
     */
    public function recordMovement(
        int $warehouseId,
        int $itemId,
        float $qty,
        InventoryTransactionType $trxType,
        ?Model $reference = null,
        $transactionNo = null,
        ?string $notes = null
    ): void {
        if ($qty == 0) return;

        DB::transaction(function () use ($warehouseId, $itemId, $qty, $trxType, $reference, $transactionNo, $notes) {
            WarehouseItem::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'item_id' => $itemId],
                ['current_qty' => 0]
            );

            $warehouseItem = WarehouseItem::where('warehouse_id', $warehouseId)
                ->where('item_id', $itemId)
                ->lockForUpdate() // قفل تشاؤمي لمنع تداخل الحركات
                ->first();

            $previousQty = $warehouseItem->current_qty;
            $currentQty = $previousQty + $qty;

            $warehouseItem->update(['current_qty' => $currentQty]);

            ItemMovement::create([
                'warehouse_id'   => $warehouseId,
                'item_id'        => $itemId,
                'transaction_no' => $transactionNo,
                'trx_type'       => $trxType,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference ? $reference->id : null,
                'previous_qty'   => $previousQty,
                'trx_qty'        => $qty,
                'current_qty'    => $currentQty,
                'notes'          => $notes,
            ]);
        });
    }

    /**
     * حذف جميع الحركات المرتبطة بمستند معين (مثل فاتورة مبيعات تم تعديلها أو حذفها)
     */
    public function deleteMovementsByReference(Model $reference): void
    {
        ItemMovement::where('reference_type', get_class($reference))
            ->where('reference_id', $reference->id)
            ->delete();
    }

    /**
     * إعادة بناء دفتر الصنف (Recalculation) من الصفر
     * يتم استدعاؤها بعد التعديل أو الحذف لضمان صحة الأرصدة السابقة والنهائية
     */
    public function recalculateItemLedger(int $warehouseId, int $itemId): void
    {
        DB::transaction(function () use ($warehouseId, $itemId) {
            // 1. قفل السجل لمنع أي مستخدم آخر من البيع أثناء إعادة الحساب
            WarehouseItem::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'item_id' => $itemId],
                ['current_qty' => 0]
            );

            $warehouseItem = WarehouseItem::where('warehouse_id', $warehouseId)
                ->where('item_id', $itemId)
                ->lockForUpdate()
                ->first();

            // 2. جلب جميع حركات هذا الصنف في هذا المخزن مرتبة زمنياً
            $movements = ItemMovement::where('warehouse_id', $warehouseId)
                ->where('item_id', $itemId)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc') // للترتيب الدقيق في حال تمت حركتان في نفس الثانية
                ->get();

            $runningQty = 0; // الرصيد التراكمي يبدأ من الصفر

            // 3. المرور على الحركات وإعادة حساب الأرصدة
            foreach ($movements as $movement) {
                $previousQty = $runningQty;
                $currentQty = $previousQty + $movement->trx_qty;

                // تحديث السطر فقط إذا كان هناك اختلاف (لتحسين أداء قاعدة البيانات)
                if ($movement->previous_qty != $previousQty || $movement->current_qty != $currentQty) {
                    $movement->update([
                        'previous_qty' => $previousQty,
                        'current_qty'  => $currentQty,
                    ]);
                }

                $runningQty = $currentQty;
            }

            // 4. تحديث الرصيد النهائي للصنف في المخزن ليكون مطابقاً تماماً لنهاية الدفتر
            if ($warehouseItem->current_qty != $runningQty) {
                $warehouseItem->update(['current_qty' => $runningQty]);
            }
        });
    }
}


