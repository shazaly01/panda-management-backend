<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'user'         => $this->user->name ?? '',
            'treasury'     => $this->treasury->name ?? '',
            'is_open'      => (bool)$this->is_open,

            'started_at'   => $this->started_at,
            'ended_at'     => $this->ended_at,

            'start_cash'      => (float)$this->start_cash,
            'end_cash_system' => (float)$this->end_cash_system,
            'end_cash_actual' => (float)$this->end_cash_actual,
            'variance'        => (float)$this->variance,
        ];
    }
}
