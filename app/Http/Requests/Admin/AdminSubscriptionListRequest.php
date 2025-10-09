<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminSubscriptionListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['nullable', 'in:apple,google'],
            'status' => ['nullable', 'in:active,expired,cancelled'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'phone' => ['nullable', 'string'],
            'product_id' => ['nullable', 'string'],
            'expires_from' => ['nullable', 'date'],
            'expires_to' => ['nullable', 'date', 'after_or_equal:expires_from'],
            'sort' => ['nullable', 'in:id,user_id,platform,status,expires_at,created_at,last_verified_at'],
            'dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
