<?php

namespace App\Http\Resources\Web;

use App\Http\Resources\Api\V1\ServiceResource;
use App\Http\Services\Appointment\AppointmentRedisService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
 * @property int $reviews_count
 * @property float $rating
 * @property int $service_id
 * @property int $tariff_id
 * @property string $slug
 * @property string|null $tariff
 */
class MasterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * This method is used to transform the `Master` model into an array that can be returned as a JSON response.
     * It calculates the average rating of the master based on the reviews and returns an array containing the master's details.
     */
    public function toArray(Request $request): array
    {
        $appointmentRedisService = app(AppointmentRedisService::class);

        $avgRating = $this->reviews_avg_rating !== null
            ? round((float) $this->reviews_avg_rating, 1)
            : 0.0;

        $photoUrl = null;
        if ($this->photo) {
            $photoUrl = Str::startsWith($this->photo, ['http://', 'https://'])
                ? (string) $this->photo
                : url('storage/'.$this->photo);
        }

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
            'rating' => (float) $avgRating,
            'main_photo' => $photoUrl,
            'distance' => (float) round($this->distance, 3),
            'main_service_id' => (int) $this->service_id,
            'slug' => (string) $this->slug,
            'services' => ServiceResource::collection($this->services),
            'reviews' => $this->reviews ? $this->reviews->map(function ($review) {
                $userName = $review->user ? $review->user->name : 'Anonymous';
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'review' => $review->review,
                    'created_at' => $review->created_at,
                    'user' => $userName,
                    'user_name' => $userName
                ];
            }) : [],
            'available' => $appointmentRedisService->isMasterAvailableAt($this->id, now()),
            'is_premium' => (bool) $this->is_premium,
            'premium_until' => optional($this->premium_until)->toISOString(),
            'approved' => isset($this->approved)
                ? (bool) $this->approved
                : (bool) ($this->user_id ?? 0),
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
