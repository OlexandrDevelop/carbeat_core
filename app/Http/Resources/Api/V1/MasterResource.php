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
     * This method is used to transform the `Master` model or array data into a JSON response.
     * It calculates the average rating of the master based on the reviews and returns an array containing the master's details.
     * Optimized to handle both Eloquent models and raw array data without triggering N+1 queries.
     */
    public function toArray(Request $request): array
    {
        // Safely access properties, handling both array and object resources
        $id = $this->getResourceProperty('id');
        $name = $this->getResourceProperty('name');
        $latitude = $this->getResourceProperty('latitude');
        $longitude = $this->getResourceProperty('longitude');
        $description = $this->getResourceProperty('description');
        $address = $this->getResourceProperty('address');
        $age = $this->getResourceProperty('age');
        $phone = $this->getResourceProperty('phone');
        $reviewsCount = $this->getResourceProperty('reviews_count');
        $rating = $this->getResourceProperty('rating');
        $photo = $this->getResourceProperty('photo');
        $mainThumbUrl = $this->getResourceProperty('main_thumb_url');
        $distance = $this->getResourceProperty('distance');
        $serviceId = $this->getResourceProperty('service_id');
        $approved = $this->getResourceProperty('approved');
        $isPremium = $this->getResourceProperty('is_premium');
        $premiumUntil = $this->getResourceProperty('premium_until');
        $isClaimed = $this->getResourceProperty('is_claimed');
        $phoneVerifiedAt = $this->getResourceProperty('phone_verified_at');
        $slug = $this->getResourceProperty('slug');
        $userId = $this->getResourceProperty('user_id');

        return [
            'id' => (int) $id,
            'name' => (string) $name,
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
            'description' => $description,
            'address' => $this->getFormattedAddress($address),
            'age' => (int) $age,
            'phone' => $phone,
            'reviews_count' => (int) $reviewsCount,
            'rating' => (float) round($rating, 1),
            'main_photo' => (string) 'storage/'.$photo,
            'main_thumb_url' => $mainThumbUrl ? (string) ('storage/'.$mainThumbUrl) : null,
            'distance' => (float) round($distance, 3),
            'main_service_id' => (int) $serviceId,
            'available' => array_key_exists($id, $this->availabilityMap)
                ? (bool) $this->availabilityMap[$id]
                : false,
            'approved' => $approved !== null
                ? (bool) $approved
                : (bool) ($userId ?? 0),
            'is_premium' => (bool) $isPremium,
            'premium_until' => $this->formatDateTime($premiumUntil),
            'is_claimed' => (bool) $isClaimed,
            'phone_verified_at' => $this->formatDateTime($phoneVerifiedAt),
            'slug' => (string) $slug,
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

    /**
     * Safely get a property from either array or object resource
     */
    private function getResourceProperty(string $property)
    {
        if (is_array($this->resource)) {
            return $this->resource[$property] ?? null;
        }

        return $this->{$property} ?? null;
    }

    /**
     * Format datetime string to ISO format
     */
    private function formatDateTime($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof \DateTime || $value instanceof \Illuminate\Support\Carbon) {
            return $value->toISOString();
        }

        // If it's already a string timestamp, try to parse it
        if (is_string($value)) {
            try {
                return \Carbon\Carbon::parse($value)->toISOString();
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
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
