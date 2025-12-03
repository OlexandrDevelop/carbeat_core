<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * MasterResource Class
 *
 * This class extends the JsonResource class provided by Laravel.
 * It is used to transform your resource into an array that can be returned as a JSON response.
 *
 * @property mixed $reviews
 * @property int $id
 * @property string $name
 * @property float $latitude
 * @property float $longitude
 * @property string $description
 * @property string $address
 * @property int $age
 * @property string $phone
 * @property mixed $services
 * @property string $photo
 * @property float $distance
 * @property int $main_service_id
 * @property bool $approved
 * @property int $reviews_count
 * @property float $rating
 * @property int $service_id
 * @property int $tariff_id
 * @property string $slug
 * @property string|null $tariff
 */
class MasterResource extends JsonResource
{
    protected array $availabilityMap;

    public function __construct($resource, array $availabilityMap = [])
    {
        parent::__construct($resource);
        $this->availabilityMap = $availabilityMap;
    }

    /**
     * Transform the resource into an array.
     *
     * This method is used to transform the `Master` model into an array that can be returned as a JSON response.
     * It calculates the average rating of the master based on the reviews and returns an array containing the master's details.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'description' => $this->description,
            'address' => $this->getFormattedAddress($this->address),
            'age' => (int) $this->age,
            'phone' => $this->phone,
            'reviews_count' => (int) $this->reviews_count,
            'rating' => (float) round($this->rating, 1),
            'main_photo' => (string) 'storage/'.$this->photo,
            'main_thumb_url' => $this->main_thumb_url ? (string) ('storage/'.$this->main_thumb_url) : null,
            'distance' => (float) round($this->distance, 3),
            'main_service_id' => (int) $this->service_id,
            'available' => array_key_exists($this->id, $this->availabilityMap)
                ? (bool) $this->availabilityMap[$this->id]
                : false,
            'approved' => isset($this->approved)
                ? (bool) $this->approved
                : (bool) ($this->user_id ?? 0),
            'is_premium' => (bool) $this->is_premium,
            'premium_until' => optional($this->premium_until)->toISOString(),
            'is_claimed' => (bool) $this->is_claimed,
            'phone_verified_at' => optional($this->phone_verified_at)->toISOString(),
            'slug' => (string) $this->slug,
            // Include services only for single master endpoint (when Eloquent relation is loaded)
            'services' => $this->when(
                ($this->resource instanceof \Illuminate\Database\Eloquent\Model)
                    && $this->resource->relationLoaded('services'),
                function () {
                    return $this->services->map(fn ($s) => [
                        'id' => (int) $s->id,
                        'name' => (string) $s->name,
                        'is_primary' => (int) $this->service_id === (int) $s->id,
                    ]);
                }
            ),

            // Include gallery photos list when relation is loaded
            'photos' => $this->when(
                ($this->resource instanceof \Illuminate\Database\Eloquent\Model)
                    && $this->resource->relationLoaded('gallery'),
                function () {
                    return $this->gallery->map(fn ($g) => [
                        'id' => (int) $g->id,
                        'url' => (string) ('storage/'.$g->photo),
                    ]);
                }
            ),

            // Include reviews when relation is loaded
            'reviews' => $this->when(
                ($this->resource instanceof \Illuminate\Database\Eloquent\Model)
                    && $this->resource->relationLoaded('reviews'),
                function () {
                    return $this->reviews
                        ->sortByDesc('id')
                        ->map(fn ($r) => [
                            'id' => (int) $r->id,
                            'rating' => (int) $r->rating,
                            'review' => (string) ($r->review ?? ''),
                            'user' => $r->relationLoaded('user') && $r->user
                                ? [
                                    'id' => (int) $r->user->id,
                                    'name' => (string) ($r->user->name ?? ''),
                                    'phone' => (string) ($r->user->phone ?? ''),
                                ]
                                : null,
                            'created_at' => optional($r->created_at)->toISOString(),
                        ]);
                }
            ),
        ];
    }

    private function getFormattedAddress($address)
    {
        // try {
        //     return json_decode($address)->results[0]->formatted_address ?? '';
        // } catch (\Exception $e) {
        return $address;
        // }
    }
}
