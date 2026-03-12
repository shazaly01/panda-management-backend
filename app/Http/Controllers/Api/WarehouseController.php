<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    protected WarehouseService $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
        $this->authorizeResource(Warehouse::class, 'warehouse');
    }

    public function index(Request $request)
    {
        $activeOnly = !$request->has('all');
        $warehouses = $this->warehouseService->getAllWarehouses($activeOnly);

        return WarehouseResource::collection($warehouses);
    }

    public function store(StoreWarehouseRequest $request)
    {
        $warehouse = $this->warehouseService->createWarehouse($request->validated());
        return new WarehouseResource($warehouse);
    }

    public function show(Warehouse $warehouse)
    {
        return new WarehouseResource($warehouse);
    }

    public function update(StoreWarehouseRequest $request, Warehouse $warehouse)
    {
        $updatedWarehouse = $this->warehouseService->updateWarehouse($warehouse, $request->validated());
        return new WarehouseResource($updatedWarehouse);
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        // قد نحتاج لمنع الحذف إذا كان المخزن يحتوي بضاعة (يتم معالجته في السرفيس أو الموديل)
        $warehouse->delete();
        return response()->json(['message' => 'تم حذف المخزن بنجاح']);
    }

    /**
     * إسناد مخازن لمستخدم معين
     * Route: POST /api/warehouses/assign-user
     */
    public function assignToUser(Request $request): JsonResponse
    {
        // هذه العملية تتطلب صلاحية خاصة (مثلاً user.update أو صلاحية خاصة)
        $this->authorize('update', new \App\Models\User());

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'warehouses' => 'required|array',
            'warehouses.*' => 'exists:warehouses,id',
            'default_warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $this->warehouseService->assignWarehousesToUser(
            $request->user_id,
            $request->warehouses,
            $request->default_warehouse_id
        );

        return response()->json(['message' => 'تم تحديث صلاحيات المخازن للمستخدم بنجاح']);
    }
}
