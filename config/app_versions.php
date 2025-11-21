<?php

return [
    'android' => [
        // Increase these and deploy to force or recommend updates
        'min_supported_build' => env('APP_ANDROID_MIN_BUILD', 1),
        'recommended_build' => env('APP_ANDROID_RECOMMENDED_BUILD', 1),
        // Optional: custom message and store URL override (web fallback used if empty)
        'message' => env('APP_ANDROID_UPDATE_MESSAGE', ''),
        'store_url' => env('APP_ANDROID_STORE_URL', ''),
    ],
    'ios' => [
        'min_supported_build' => env('APP_IOS_MIN_BUILD', 1),
        'recommended_build' => env('APP_IOS_RECOMMENDED_BUILD', 1),
        'message' => env('APP_IOS_UPDATE_MESSAGE', ''),
        'store_url' => env('APP_IOS_STORE_URL', ''),
    ],
];









