<?php

namespace App\Http\Controllers;

use App\Enums\AppBrand;
use Illuminate\Http\RedirectResponse;

class PublicMasterController extends Controller
{
    public function show(string $slug): RedirectResponse
    {
        $route = AppBrand::fromHost(request()->getHost()) === AppBrand::FLOXCITY
            ? 'public.salon.show'
            : 'public.sto.show';

        return redirect()->route($route, ['slug' => $slug], 301);
    }
}
