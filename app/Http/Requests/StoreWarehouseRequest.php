<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('warehouse') ? $this->route('warehouse')->id : null;

        return [
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['nullable', Rule::unique('warehouses', 'code')->ignore($id)],
            'location'    => ['nullable', 'string', 'max:255'],
            'keeper_name' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['boolean'],
        ];
    }
}
