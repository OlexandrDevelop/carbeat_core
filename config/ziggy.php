<?php

return [
    'groups' => [
        'public' => [
            'landing',
            'marketing.landing',
            'public.*',
            'sitemap',
            'robots',
            'terms',
            'privacy',
            'data_deletion',
            'login',
        ],
        'admin' => [
            'landing',
            'marketing.landing',
            'public.*',
            'sitemap',
            'robots',
            'terms',
            'privacy',
            'data_deletion',
            'login',
            'logout',
            'admin.*',
        ],
    ],
];
