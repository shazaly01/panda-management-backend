<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Services\InventoryService;
use Illuminate\Support\Arr;

class TransfersController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function store(StoreTransactionRequest $request)
    {
        $data = $request->validated();

        $headerData = Arr::except($data, ['items']);
        $items = $data['items'];

        $headerData['created_by'] = auth()->id();
        $headerData['trx_no'] = 'TRF-' . time(); // توليد رقم حركة تحويل

        // استدعاء دالة التحويل الموجودة في InventoryService
        $transfer = $this->inventoryService->createTransfer($headerData, $items);

        return response()->json([
            'data' => $transfer->load('details'),
            'message' => 'Transfer created successfully'
        ], 201);
    }
}
