<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemMovement;
use App\Http\Resources\ItemMovementResource;
use Illuminate\Http\Request;

class ItemMovementController extends Controller
{
    /**
     * عرض سجل حركات الأصناف مع إمكانية الفلترة
     */
    public function index(Request $request)
    {
        $query = ItemMovement::with(['warehouse', 'item', 'reference'])
            ->latest('id'); // الترتيب من الأحدث للأقدم

        // فلترة حسب المخزن
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // فلترة حسب الصنف
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        // فلترة حسب نوع الحركة (مبيعات، مشتريات...)
        if ($request->filled('trx_type')) {
            $query->where('trx_type', $request->trx_type);
        }

        $movements = $query->paginate($request->get('per_page', 20));

        return ItemMovementResource::collection($movements);
    }
}
