<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResponse extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'error' => $this->resource['error'] ?? 'error',
            'message' => $this->resource['message'] ?? '',
            'limit' => $this->resource['limit'] ?? null,
            'upgrade_required' => $this->resource['upgrade_required'] ?? false,
        ];
    }
}


