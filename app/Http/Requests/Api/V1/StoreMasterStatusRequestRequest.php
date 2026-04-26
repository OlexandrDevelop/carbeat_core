<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterStatusRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'master_id' => ['required', 'integer', 'exists:masters,id'],
            'driver_user_id' => ['nullable', 'integer'],
            'guest_device_id' => ['nullable', 'string', 'max:128'],
            'guest_push_token' => ['nullable', 'string', 'max:512'],
            'guest_platform' => ['nullable', 'string', 'in:android,ios,web,macos'],
        ];
    }
}
