<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminPaymentSettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'apple' => ['nullable', 'array'],
            'apple.issuer_id' => ['nullable', 'string'],
            'apple.key_id' => ['nullable', 'string'],
            'apple.private_key' => ['nullable', 'string'],
            'apple.bundle_id' => ['nullable', 'string'],
            'apple.use_sandbox' => ['nullable', 'boolean'],

            'google' => ['nullable', 'array'],
            'google.service_account_json' => ['nullable', 'string'],
            'google.package_name' => ['nullable', 'string'],
        ];
    }
}
