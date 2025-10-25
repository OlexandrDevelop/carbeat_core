<?php

namespace App\Http\Services\Admin;

use App\Models\Tariff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class TariffAdminService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $q = Tariff::query();
        if (! empty($filters['q'])) {
            $q->where('name', 'like', '%'.$filters['q'].'%');
        }
        if (! empty($filters['currency'])) {
            $q->where('currency', $filters['currency']);
        }
        if (! empty($filters['price_min'])) {
            $q->where('price', '>=', (float) $filters['price_min']);
        }
        if (! empty($filters['price_max'])) {
            $q->where('price', '<=', (float) $filters['price_max']);
        }

        $sort = $filters['sort'] ?? 'id';
        $dir = strtolower($filters['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $q->orderBy($sort, $dir);

        $perPage = (int) ($filters['per_page'] ?? 20);
        return $q->paginate($perPage);
    }

    public function get(int $id): Tariff
    {
        return Tariff::findOrFail($id);
    }

    public function create(array $data): Tariff
    {
        return Tariff::create($data);
    }

    public function update(int $id, array $data): Tariff
    {
        $t = Tariff::findOrFail($id);
        $t->fill($data);
        $t->save();
        return $t->refresh();
    }

    public function destroy(int $id): void
    {
        Tariff::whereKey($id)->delete();
    }
}
