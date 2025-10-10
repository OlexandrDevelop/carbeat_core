<?php

namespace App\Http\Services\Admin;

use App\Models\Master;
use App\Models\City;
use App\Models\Review;
use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MasterAdminService
{
    public function listMasters(array $params): LengthAwarePaginator
    {
        $query = Master::query()
            ->with(['services', 'user', 'city'])
            ->withAvg('reviews', 'rating')
            ->withCount('gallery');

        $this->applyFilters($query, $params);
        $this->applySorting($query, $params['sort_by'] ?? 'created_at', $params['sort_dir'] ?? 'desc');

        $perPage = min(max((int) ($params['per_page'] ?? 20), 1), 100);
        return $query->paginate($perPage);
    }

    public function getMaster(int $id): Master
    {
        return Master::with(['services', 'user', 'reviews', 'gallery'])
            ->withAvg('reviews', 'rating')
            ->findOrFail($id);
    }

    public function updateMaster(int $id, array $data): Master
    {
        $master = Master::findOrFail($id);
        $master->fill($data);
        $master->save();

        if (array_key_exists('service_ids', $data)) {
            $master->services()->sync($data['service_ids']);
        }

        return $master->fresh(['services', 'user', 'reviews'])->loadAvg('reviews', 'rating');
    }

    public function deleteMaster(int $id): void
    {
        DB::transaction(function () use ($id) {
            $master = Master::with(['services', 'reviews'])->findOrFail($id);

            if (method_exists($master, 'reviews')) {
                $master->reviews()->delete();
            }
            if (method_exists($master, 'appointments')) {
                $master->appointments()->delete();
            }
            if (method_exists($master, 'galleryPhotos')) {
                $master->galleryPhotos()->delete();
            }

            if (method_exists($master, 'services')) {
                $master->services()->detach();
            }

            $master->delete();
        });
    }

    public function deleteAllMasters(): int
    {
        $deletedCount = 0;
        DB::transaction(function () use (&$deletedCount) {
            Master::query()->chunkById(200, function ($masters) use (&$deletedCount) {
                /** @var Master $master */
                foreach ($masters as $master) {
                    if (method_exists($master, 'reviews')) {
                        $master->reviews()->delete();
                    }
                    if (method_exists($master, 'appointments')) {
                        $master->appointments()->delete();
                    }
                    if (method_exists($master, 'galleryPhotos')) {
                        $master->galleryPhotos()->delete();
                    }
                    if (method_exists($master, 'services')) {
                        $master->services()->detach();
                    }
                    $master->delete();
                    $deletedCount++;
                }
            });
        });
        return $deletedCount;
    }

    public function listServices()
    {
        return Service::query()->orderBy('name')->get(['id', 'name']);
    }

    public function getReviews(int $masterId)
    {
        return Review::where('master_id', $masterId)
            ->with('user:id,name,phone')
            ->orderByDesc('created_at')
            ->get(['id', 'rating', 'review', 'created_at', 'user_id', 'master_id']);
    }

    public function createReview(int $masterId, array $data): Review
    {
        if (empty($data['user_id'])) {
            throw new \InvalidArgumentException('user_id is required');
        }

        $review = Review::create([
            'master_id' => $masterId,
            'user_id' => $data['user_id'],
            'rating' => $data['rating'],
            'review' => $data['review'] ?? null,
        ]);
        return $review->load('user:id,name,phone');
    }

    public function updateReview(int $reviewId, array $data): Review
    {
        $review = Review::findOrFail($reviewId);
        $review->fill($data);
        $review->save();
        return $review->load('user:id,name,phone');
    }

    public function deleteReview(int $reviewId): void
    {
        $review = Review::findOrFail($reviewId);
        $review->delete();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if (isset($filters['available']) && $filters['available'] !== '') {
            $query->where('available', filter_var($filters['available'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['service_id'])) {
            $serviceId = (int) $filters['service_id'];
            $query->whereHas('services', fn ($q) => $q->where('services.id', $serviceId));
        }

        if (! empty($filters['city_id'])) {
            $cityId = (int) $filters['city_id'];
            $query->where('city_id', $cityId);
        }

        if (isset($filters['uses_system']) && $filters['uses_system'] !== '') {
            if (filter_var($filters['uses_system'], FILTER_VALIDATE_BOOLEAN)) {
                $query->where('user_id', '!=', 1);
            } else {
                $query->where('user_id', 1);
            }
        }
    }

    public function listCities()
    {
        return City::query()->orderBy('name')->get(['id', 'name']);
    }

    private function applySorting(Builder $query, string $sortBy, string $sortDir): void
    {
        $direction = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        switch ($sortBy) {
            case 'id':
                $query->orderBy('masters.id', $direction);
                break;
            case 'uses_system':
                $query->orderByRaw('(CASE WHEN user_id != 1 THEN 1 ELSE 0 END) '.$direction);
                break;
            case 'last_login_at':
                $query->leftJoin('users', 'users.id', '=', 'masters.user_id')
                    ->select('masters.*')
                    ->orderBy('users.last_login_at', $direction);
                break;
            case 'city':
                $query->leftJoin('cities', 'cities.id', '=', 'masters.city_id')
                    ->select('masters.*')
                    ->orderBy('cities.name', $direction);
                break;
            case 'rating':
                // Sort by average rating from reviews (use withAvg alias)
                $query->orderBy('reviews_avg_rating', $direction);
                break;
            case 'photos_count':
                // withCount('gallery') alias is gallery_count
                $query->orderBy('gallery_count', $direction);
                break;
            case 'available':
                $query->orderBy('available', $direction);
                break;
            case 'photo':
                // Sort by presence of photo (non-null and non-empty)
                $query->orderByRaw('(CASE WHEN photo IS NOT NULL AND photo != "" THEN 1 ELSE 0 END) '.$direction)
                    ->orderBy('id', 'desc');
                break;
            case 'name':
            case 'age':
            case 'created_at':
                $query->orderBy($sortBy, $direction);
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }
    }
}
