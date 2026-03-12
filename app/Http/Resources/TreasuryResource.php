<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreasuryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'code'            => $this->code,
            'is_bank'         => (bool)$this->is_bank,
            'bank_account_no' => $this->bank_account_no,
            'current_balance' => (float)$this->current_balance,
            'is_active'       => (bool)$this->is_active,
        ];
    }
}
