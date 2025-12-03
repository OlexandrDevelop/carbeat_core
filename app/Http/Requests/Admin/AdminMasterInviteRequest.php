<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminMasterInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'master_ids' => ['required', 'array', 'min:1'],
            'master_ids.*' => ['integer', 'exists:masters,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}

