<?php

namespace App\Http\Requests;

use App\Enums\AppBrand;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * @property mixed $phone
 * @property mixed $sms_code
 * @property mixed $name
 * @property mixed $car_make
 * @property mixed $car_model
 * @property mixed $car_year
 * @property mixed $description
 * @property mixed $service_id
 * @property mixed $city
 * @property mixed $latitude
 * @property mixed $longitude
 */
class SubmitRepairRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors(),
        ], 422));
    }

    public function rules(): array
    {
        $brand = config('app.client') instanceof AppBrand
            ? config('app.client')
            : AppBrand::CARBEAT;

        return [
            'phone' => [
                'required',
                'regex:/^\+(?:\d{1,3})?\d{6,14}$/',
            ],
            'sms_code' => 'required|numeric',
            'name' => 'required|string|max:255',
            'car_make' => 'required|string|max:255',
            'car_model' => 'nullable|string|max:255',
            'car_year' => 'nullable|digits:4|integer|min:1970|max:'.(now()->year + 1),
            'description' => 'required|string|max:2000',
            'city' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where('app', $brand->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Wrong phone number format. Use international format. For example: +380501234567',
        ];
    }
}
