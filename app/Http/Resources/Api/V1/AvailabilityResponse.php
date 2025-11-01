<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class AvailabilityResponse extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array|JsonSerializable|Arrayable
    {
        // Expecting $this->resource to be an associative array
        return [
            'message' => (string) ($this->resource['message'] ?? ''),
            'master_id' => isset($this->resource['master_id']) ? (int) $this->resource['master_id'] : null,
            'available' => isset($this->resource['available']) ? (bool) $this->resource['available'] : null,
        ];
    }
}


