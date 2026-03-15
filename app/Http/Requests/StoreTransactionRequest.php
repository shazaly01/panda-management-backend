<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TransactionStatus;          // <-- أضف هذا السطر
use Illuminate\Validation\Rules\Enum;     // <-- أضف هذا السطر

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 1. تحديد نوع العملية
        $isTransfer = $this->has('from_warehouse_id');
        $isAdjustment = $this->filled('trx_type') && in_array($this->trx_type, ['adjustment_in', 'adjustment_out']);

        // 2. القواعد الأساسية (مشتركة للكل)
        $rules = [
            'trx_date' => ['required', 'date'],
            'notes'    => ['nullable', 'string'],
            'items'    => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.qty'     => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_factor' => ['nullable', 'numeric'],
            'items.*.production_date' => ['nullable', 'date'],
            'items.*.expiry_date'     => ['nullable', 'date', 'after:items.*.production_date'],

            // تفاصيل الأصناف (لنشاط الطباعة)
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.length'      => ['nullable', 'numeric', 'min:0'],
            'items.*.width'       => ['nullable', 'numeric', 'min:0'],
            'items.*.area'        => ['nullable', 'numeric', 'min:0'],
        ];

        // 3. تخصيص القواعد حسب النوع
        if ($isTransfer) {
            // === تحويل ===
            $rules['from_warehouse_id'] = ['required', 'exists:warehouses,id'];
            $rules['to_warehouse_id']   = ['required', 'exists:warehouses,id', 'different:from_warehouse_id'];
            $rules['partner_id'] = ['nullable'];
            $rules['items.*.price'] = ['nullable'];

        } elseif ($isAdjustment) {
            // === تسوية جردية ===
            $rules['warehouse_id'] = ['required', 'exists:warehouses,id'];
            $rules['trx_type'] = ['required', 'string'];
            $rules['partner_id'] = ['nullable'];
            $rules['items.*.price'] = ['nullable'];

        } else {
            // === بيع / شراء ===
            $rules['warehouse_id'] = ['required', 'exists:warehouses,id'];
            $rules['partner_id']   = ['required', 'exists:partners,id'];
            $rules['status'] = ['nullable', new Enum(TransactionStatus::class)];
            $rules['items.*.price'] = ['required', 'numeric', 'min:0'];
            $rules['treasury_id']  = ['nullable', 'exists:treasuries,id'];

            // --- [تمت الإضافة]: الحقول المالية لضمان مرورها عبر الـ Validator ---
            $rules['paid_amount']     = ['nullable', 'numeric', 'min:0']; // المبلغ المدفوع
            $rules['discount_amount'] = ['nullable', 'numeric', 'min:0']; // قيمة الخصم
            $rules['tax_amount']      = ['nullable', 'numeric', 'min:0']; // قيمة الضريبة
            $rules['total_area']      = ['nullable', 'numeric', 'min:0'];

            // الإضافات الخاصة بنشاط الطباعة
            $rules['walk_in_customer_name'] = ['nullable', 'string', 'max:255'];
            $rules['shipping_destination']  = ['nullable', 'string', 'max:255'];
            $rules['designer_id']           = ['nullable', 'exists:users,id'];
            $rules['design_commission']     = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }
}
