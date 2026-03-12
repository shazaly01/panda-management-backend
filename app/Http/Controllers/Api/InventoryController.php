<?php

namespace App\Http\Controllers\Api;

use App\Enums\InventoryTransactionType;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Models\InventoryHeader;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;

        // 1. التعديل هنا: استخدام 'inventory' ليتطابق مع الـ Route Model Binding والـ Policy
        $this->authorizeResource(InventoryHeader::class, 'inventory');
    }

    public function index(Request $request)
    {
        $query = InventoryHeader::with(['fromWarehouse', 'toWarehouse', 'createdBy']);

        if ($request->has('warehouse_id')) {
            $wId = $request->warehouse_id;
            $query->where(function($q) use ($wId) {
                $q->where('from_warehouse_id', $wId)
                  ->orWhere('to_warehouse_id', $wId);
            });
        }

        return InventoryResource::collection($query->latest()->paginate(15));
    }

    public function store(StoreInventoryRequest $request)
    {
        $data = $request->validated();

        $headerData = Arr::except($data, ['items']);
        $items = $data['items'];

        $headerData['created_by'] = auth()->id();
        $headerData['status'] = TransactionStatus::CONFIRMED;

        // ملاحظة: تم نقل توليد trx_no داخل السيرفس لضمان أنه DECIMAL(18, 0)

        if ($headerData['trx_type'] === InventoryTransactionType::TRANSFER->value) {
            $transaction = $this->inventoryService->createTransfer($headerData, $items);
        } else {
            $transaction = $this->inventoryService->createAdjustment($headerData, $items);
        }

        return new InventoryResource($transaction->load('details.item'));
    }

    // 2. التعديل هنا: استخدام اسم المتغير $inventory ليتطابق مع المسار
    public function show(InventoryHeader $inventory)
    {
        return new InventoryResource($inventory->load(['details.item', 'details.unit', 'fromWarehouse', 'toWarehouse']));
    }

    // --- الإضافات الجديدة للتكامل مع الـ Vue ---

    /**
     * تحديث حركة مخزنية (تحويل أو تسوية)
     */
    public function update(StoreInventoryRequest $request, InventoryHeader $inventory)
    {
        $data = $request->validated();
        $headerData = Arr::except($data, ['items']);
        $items = $data['items'];

        // الحفاظ على الحالة الأصلية
        $headerData['status'] = $inventory->status;

        $updated = $this->inventoryService->updateInventoryTransaction($inventory, $headerData, $items);

        return new InventoryResource($updated->load('details.item'));
    }

    /**
     * حذف حركة مخزنية وعكس تأثيرها
     */
    public function destroy(InventoryHeader $inventory)
    {
        $this->inventoryService->deleteInventoryTransaction($inventory);

        return response()->json(['message' => 'تم حذف الحركة المخزنية وعكس تأثيرها بنجاح']);
    }
}
