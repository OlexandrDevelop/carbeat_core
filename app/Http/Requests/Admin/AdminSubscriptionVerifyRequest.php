<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminSubscriptionVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'platform' => ['required', 'in:apple,google'],
            'receipt_token' => ['required', 'string'],
            'product_id' => ['nullable', 'string', 'max:191'],
        ];
    }
}
