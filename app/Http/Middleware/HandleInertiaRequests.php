<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use App\Enums\AppBrand;

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
        // Determine brand for this request in the same order as AdminBrand middleware
        // so that Inertia shares reflect the immediate query param / session / cookie.
        $selected = null;
        try {
            $brandParam = $request->query('brand');
            if ($brandParam) {
                $selected = AppBrand::from($brandParam);
            }
        } catch (\Throwable $e) {
            $selected = null;
        }

        if (! $selected) {
            try {
                $stored = $request->session()->get('admin_brand');
                $cookieBrand = $request->cookie('admin_brand');

                if ($stored) {
                    $selected = AppBrand::from($stored);
                } elseif ($cookieBrand) {
                    $selected = AppBrand::from($cookieBrand);
                }
            } catch (\Throwable $e) {
                $selected = null;
            }
        }

        // Fallback to header-detection (DetectApp) if still nothing selected
        if (! $selected) {
            $selected = AppBrand::fromHeader($request->header('X-App'));
        }

        $brandValue = $selected instanceof AppBrand ? $selected->value : (string) ($selected ?? AppBrand::CARBEAT->value);
        $brands = array_map(function (AppBrand $b) {
            return [
                'value' => $b->value,
                'label' => strtoupper($b->value),
            ];
        }, AppBrand::cases());

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'locale' => App::getLocale(),
            'translations' => function () {
                return [
                    'messages' => trans('messages'),
                ];
            },
            'csrf_token' => csrf_token(),
            'adminBrand' => $brandValue,
            'brands' => $brands,
        ];
    }
}
