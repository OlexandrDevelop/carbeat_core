<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminTariffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'price' => $this->price,
            'currency' => (string) $this->currency,
            'features' => $this->features ?? [],
            'apple_product_id' => $this->apple_product_id,
            'google_product_id' => $this->google_product_id,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
