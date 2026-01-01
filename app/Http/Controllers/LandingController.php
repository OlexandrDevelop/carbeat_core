<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class LandingController extends Controller
{
    public function __invoke(): InertiaResponse|Response
    {
        // Keep the old behavior for logged-in users (go to admin UI)
        if (Auth::check()) {
            return Inertia::location(route('admin.masters.index'));
        }

        return Inertia::render('Landing', [
            'appName' => config('app.name'),
            'adminUrl' => url('/admin'),
            'termsUrl' => route('terms'),
            'privacyUrl' => route('privacy'),
            'dataDeletionUrl' => route('data_deletion'),
        ]);
    }
}
