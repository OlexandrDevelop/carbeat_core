<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminSeoContentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'intro' => ['nullable', 'string'],
            'sections' => ['nullable', 'array'],
            'sections.*.heading' => ['required_with:sections', 'string'],
            'sections.*.body' => ['required_with:sections', 'string'],
            'faq' => ['nullable', 'array'],
            'faq.*.q' => ['required_with:faq', 'string'],
            'faq.*.a' => ['required_with:faq', 'string'],
        ];
    }
}
