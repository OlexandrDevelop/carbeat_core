<?php

namespace App\Http\Controllers;

use App\Enums\AppBrand;
use App\Http\Services\ClaimService;
use App\Models\Master;
use Illuminate\Http\Request;

class ClaimLinkController extends Controller
{
    public function __invoke(Request $request, string $masterId, ClaimService $claimService)
    {
        $master = Master::where('id', $masterId)->first();

        $deepLink = $claimService->buildDeepLink($master->id);

        return response()->view('claim-link', [
            'master' => $master,
            'masterId' => $masterId,
            'deepLink' => $deepLink,
            'androidStoreUrl' => config('app.deep_links.android_store_url'),
            'iosStoreUrl' => config('app.deep_links.ios_store_url'),
        ]);
    }


}

