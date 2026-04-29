<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminServiceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'translations'     => ['required', 'array'],
            'translations.uk'  => ['required', 'string', 'max:255'],
            'translations.en'  => ['nullable', 'string', 'max:255'],
        ];
    }
}
