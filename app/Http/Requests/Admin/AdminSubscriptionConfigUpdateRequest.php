<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminSubscriptionConfigUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trial_enabled' => ['required', 'boolean'],
            'trial_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}


