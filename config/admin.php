<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Access Control
    |--------------------------------------------------------------------------
    |
    | In local and testing environments the admin panel stays open to any
    | authenticated session by default. In all other environments, access is
    | gated by the `is_admin` column on the users table.
    |
    */

    'allow_all_in_local' => env('ADMIN_ALLOW_ALL_IN_LOCAL', true),
];
