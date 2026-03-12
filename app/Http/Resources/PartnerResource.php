<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'code'            => $this->code,
            'type'            => $this->type,
            'type_label'      => $this->type->label(),
            'phone'           => $this->phone,
            'email'           => $this->email,
            'tax_number'      => $this->tax_number,
            'address'         => $this->address,
            'current_balance' => (float)$this->current_balance, // تحويل لرقم
            'is_active'       => (bool)$this->is_active,
        ];
    }
}
