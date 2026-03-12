<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'username' => $this->username,
            'email' => $this->email,

            // --- الحقول الجديدة المرسلة للواجهة ---
            'warehouse_id' => $this->warehouse_id,
            'treasury_id' => $this->treasury_id,
            'bank_id' => $this->bank_id,

            'created_at' => $this->created_at->toDateTimeString(),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
        ];
    }
}
