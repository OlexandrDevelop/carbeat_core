<?php

namespace App\Http\Requests;

use App\Http\Resources\Api\V1\ErrorResponse;
use App\Models\Master;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddMasterGalleryPhotosRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', new \App\Rules\Base64Image],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalize to array
        if ($this->has('photos') && ! is_array($this->input('photos'))) {
            $this->merge(['photos' => (array) $this->input('photos')]);
        }
    }

    protected function passedValidation(): void
    {
        $masterId = (int) $this->route('id');
        $master = Master::findOrFail($masterId);

        $currentCount = $master->gallery()->count();
        $isPremium = (bool) $master->is_premium;
        if ($master->premium_until && $master->premium_until->isFuture()) {
            $isPremium = true;
        }

        $limit = $isPremium
            ? (int) config('limits.max_photos_premium')
            : (int) config('limits.max_photos_free');

        $incoming = count($this->input('photos', []));
        if ($currentCount >= $limit || ($currentCount + $incoming) > $limit) {
            $resource = new ErrorResponse([
                'error' => 'photos_limit',
                'message' => 'Досягнуто максимальну кількість фото',
                'limit' => $limit,
                'upgrade_required' => true,
            ]);
            throw new HttpResponseException($resource->response()->setStatusCode(403));
        }
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors(),
        ], 422));
    }
}


