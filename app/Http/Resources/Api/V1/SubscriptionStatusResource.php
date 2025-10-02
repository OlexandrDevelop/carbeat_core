<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'active' => (bool) $this->active,
            'platform' => $this->platform,
            'expires_at' => $this->expires_at ? $this->expires_at->toIso8601String() : null,
            'product_id' => $this->product_id,
        ];
    }
}
