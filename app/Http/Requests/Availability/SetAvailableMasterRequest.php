<?php

namespace App\Http\Requests\Availability;

use App\Models\Master;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SetAvailableMasterRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors(),
        ], 422));
    }

    public function rules(): array
    {
        return [
            'start_time' => ['nullable', 'date'],
            'duration' => ['required', 'integer', 'min:1', 'max:1440'], // minutes
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $id = $this->route('id');
            if (! Master::where('id', $id)->exists()) {
                $validator->errors()->add('id', 'Master not found.');
            }
        });
    }
}
