<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\ItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    protected ItemService $itemService;

    public function __construct(ItemService $itemService)
    {
        $this->itemService = $itemService;
        $this->authorizeResource(Item::class, 'item');
    }

    public function index(Request $request)
    {
        // تمرير مصفوفة الفلاتر من الرابط للخدمة
        $filters = $request->only(['search', 'category_id', 'is_active']);

        $items = $this->itemService->getItems($filters);

        return ItemResource::collection($items);
    }

    public function store(StoreItemRequest $request)
    {
        $item = $this->itemService->createItem($request->validated());
        return new ItemResource($item);
    }

    public function show(Item $item)
    {
        // تحميل العلاقات الضرورية للعرض
        $item->load(['category', 'unit1', 'unit2', 'unit3']);
        return new ItemResource($item);
    }

    public function update(StoreItemRequest $request, Item $item)
    {
        $updatedItem = $this->itemService->updateItem($item, $request->validated());
        return new ItemResource($updatedItem);
    }

    public function destroy(Item $item): JsonResponse
    {
        $this->itemService->deleteItem($item);
        return response()->json(['message' => 'تم حذف الصنف بنجاح']);
    }
}
