<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminServiceMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'service_ids' => 'required|array|min:2',
            'service_ids.*' => 'integer|distinct|exists:services,id',
            'primary_id' => [
                'required',
                'integer',
                Rule::in($this->input('service_ids', [])),
            ],
        ];
    }
}
