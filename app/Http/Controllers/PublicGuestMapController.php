<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppBrand;
use Inertia\Inertia;
use Inertia\Response;

class PublicGuestMapController extends Controller
{
    public function __invoke(): Response
    {
        $brand = config('app.client') instanceof AppBrand
            ? config('app.client')
            : AppBrand::CARBEAT;

        $page = match ($brand) {
            AppBrand::FLOXCITY => 'Floxcity/Public/GuestMap',
            default => 'Carbeat/Public/GuestMap',
        };

        return Inertia::render($page, [
            'apiBase' => '/api',
        ]);
    }
}
