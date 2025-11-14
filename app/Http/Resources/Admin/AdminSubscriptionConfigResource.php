<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSubscriptionConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'trial_enabled' => (bool) ($this['trial_enabled'] ?? false),
            'trial_days' => (int) ($this['trial_days'] ?? 30),
        ];
    }
}


