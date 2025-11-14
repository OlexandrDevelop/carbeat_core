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
            $masterId = (int) $this->route('id');
            $master = \App\Models\Master::find($masterId);
            $isPremium = false;
            if ($master) {
                $isPremium = (bool) $master->is_premium;
                if ($master->premium_until && $master->premium_until->isFuture()) {
                    $isPremium = true;
                }
            }
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
        $masterId = (int) $this->route('id');
        $master = \App\Models\Master::find($masterId);
        $isPremium = false;
        if ($master) {
            $isPremium = (bool) $master->is_premium;
            if ($master->premium_until && $master->premium_until->isFuture()) {
                $isPremium = true;
            }
        }
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
}
