<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAppConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'android' => [
                'min_supported_build' => (int) ($this['android']['min_supported_build'] ?? 1),
                'recommended_build' => (int) ($this['android']['recommended_build'] ?? 1),
                'message' => (string) ($this['android']['message'] ?? ''),
                'store_url' => (string) ($this['android']['store_url'] ?? ''),
            ],
            'ios' => [
                'min_supported_build' => (int) ($this['ios']['min_supported_build'] ?? 1),
                'recommended_build' => (int) ($this['ios']['recommended_build'] ?? 1),
                'message' => (string) ($this['ios']['message'] ?? ''),
                'store_url' => (string) ($this['ios']['store_url'] ?? ''),
            ],
        ];
    }
}


