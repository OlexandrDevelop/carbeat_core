<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'master_id' => $this->master_id,
            'client_id' => $this->client_id,
            'start_time' => optional($this->start_time)->toIso8601String(),
            'end_time' => optional($this->end_time)->toIso8601String(),
            'status' => $this->status,
            'note' => $this->note,
        ];
    }
}
