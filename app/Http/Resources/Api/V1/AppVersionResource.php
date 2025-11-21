<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AppVersionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'platform' => $this['platform'],
            'min_supported_build' => (int) $this['min_supported_build'],
            'recommended_build' => (int) $this['recommended_build'],
            'store_url' => (string) ($this['store_url'] ?? ''),
            'message' => (string) ($this['message'] ?? ''),
            // Helpful server-side evaluation (optional)
            'mandatory' => isset($this['current_build'])
                ? ((int) $this['current_build'] < (int) $this['min_supported_build'])
                : null,
        ];
    }
}












