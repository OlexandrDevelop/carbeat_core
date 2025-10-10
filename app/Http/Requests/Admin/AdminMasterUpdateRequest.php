<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminMasterUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // Adjust Gate/Policy as needed
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'address' => 'sometimes|nullable|string|max:255',
            'age' => 'sometimes|integer|min:0',
            'phone' => 'sometimes|nullable|string|max:50',
            'available' => 'sometimes|boolean',
            'service_id' => 'sometimes|integer|exists:services,id',
            'city_id' => 'sometimes|nullable|integer|exists:cities,id',
            'tariff_id' => 'sometimes|integer|exists:tariffs,id',
            'slug' => 'sometimes|string|max:255',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'rating' => 'sometimes|numeric|min:0|max:5',
            'reviews_count' => 'sometimes|integer|min:0',
            'service_ids' => 'sometimes|array',
            'service_ids.*' => 'integer|exists:services,id',
        ];
    }
}
