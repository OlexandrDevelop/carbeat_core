<?php

namespace App\Http\Requests;

use App\Rules\Base64Image;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateMasterRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        if ($errors->has('description')) {
            $masterId = $this->determineMasterId();
            $master = \App\Models\Master::find($masterId);
            $isPremium = $master?->is_premium ?? false;
            $limit = $isPremium
                ? (int) config('limits.max_description_premium')
                : (int) config('limits.max_description_free');
            $resource = new \App\Http\Resources\Api\V1\ErrorResponse([
                'error' => 'description_limit',
                'message' => 'Ваш опис перевищує дозволену довжину',
                'limit' => $limit,
                'upgrade_required' => !$isPremium,
            ]);
            throw new HttpResponseException($resource->response()->setStatusCode(422));
        }
        throw new HttpResponseException(response()->json([
            'errors' => $errors,
        ], 422));
    }

    public function rules(): array
    {
        $masterId = $this->determineMasterId();
        $master = $masterId ? \App\Models\Master::find($masterId) : null;
        $isPremium = $master?->is_premium ?? false;
        $maxLength = $isPremium
            ? (int) config('limits.max_description_premium')
            : (int) config('limits.max_description_free');

        return [
            'contact_phone' => ['nullable', 'regex:/^\+?380\d{9}$/'],
            'description' => ['nullable', 'string', "max:$maxLength"],
            'service_id' => ['nullable', 'exists:services,id'],
            'age' => ['nullable', 'integer', 'between:18,99'],
            'photo' => ['nullable', new Base64Image],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    private function determineMasterId(): int
    {
        $routeId = $this->route('id');
        if ($routeId) {
            return (int) $routeId;
        }

        $user = $this->user();
        if ($user && $user->master) {
            return (int) $user->master->id;
        }

        return 0;
    }
}
