<?php

namespace App\Http\Requests;

use App\Enums\TreasuryTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreasuryTransactionRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرحاً له بعمل هذا الطلب.
     * (التحقق من الصلاحيات تم في الـ Policy، لذا نتركه true هنا)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق.
     */
    public function rules(): array
    {
        return [
            'trx_date' => ['required', 'date'],
            'type' => ['required', 'string', Rule::enum(TreasuryTransactionType::class)],
            'treasury_id' => ['required', 'integer', Rule::exists('treasuries', 'id')],
            // الشريك (عميل/مورد) اختياري لأن السند قد يكون لمصروفات أخرى
            'partner_id' => ['nullable', 'integer', Rule::exists('partners', 'id')],
            // المبلغ يجب أن يكون رقماً وأكبر من الصفر
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * رسائل الخطأ المخصصة (اختياري).
     */
    public function messages(): array
    {
        return [
            'amount.gt' => 'يجب أن يكون المبلغ أكبر من الصفر.',
            'treasury_id.exists' => 'الخزينة المحددة غير موجودة.',
            'partner_id.exists' => 'العميل أو المورد المحدد غير موجود.',
        ];
    }
}
