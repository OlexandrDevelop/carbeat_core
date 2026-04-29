<?php

namespace App\Http\Services\Master;

use App\Models\Master;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MasterListService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Master::query();

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters['sort_by'] ?? 'rating');

        $query->with(['services.translations', 'reviews']);

        $perPage = $filters['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('services', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['service_id'])) {
            $query->whereHas('services', function ($q) use ($filters) {
                $q->where('services.id', $filters['service_id']);
            });
        }

        if (! empty($filters['min_rating'])) {
            $minRating = $filters['min_rating'];
            $query->whereHas('reviews', function ($q) use ($minRating) {
                $q->havingRaw('AVG(rating) >= ?', [$minRating]);
            });
        }

        if (array_key_exists('available', $filters) && $filters['available'] !== null) {
            $query->where('available', (bool) $filters['available']);
        }

        if (! empty($filters['min_age'])) {
            $query->where('age', '>=', $filters['min_age']);
        }

        if (! empty($filters['max_age'])) {
            $query->where('age', '<=', $filters['max_age']);
        }

        if (! empty($filters['min_price'])) {
            $minPrice = $filters['min_price'];
            $query->whereHas('services', function ($q) use ($minPrice) {
                $q->where('price', '>=', $minPrice);
            });
        }

        if (! empty($filters['max_price'])) {
            $maxPrice = $filters['max_price'];
            $query->whereHas('services', function ($q) use ($maxPrice) {
                $q->where('price', '<=', $maxPrice);
            });
        }

        if (! empty($filters['selected_services']) && is_array($filters['selected_services'])) {
            $ids = $filters['selected_services'];
            $query->whereHas('services', function ($q) use ($ids) {
                $q->whereIn('services.id', $ids);
            });
        }
    }

    protected function applySorting(Builder $query, string $sortBy): void
    {
        switch ($sortBy) {
            case 'rating':
                $query->leftJoin('reviews', 'masters.id', '=', 'reviews.master_id')
                    ->select('masters.*', DB::raw('COALESCE(AVG(reviews.rating), masters.rating_google, 0) as avg_rating'))
                    ->groupBy('masters.id')
                    ->orderBy('avg_rating', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'age':
                $query->orderBy('age', 'asc');
                break;
            default:
                $query->leftJoin('reviews', 'masters.id', '=', 'reviews.master_id')
                    ->select('masters.*', DB::raw('COALESCE(AVG(reviews.rating), masters.rating_google, 0) as avg_rating'))
                    ->groupBy('masters.id')
                    ->orderBy('avg_rating', 'desc');
        }
    }
}
