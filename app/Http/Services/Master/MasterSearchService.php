<?php

namespace App\Http\Services\Master;

use App\Enums\AppBrand;
use App\Models\Master;
use Illuminate\Support\Facades\DB;

class MasterSearchService
{
    public function getMastersOnDistance(float $lat, float $lng, float $zoom, array $filters, int $perPage, int $page): array
    {
        $maxDistance = $this::calculateSearchRadius($zoom);
        $offset = ($page - 1) * $perPage;

        $latDelta = $maxDistance / 111; // 111 км ≈ 1° широти
        $lngDelta = $maxDistance / (111 * cos(deg2rad($lat))); // Δ довготи залежить від широти

        $query = '
    SELECT
        masters.id,
        masters.name,
        COALESCE(masters.contact_phone, users.phone) as phone,
        masters.address,
        masters.latitude,
        masters.longitude,
        masters.description,
        masters.main_thumb_url,
        masters.slug,
        masters.status,
        masters.status_expires_at,
        masters.age,
        masters.photo,
        masters.service_id,
        masters.is_premium,
        masters.premium_until,
        masters.is_claimed,
        masters.phone_verified_at,
        masters.app,
        masters.user_id,
        masters.contact_phone,
        CASE WHEN masters.user_id IS NULL THEN 0 ELSE 1 END as approved,
        (
            6371 * acos(
                cos(radians(:distance_lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians(:distance_lng))
                + sin(radians(:distance_lat2)) * sin(radians(latitude))
            )
        ) as distance,
        COALESCE(reviews_summary.reviews_count, 0) as reviews_count,
        COALESCE(reviews_summary.rating, masters.rating_google, 0) as rating
    FROM
        masters
    LEFT JOIN (
        SELECT
            master_id,
            COUNT(id) as reviews_count,
            AVG(rating) as rating
        FROM
            reviews
        GROUP BY
            master_id
    ) as reviews_summary ON reviews_summary.master_id = masters.id
    LEFT JOIN users ON users.id = masters.user_id
    WHERE
        latitude BETWEEN :min_lat AND :max_lat
        AND longitude BETWEEN :min_lng AND :max_lng
    ';
        $queryParams = [
            'distance_lat' => $lat,
            'distance_lng' => $lng,
            'distance_lat2' => $lat,
            'min_lat' => $lat - $latDelta,
            'max_lat' => $lat + $latDelta,
            'min_lng' => $lng - $lngDelta,
            'max_lng' => $lng + $lngDelta,
            'max_distance' => $maxDistance,
        ];

        // Додатково застосовуємо фільтри, якщо потрібно
        MasterFilterService::applyFilters($filters, $query, $queryParams);
        $query .= '
        HAVING
        distance <= :max_distance
    ORDER BY
        distance ASC
    ';
        $query .= " LIMIT {$perPage} OFFSET {$offset}";

        $results = DB::select($query, $queryParams);

        // Convert raw results to array format for compatibility
        // Ensure app field is present for each master to avoid N+1 queries in Redis service
        return array_map(function ($result) {
            $arr = (array) $result;
            // Ensure app field exists (should be selected in query, but add default as safety)
            if (!isset($arr['app'])) {
                $arr['app'] = config('app.client') instanceof AppBrand
                    ? config('app.client')->value
                    : (config('app.client') ?: 'carbeat');
            }
            return $arr;
        }, $results);
    }

    private static function calculateSearchRadius(float $zoom): float
    {
        $earthRadiusKm = 20037.5;

        return $earthRadiusKm / $zoom;
    }
}
