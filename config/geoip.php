<?php

return [
    // Local MaxMind GeoLite2-Country database, refreshed weekly by `geoip:update`.
    'database_path' => env('GEOIP_DATABASE_PATH', storage_path('app/geoip/GeoLite2-Country.mmdb')),

    // Credentials for `geoip:update` (maxmind.com account, free GeoLite2 license).
    'account_id' => env('GEOIP_ACCOUNT_ID'),
    'license_key' => env('GEOIP_LICENSE_KEY'),

    // Default map view used when the visitor's country can't be resolved
    // (no database, private/local IP, lookup miss) or isn't in the map below.
    'default_center' => [
        'lat' => 50.4501,
        'lng' => 30.5234,
        'zoom' => 11,
    ],

    // Per-country default map view, keyed by ISO 3166-1 alpha-2 country code.
    'country_centers' => [
        'UA' => ['lat' => 50.4501, 'lng' => 30.5234, 'zoom' => 11], // Kyiv
        'DE' => ['lat' => 52.5200, 'lng' => 13.4050, 'zoom' => 6],  // Berlin
    ],
];
