<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTreasuryRequest;
use App\Http\Resources\TreasuryResource;
use App\Models\Treasury;
use App\Services\TreasuryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TreasuryController extends Controller
{
    protected TreasuryService $treasuryService;

    public function __construct(TreasuryService $treasuryService)
    {
        $this->treasuryService = $treasuryService;
        $this->authorizeResource(Treasury::class, 'treasury');
    }

    public function index(Request $request)
    {
        $activeOnly = !$request->has('all');
        $treasuries = $this->treasuryService->getAllTreasuries($activeOnly);

        return TreasuryResource::collection($treasuries);
    }

    public function store(StoreTreasuryRequest $request)
    {
        $treasury = $this->treasuryService->createTreasury($request->validated());
        return new TreasuryResource($treasury);
    }

    public function show(Treasury $treasury)
    {
        return new TreasuryResource($treasury);
    }

    public function update(StoreTreasuryRequest $request, Treasury $treasury)
    {
        $updatedTreasury = $this->treasuryService->updateTreasury($treasury, $request->validated());
        return new TreasuryResource($updatedTreasury);
    }

    public function destroy(Treasury $treasury): JsonResponse
    {
        $treasury->delete();
        return response()->json(['message' => 'تم حذف الخزينة بنجاح']);
    }

    /**
     * إسناد خزائن لمستخدم (للكاشير)
     * Route: POST /api/treasuries/assign-user
     */
    public function assignToUser(Request $request): JsonResponse
    {
        $this->authorize('update', new \App\Models\User());

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'data' => 'required|array', // مصفوفة فيها الخزينة والصلاحيات
            'data.*.treasury_id' => 'required|exists:treasuries,id',
            'data.*.can_view_balance' => 'boolean',
            'data.*.is_default' => 'boolean',
        ]);

        $this->treasuryService->assignTreasuriesToUser($request->user_id, $request->data);

        return response()->json(['message' => 'تم تحديث صلاحيات الخزائن للمستخدم بنجاح']);
    }
}
