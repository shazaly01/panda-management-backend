<?php

namespace App\Http\Requests;

use App\Enums\TreasuryTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTreasuryTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trx_date' => ['sometimes', 'required', 'date'],
            'type' => ['sometimes', 'required', 'string', Rule::enum(TreasuryTransactionType::class)],
            'treasury_id' => ['sometimes', 'required', 'integer', Rule::exists('treasuries', 'id')],
            'partner_id' => ['nullable', 'integer', Rule::exists('partners', 'id')],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
