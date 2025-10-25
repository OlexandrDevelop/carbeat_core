<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminTariffSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:8'],
            'features' => ['nullable', 'array'],
            'apple_product_id' => ['nullable', 'string', 'max:191'],
            'google_product_id' => ['nullable', 'string', 'max:191'],
        ];
    }
}
