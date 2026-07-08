<?php

namespace App\Http\Middleware;

use App\Enums\AppBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy($this->ziggyGroup($request)))->toArray(),
                'location' => $request->url(),
            ],
            'locale' => App::getLocale(),
            'translations' => function () {
                return [
                    'messages' => trans('messages'),
                ];
            },
            'brand' => config('app.client') instanceof AppBrand
                ? config('app.client')->value
                : AppBrand::CARBEAT->value,
        ];
    }

    private function ziggyGroup(Request $request): string
    {
        if ($request->routeIs('admin.*')) {
            return 'admin';
        }

        if ($request->routeIs('master.*') || $request->routeIs('master-login') || $request->routeIs('master-logout')) {
            return 'master';
        }

        return 'public';
    }
}
