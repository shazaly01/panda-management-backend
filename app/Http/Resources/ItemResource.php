<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'code'        => $this->code,
            'barcode'     => $this->barcode,
            'type'        => $this->type,
            'type_label'  => $this->type->label(), // استخدام دالة الـ Enum

            'category'    => new CategoryResource($this->whenLoaded('category')),

            // تجميع بيانات الوحدات بشكل مرتب للواجهة
            'units' => [
                'main' => [
                    'id' => $this->unit1_id,
                    'name' => $this->unit1->name ?? '',
                    'price' => (float)$this->price1,
                    'factor' => 1,
                ],
                'medium' => $this->unit2_id ? [
                    'id' => $this->unit2_id,
                    'name' => $this->unit2->name ?? '',
                    'price' => (float)$this->price2,
                    'factor' => (float)$this->factor2,
                ] : null,
                'small' => $this->unit3_id ? [
                    'id' => $this->unit3_id,
                    'name' => $this->unit3->name ?? '',
                    'price' => (float)$this->price3,
                    'factor' => (float)$this->factor3,
                ] : null,
            ],

            'base_cost'   => (float)$this->base_cost,
            'has_expiry'  => (bool)$this->has_expiry,
            'is_active'   => (bool)$this->is_active,
        ];
    }
}
