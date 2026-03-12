<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreasuryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('treasury') ? $this->route('treasury')->id : null;

        return [
            'name'            => ['required', 'string', 'max:255'],
            'code'            => ['nullable', Rule::unique('treasuries', 'code')->ignore($id)],
            'is_bank'         => ['boolean'],
            // رقم الحساب مطلوب فقط إذا كانت الخزينة بنكاً
            'bank_account_no' => ['nullable', 'string', 'required_if:is_bank,true'],
            'is_active'       => ['boolean'],
        ];
    }
}
