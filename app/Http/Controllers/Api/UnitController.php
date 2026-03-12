<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    protected UnitService $unitService;

    public function __construct(UnitService $unitService)
    {
        $this->unitService = $unitService;
        // تفعيل السياسات (Policies)
        $this->authorizeResource(Unit::class, 'unit');
    }

    /**
     * عرض كل الوحدات
     */
    public function index(Request $request)
    {
        // إذا طلب المستخدم ?all=1 نجلب الكل، وإلا نجلب النشط فقط
        $activeOnly = !$request->has('all');
        $units = $this->unitService->getAllUnits($activeOnly);

        return UnitResource::collection($units);
    }

    /**
     * إنشاء وحدة جديدة
     */
    public function store(StoreUnitRequest $request)
    {
        $unit = $this->unitService->createUnit($request->validated());

        return new UnitResource($unit);
    }

    /**
     * عرض وحدة محددة
     */
    public function show(Unit $unit)
    {
        return new UnitResource($unit);
    }

    /**
     * تحديث وحدة
     */
    public function update(StoreUnitRequest $request, Unit $unit)
    {
        $updatedUnit = $this->unitService->updateUnit($unit, $request->validated());

        return new UnitResource($updatedUnit);
    }

    /**
     * حذف وحدة
     */
    public function destroy(Unit $unit): JsonResponse
    {
        $this->unitService->deleteUnit($unit);

        return response()->json(['message' => 'تم حذف الوحدة بنجاح']);
    }
}
