<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminMaintenanceRegenerateThumbsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) auth()->check();
    }

    public function rules(): array
    {
        return [
            'reset' => ['sometimes', 'boolean'],
        ];
    }
}


