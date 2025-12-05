<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminAppConfigUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'android' => ['sometimes', 'array'],
            'android.min_supported_build' => ['nullable', 'integer', 'min:1'],
            'android.recommended_build' => ['nullable', 'integer', 'min:1'],
            'android.message' => ['nullable', 'string', 'max:500'],
            'android.store_url' => ['nullable', 'string', 'max:500'],

            'ios' => ['sometimes', 'array'],
            'ios.min_supported_build' => ['nullable', 'integer', 'min:1'],
            'ios.recommended_build' => ['nullable', 'integer', 'min:1'],
            'ios.message' => ['nullable', 'string', 'max:500'],
            'ios.store_url' => ['nullable', 'string', 'max:500'],
        ];
    }
}


