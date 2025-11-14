<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateMasterServicesRequest extends FormRequest
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
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function passedValidation(): void
    {
        $masterId = (int) $this->route('id');
        $master = \App\Models\Master::findOrFail($masterId);
        $isPremium = (bool) $master->is_premium;
        if ($master->premium_until && $master->premium_until->isFuture()) {
            $isPremium = true;
        }

        $limit = $isPremium
            ? (int) config('limits.max_services_premium')
            : (int) config('limits.max_services_free');

        $serviceIds = collect($this->input('service_ids', []))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $count = $serviceIds->count();
        if ($count > $limit) {
            $resource = new \App\Http\Resources\Api\V1\ErrorResponse([
                'error' => 'services_limit',
                'message' => 'Досягнуто максимальну кількість послуг',
                'limit' => $limit,
                'upgrade_required' => !$isPremium,
            ]);
            throw new \Illuminate\Http\Exceptions\HttpResponseException($resource->response()->setStatusCode(403));
        }
    }
}
