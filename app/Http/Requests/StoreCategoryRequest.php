<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
{
    // البحث عن المعرف بأكثر من طريقة لضمان التوافق
    $category = $this->route('category');
    $id = is_object($category) ? $category->id : $category;

    // إذا لم يجده في المسار، قد يكون مرسلاً في جسم الطلب (اختياري)
    $id = $id ?? $this->input('id');

    return [
        'parent_id' => ['nullable', 'exists:categories,id'],
        'name'      => ['required', 'string', 'max:255'],
        // نجعل الكود nullable ونحرص على تجاهل السجل الحالي عند التعديل
        'code'      => ['nullable', Rule::unique('categories', 'code')->ignore($id)],
        'is_active' => ['boolean'],
    ];
}
}
