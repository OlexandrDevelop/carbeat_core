<?php

namespace App\Http\Controllers;

use App\Enums\AppBrand;
use App\Models\Master;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicMasterController extends Controller
{
    public function show(string $slug): Response
    {
        $master = Master::with(['services.translations', 'gallery', 'city'])
            ->where('slug', $slug)
            ->firstOrFail();

        $photo = $master->photo ? Storage::url($master->photo) : $master->main_photo;
        $gallery = $master->gallery->map(function ($item) {
            return [
                'id' => $item->id,
                'url' => Storage::url($item->photo),
            ];
        })->toArray();

        $services = $master->services->map(function ($service) use ($master) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'is_primary' => (int) $service->id === (int) $master->service_id,
            ];
        })->toArray();

        $claimLink = $master->is_claimed ? null : $this->buildClaimLink($master);

        $brand = config('app.client') instanceof AppBrand ? config('app.client') : AppBrand::CARBEAT;
        $page = $brand === AppBrand::FLOXCITY ? 'Floxcity/Public/Master' : 'Carbeat/Public/Master';

        return Inertia::render($page, [
            'master' => [
                'id' => $master->id,
                'name' => $master->name,
                'description' => $master->description,
                'address' => $master->address,
                'city' => optional($master->city)->name,
                'phone' => $master->phone,
                'latitude' => $master->latitude,
                'longitude' => $master->longitude,
                'experience' => $master->experience,
                'services' => $services,
                'gallery' => $gallery,
                'main_photo' => $photo,
                'working_hours' => $master->working_hours ?? [],
                'is_claimed' => (bool) $master->is_claimed,
                'claim_link' => $claimLink,
                'slug' => $master->slug,
                'rating' => $master->rating,
                'reviews_count' => $master->reviews_count,
            ],
        ]);
    }

    private function buildClaimLink(Master $master): ?string
    {
        if (empty($master->claim_token)) {
            return null;
        }

        $base = rtrim(config('app.claim_base_url'), '/');

        return "{$base}/{$master->claim_token}?master_id={$master->id}";
    }
}

