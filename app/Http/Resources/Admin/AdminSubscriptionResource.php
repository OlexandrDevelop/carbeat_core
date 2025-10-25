<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'user_phone' => $this->user?->phone,
            'platform' => (string) $this->platform,
            'product_id' => $this->product_id,
            'external_id' => $this->external_id,
            'status' => (string) $this->status,
            'expires_at' => optional($this->expires_at)->toIso8601String(),
            'last_verified_at' => optional($this->last_verified_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
