<?php

namespace App\Http\Requests\Api\V1\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class CheckSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'in:apple,google'],
            'receipt_token' => ['required', 'string'],
            'product_id' => ['nullable', 'string', 'max:191'],
        ];
    }
}
