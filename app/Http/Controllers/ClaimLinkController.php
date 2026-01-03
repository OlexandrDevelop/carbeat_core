<?php

namespace App\Http\Controllers;

use App\Enums\AppBrand;
use App\Http\Services\ClaimService;
use App\Models\Master;
use Illuminate\Http\Request;

class ClaimLinkController extends Controller
{
    public function __invoke(Request $request, string $token, ClaimService $claimService)
    {
        $master = Master::where('claim_token', $token)->first();
        $masterId = $request->integer('master_id', $master?->id);

        $deepLink = $claimService->buildDeepLink($token, $masterId);

        return response()->view('claim-link', [
            'token' => $token,
            'master' => $master,
            'masterId' => $masterId,
            'deepLink' => $deepLink,
            'androidStoreUrl' => config('app.deep_links.android_store_url'),
            'iosStoreUrl' => config('app.deep_links.ios_store_url'),
        ]);
    }


}

