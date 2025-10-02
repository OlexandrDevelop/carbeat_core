<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminSubscriptionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'in:active,expired,cancelled'],
            'product_id' => ['sometimes', 'nullable', 'string', 'max:191'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
