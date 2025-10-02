<?php

namespace App\Http\Services\Master;

use App\Http\Resources\Api\V1\MasterResource;
use App\Http\Services\Appointment\AppointmentRedisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class MasterFetcher
{
    public function __construct(
        private MasterService $masterService,
        private AppointmentRedisService $appointmentRedisService,
    ) {}

    public function fetch(array $validated): JsonResponse
    {
        $page = $validated['page'] ?? 1;

        $filters = [
            'name' => $validated['name'] ?? null,
            'distance' => $validated['distance'] ?? null,
            'service_id' => $validated['service_id'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'available' => $validated['available'] ?? null,
        ];

        //Kyiv coordinates by default
        $lat = $validated['lat'] ?? 50.4501;
        $lng = $validated['lng'] ?? 30.5234;
        $zoom = $validated['zoom'] ?? 12;

        $masters = $this->masterService->getMastersOnDistance(
            $page,
            $lat,
            $lng,
            $zoom,
            $filters
        );

        // Use simple availability flags map
        $availabilityMap = $this->appointmentRedisService
            ->getAvailabilityFlagsForMany($masters->pluck('id')->all());

        $masters = $this->applyAvailabilityFilter($masters, $filters['available'], $availabilityMap);

        return response()->json([
            'data' => $masters->map(fn ($m) => new MasterResource($m, $availabilityMap)),
            'meta' => [
                'last_page' => $masters->lastPage(),
                'current_page' => $masters->currentPage(),
                'per_page' => $masters->perPage(),
                'total' => $masters->total(),
            ],
        ]);
    }

    private function applyAvailabilityFilter($masters, $onlyAvailable, $availabilityMap)
    {
        if (! $onlyAvailable) {
            return $masters;
        }

        $filtered = $masters->getCollection()->filter(fn ($m) => $availabilityMap[$m->id] ?? false);

        return new LengthAwarePaginator(
            $filtered->forPage($masters->currentPage(), $masters->perPage()),
            $filtered->count(),
            $masters->perPage(),
            $masters->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
