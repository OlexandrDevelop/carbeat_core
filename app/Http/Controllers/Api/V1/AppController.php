<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AppVersionRequest;
use App\Http\Resources\Api\V1\AppVersionResource;

class AppController extends Controller
{
    public function version(AppVersionRequest $request): AppVersionResource
    {
        $platform = $request->input('platform');
        $build = (int) ($request->input('build') ?? 0);

        $config = config('app_versions');
        $data = $config[$platform] ?? [
            'min_supported_build' => 1,
            'recommended_build' => 1,
            'store_url' => '',
            'message' => '',
        ];

        return new AppVersionResource([
            'platform' => $platform,
            'current_build' => $build,
            'min_supported_build' => (int) ($data['min_supported_build'] ?? 1),
            'recommended_build' => (int) ($data['recommended_build'] ?? 1),
            'store_url' => (string) ($data['store_url'] ?? ''),
            'message' => (string) ($data['message'] ?? ''),
        ]);
    }
}


