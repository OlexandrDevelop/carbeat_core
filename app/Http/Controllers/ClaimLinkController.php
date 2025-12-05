<?php

namespace App\Http\Controllers;

use App\Models\Master;
use Illuminate\Http\Request;

class ClaimLinkController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        $master = Master::where('claim_token', $token)->first();
        $masterId = $request->integer('master_id', $master?->id);

        $deepLink = $this->buildDeepLink($token, $masterId);

        return response()->view('claim-link', [
            'token' => $token,
            'master' => $master,
            'masterId' => $masterId,
            'deepLink' => $deepLink,
            'androidStoreUrl' => config('app.deep_links.android_store_url'),
            'iosStoreUrl' => config('app.deep_links.ios_store_url'),
        ]);
    }

    private function buildDeepLink(string $token, ?int $masterId): string
    {
        $scheme = config('app.deep_links.scheme', 'carbeat');
        $host = config('app.deep_links.host', 'claim');
        $query = $masterId ? '?master_id='.$masterId : '';

        return "{$scheme}://{$host}/{$token}{$query}";
    }
}

