<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminSubscriptionCreateRequest extends FormRequest
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
            'product_id' => ['nullable', 'string', 'max:191'],
            'external_id' => ['required', 'string', 'max:191'],
            'status' => ['required', 'in:active,expired,cancelled'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
