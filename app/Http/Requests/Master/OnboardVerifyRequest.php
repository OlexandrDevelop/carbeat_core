<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class OnboardVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^\+(?:\d{1,3})?\d{6,14}$/'],
            'code' => ['required', 'digits:4'],
            // Only required when the server-side lookup determines this is a
            // brand-new registration (no existing Master for the phone) —
            // enforced in the controller, not here, since the branch isn't
            // known until after the phone is looked up.
            'name' => ['sometimes', 'string', 'max:255'],
            'service_id' => ['sometimes', 'integer'],
            'latitude' => ['sometimes', 'numeric'],
            'longitude' => ['sometimes', 'numeric'],
        ];
    }
}
