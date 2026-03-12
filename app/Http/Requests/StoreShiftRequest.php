<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // نتحقق من المسار لمعرفة هل هو فتح أم إغلاق
        // نفترض أن هذا الريكوست يستخدم للعمليتين

        return [
            'treasury_id' => ['required', 'exists:treasuries,id'],

            // عند الفتح
            'start_cash'  => ['nullable', 'numeric', 'min:0'],

            // عند الإغلاق (تحديث)
            'end_cash_actual' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
