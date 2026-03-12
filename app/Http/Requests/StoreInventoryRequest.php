<?php

namespace App\Http\Requests;

use App\Enums\InventoryTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trx_date' => ['required', 'date'],
            'trx_type' => ['required', Rule::enum(InventoryTransactionType::class)],
            'notes'    => ['nullable', 'string'],

            // المخزن المصدر (مطلوب دائماً إلا في حالة التسوية بالزيادة قد يكون هو الهدف)
            'from_warehouse_id' => ['nullable', 'exists:warehouses,id'],

            // المخزن المستقبل (مطلوب في حالة التحويل)
            'to_warehouse_id'   => [
                'nullable',
                'exists:warehouses,id',
                'different:from_warehouse_id',
                // إجباري إذا كان النوع تحويل
                'required_if:trx_type,' . InventoryTransactionType::TRANSFER->value
            ],

            // الأصناف
            'items'             => ['required', 'array', 'min:1'],
            'items.*.item_id'   => ['required', 'exists:items,id'],
            'items.*.unit_id'   => ['required', 'exists:units,id'],
            'items.*.qty'       => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_factor' => ['required', 'numeric', 'min:0'],
        ];
    }
}
