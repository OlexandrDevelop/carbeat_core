<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['POST', 'GET', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
    'allowed_origins' => [
        'chrome-extension://*',
        '*', // Allow all origins for mobile apps
    ],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-App', 'locale'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false,
];
