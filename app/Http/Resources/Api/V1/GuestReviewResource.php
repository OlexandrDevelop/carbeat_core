<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property int|null $rating
 * @property string $review
 * @property string|null $guest_name
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class GuestReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'rating' => $this->rating !== null ? (int) $this->rating : null,
            'review' => (string) $this->review,
            'user' => [
                'name' => (string) ($this->guest_name ?? ''),
            ],
            'created_at' => optional($this->created_at)->toISOString(),
            'replies' => [],
        ];
    }
}
