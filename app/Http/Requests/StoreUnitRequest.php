<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // للحصول على ID الوحدة في حالة التعديل لتجاهله في فحص الـ Unique
        $id = $this->route('unit') ? $this->route('unit')->id : null;

        return [
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['nullable', Rule::unique('units', 'code')->ignore($id)],
            'is_active' => ['boolean'],
        ];
    }
}
