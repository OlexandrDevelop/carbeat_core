<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Access Control
    |--------------------------------------------------------------------------
    |
    | In local and testing environments the admin panel stays open to any
    | authenticated session by default. Production-like environments should
    | explicitly define at least one allowlist below.
    |
    */

    'allow_all_in_local' => env('ADMIN_ALLOW_ALL_IN_LOCAL', true),

    'allowed_user_ids' => array_values(array_filter(array_map(
        static fn (string $id): int => (int) trim($id),
        explode(',', (string) env('ADMIN_ALLOWED_USER_IDS', ''))
    ))),

    'allowed_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => mb_strtolower(trim($email)),
        explode(',', (string) env('ADMIN_ALLOWED_EMAILS', ''))
    ))),

    'allowed_phones' => array_values(array_filter(array_map(
        static fn (string $phone): string => trim($phone),
        explode(',', (string) env('ADMIN_ALLOWED_PHONES', ''))
    ))),
];
