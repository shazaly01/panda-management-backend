<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\ItemType;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحيات تدار في السياسات (Policies)
    }

    public function rules(): array
    {
        // التعامل مع التحديث (استثناء الـ ID الحالي من فحص التكرار)
        $id = $this->route('item') ? $this->route('item')->id : null;

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['nullable', Rule::unique('items', 'code')->ignore($id)],
            'barcode'     => ['nullable', 'string', 'max:100', Rule::unique('items', 'barcode')->ignore($id)],
            'type'        => ['required', Rule::enum(ItemType::class)],

            // الوحدة الأساسية
            'unit1_id'    => ['required', 'exists:units,id'],
            'price1'      => ['required', 'numeric', 'min:0'],

            // الوحدة الثانية (اختيارية لكن بشروط)
            'unit2_id'    => ['nullable', 'exists:units,id', 'different:unit1_id'],
            'factor2' => ['nullable', 'required_with:unit2_id', 'numeric', 'gt:1'], // يجب أن يكون أكبر من 1
            'price2'      => ['nullable', 'numeric', 'min:0'],

            // الوحدة الثالثة
            'unit3_id'    => ['nullable', 'exists:units,id', 'different:unit1_id', 'different:unit2_id'],
            'factor3' => ['nullable', 'required_with:unit3_id', 'numeric', 'gt:factor2'], // يجب أن يكون أكبر من معامل الوحدة الثانية
            'price3'      => ['nullable', 'numeric', 'min:0'],

            'base_cost'   => ['nullable', 'numeric', 'min:0'],
            'has_expiry'  => ['boolean'],
            'is_active'   => ['boolean'],
        ];
    }
}
