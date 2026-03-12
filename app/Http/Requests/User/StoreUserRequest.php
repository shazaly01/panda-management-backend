<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|string|email|max:255',
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name,guard_name,api',

            // --- الحقول الجديدة ---
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'treasury_id' => 'nullable|integer|exists:treasuries,id',
            // بما أن البنوك موجودة في جدول treasuries، نتحقق من نفس الجدول
            'bank_id' => 'nullable|integer|exists:treasuries,id',
        ];
    }
}
