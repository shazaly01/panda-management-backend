<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'code'      => $this->code, // يتم إرجاعه كنص لأننا حفظناه كـ DECIMAL
            'is_active' => (bool)$this->is_active,
        ];
    }
}
