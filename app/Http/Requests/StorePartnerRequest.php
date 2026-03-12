<?php

namespace App\Http\Requests;

use App\Enums\PartnerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('partner') ? $this->route('partner')->id : null;

        return [
            'name'       => ['required', 'string', 'max:255'],
            'code'       => ['nullable', Rule::unique('partners', 'code')->ignore($id)],
            // التحقق من أن النوع موجود داخل الـ Enum
            'type'       => ['required', Rule::enum(PartnerType::class)],
            'phone'      => ['nullable', 'string', 'max:50'],
            'email'      => ['nullable', 'email', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'address'    => ['nullable', 'string'],
            'is_active'  => ['boolean'],
        ];
    }
}
