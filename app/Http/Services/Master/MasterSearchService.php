<?php

namespace App\Http\Services\Master;

use App\Enums\AppBrand;
use App\Models\Master;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MasterSearchService
{
    private const CACHE_TTL_SECONDS = 30;

    /**
     * @return array{data: array<int, array<string, mixed>>, total: int}
     */
    public function getMastersOnDistance(float $lat, float $lng, float $zoom, array $filters, int $perPage, int $page, ?array $bbox = null, ?string $fields = null): array
    {
        $cacheKey = $this->buildCacheKey($lat, $lng, $zoom, $filters, $perPage, $page, $bbox, $fields);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($lat, $lng, $zoom, $filters, $perPage, $page, $bbox, $fields) {
            return $this->queryMastersOnDistance($lat, $lng, $zoom, $filters, $perPage, $page, $bbox, $fields);
        });
    }

    private function buildCacheKey(float $lat, float $lng, float $zoom, array $filters, int $perPage, int $page, ?array $bbox, ?string $fields): string
    {
        // Coordinates are rounded so nearby users/pans within the same short window share a cache entry.
        $bucket = $bbox !== null
            ? array_map(fn (float $v) => round($v, 3), $bbox)
            : ['lat' => round($lat, 3), 'lng' => round($lng, 3), 'zoom' => round($zoom, 1)];

        return 'masters:search:'.md5(json_encode([$bucket, $filters, $perPage, $page, $fields]));
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, total: int}
     */
    private function queryMastersOnDistance(float $lat, float $lng, float $zoom, array $filters, int $perPage, int $page, ?array $bbox, ?string $fields): array
    {
        $offset = ($page - 1) * $perPage;

        // Exact viewport bbox (from the web map) takes precedence over the zoom-derived radius.
        // The zoom-radius path below is left untouched so mobile clients (which never send bbox)
        // get exactly the same result set as before.
        $useExactBbox = $bbox !== null;

        if ($useExactBbox) {
            $minLat = $bbox['min_lat'];
            $maxLat = $bbox['max_lat'];
            $minLng = $bbox['min_lng'];
            $maxLng = $bbox['max_lng'];
        } else {
            $maxDistance = $this::calculateSearchRadius($zoom);
            $latDelta = $maxDistance / 111; // 111 км ≈ 1° широти
            $lngDelta = $maxDistance / (111 * cos(deg2rad($lat))); // Δ довготи залежить від широти
            $minLat = $lat - $latDelta;
            $maxLat = $lat + $latDelta;
            $minLng = $lng - $lngDelta;
            $maxLng = $lng + $lngDelta;
        }

        $descriptionField = $fields === 'light' ? "'' as description" : 'masters.description';
        $addressField = $fields === 'light' ? "'' as address" : 'masters.address';

        $query = "
    SELECT
        masters.id,
        masters.name,
        COALESCE(masters.contact_phone, users.phone) as phone,
        {$addressField},
        masters.latitude,
        masters.longitude,
        {$descriptionField},
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
    ";
        $queryParams = [
            'distance_lat' => $lat,
            'distance_lng' => $lng,
            'distance_lat2' => $lat,
            'min_lat' => $minLat,
            'max_lat' => $maxLat,
            'min_lng' => $minLng,
            'max_lng' => $maxLng,
        ];

        // Count query mirrors the same FROM/JOIN/WHERE (+ filters) so `total`/`last_page` reflect
        // every matching master, not just the current page — the paginator (and the mobile client,
        // which reads meta.last_page to fetch remaining pages) relies on this being accurate.
        $countQuery = "
    SELECT
        (
            6371 * acos(
                cos(radians(:distance_lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians(:distance_lng))
                + sin(radians(:distance_lat2)) * sin(radians(latitude))
            )
        ) as distance
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
    ";
        $countParams = $queryParams;

        // Додатково застосовуємо фільтри, якщо потрібно (окремо для обох запитів)
        MasterFilterService::applyFilters($filters, $query, $queryParams);
        MasterFilterService::applyFilters($filters, $countQuery, $countParams);

        if ($useExactBbox) {
            // bbox is already an exact rectangle — no radius cutoff needed.
            // Secondary sort by id keeps LIMIT/OFFSET pagination stable across parallel page fetches.
            $query .= '
        ORDER BY
        distance ASC, masters.id ASC
    ';
        } else {
            $queryParams['max_distance'] = $maxDistance;
            $countParams['max_distance'] = $maxDistance;
            $query .= '
        HAVING
        distance <= :max_distance
    ORDER BY
        distance ASC
    ';
            $countQuery .= '
        HAVING
        distance <= :max_distance
    ';
        }
        $query .= " LIMIT {$perPage} OFFSET {$offset}";

        $results = DB::select($query, $queryParams);

        $countRow = DB::select("SELECT COUNT(*) as total FROM ({$countQuery}) as bounded", $countParams);
        $total = (int) ($countRow[0]->total ?? 0);

        // Convert raw results to array format for compatibility
        // Ensure app field is present for each master to avoid N+1 queries in Redis service
        $data = array_map(function ($result) {
            $arr = (array) $result;
            // Ensure app field exists (should be selected in query, but add default as safety)
            if (!isset($arr['app'])) {
                $arr['app'] = config('app.client') instanceof AppBrand
                    ? config('app.client')->value
                    : (config('app.client') ?: 'carbeat');
            }
            return $arr;
        }, $results);

        return ['data' => $data, 'total' => $total];
    }

    private static function calculateSearchRadius(float $zoom): float
    {
        $earthRadiusKm = 20037.5;

        return $earthRadiusKm / $zoom;
    }
}
