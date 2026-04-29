<?php

namespace App\Http\Services;

use App\Models\Master;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceService
{
    public function getServices(Request $request): Collection|array|LengthAwarePaginator
    {
        return Service::query()
            ->select(['id', 'name'])
            ->with('translations')
            ->withCount('masters')
            ->paginate(100, ['*'], 'page', $request->input('page'));
    }

    public function getServicesForMaster(int $masterId): Collection|array|LengthAwarePaginator
    {
        $master = Master::find($masterId);

        return $master->services()->with('translations')->get();
    }
}
