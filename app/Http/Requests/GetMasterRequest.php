<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => 'numeric',
            'lng' => 'numeric',
            'zoom' => 'numeric',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:2000|nullable',
            'name' => 'string|nullable',
            'distance' => 'numeric|nullable',
            'service_id' => 'int|nullable',
            'rating' => 'numeric|min:1|max:5|nullable',
            'available' => 'boolean|nullable',
            // Optional viewport bbox (all four required together) — additive, used by the web map only.
            'min_lat' => 'numeric|nullable|required_with:max_lat,min_lng,max_lng',
            'max_lat' => 'numeric|nullable|required_with:min_lat,min_lng,max_lng',
            'min_lng' => 'numeric|nullable|required_with:min_lat,max_lat,max_lng',
            'max_lng' => 'numeric|nullable|required_with:min_lat,max_lat,min_lng',
            // Optional lightweight field set (skips heavy text fields) — additive, used by the web map only.
            'fields' => 'string|nullable|in:light',
        ];
    }
}
