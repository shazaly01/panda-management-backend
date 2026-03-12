<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartnerRequest;
use App\Http\Resources\PartnerResource;
use App\Models\Partner;
use App\Services\PartnerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    protected PartnerService $partnerService;

    public function __construct(PartnerService $partnerService)
    {
        $this->partnerService = $partnerService;
        $this->authorizeResource(Partner::class, 'partner');
    }

    public function index(Request $request)
    {
        // تمرير الفلاتر (بحث، نوع) للخدمة
        $filters = $request->only(['search', 'type']);

        $partners = $this->partnerService->getPartners($filters);

        return PartnerResource::collection($partners);
    }

    public function store(StorePartnerRequest $request)
    {
        $partner = $this->partnerService->createPartner($request->validated());
        return new PartnerResource($partner);
    }

    public function show(Partner $partner)
    {
        return new PartnerResource($partner);
    }

    public function update(StorePartnerRequest $request, Partner $partner)
    {
        $updatedPartner = $this->partnerService->updatePartner($partner, $request->validated());
        return new PartnerResource($updatedPartner);
    }

    public function destroy(Partner $partner): JsonResponse
    {
        try {
            $this->partnerService->deletePartner($partner);
            return response()->json(['message' => 'تم حذف الشريك بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
