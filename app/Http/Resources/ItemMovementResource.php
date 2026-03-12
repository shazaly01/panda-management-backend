<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'warehouse_name' => $this->warehouse->name ?? null,
            'item_name'      => $this->item->name ?? null,
            'transaction_no' => $this->transaction_no, // DECIMAL(18, 0)
            'trx_type'       => $this->trx_type->value,
            'trx_label'      => $this->trx_type->label(), // النص العربي للنوع
            'previous_qty'   => number_format($this->previous_qty, 4, '.', ''),
            'trx_qty'        => number_format($this->trx_qty, 4, '.', ''),
            'current_qty'    => number_format($this->current_qty, 4, '.', ''),
            'notes'          => $this->notes,
            'date'           => $this->created_at->format('Y-m-d H:i:s'),

            // رابط للمستند الأصلي (فاتورة مبيعات، مشتريات، إلخ)
            'reference_id'   => $this->reference_id,
            'reference_type' => $this->reference_type,
        ];
    }
}
