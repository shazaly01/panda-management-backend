<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Services\InventoryService;
use Illuminate\Support\Arr;

class AdjustmentsController extends Controller
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
        $headerData['trx_no'] = 'ADJ-' . time();

        // استدعاء دالة التسوية الموجودة في InventoryService
        $adjustment = $this->inventoryService->createAdjustment($headerData, $items);

        return response()->json([
            'data' => $adjustment->load('details'),
            'message' => 'Inventory adjustment created successfully'
        ], 201);
    }
}
