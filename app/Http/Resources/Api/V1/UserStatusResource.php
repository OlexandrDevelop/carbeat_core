<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'is_premium' => (bool) $this->resource['is_premium'],
            'premium_until' => $this->resource['premium_until'],
            'max_photos' => (int) $this->resource['max_photos'],
            'max_description' => (int) $this->resource['max_description'],
            'max_services' => (int) $this->resource['max_services'],
        ];
    }
}


