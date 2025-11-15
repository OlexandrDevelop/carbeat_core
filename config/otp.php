<?php

return [
    // Universal OTP override code. When set (non-empty), any request with this code will pass verification.
    'universal_code' => env('OTP_UNIVERSAL_CODE', ''),

    // Allow a local-only bypass code for developer convenience.
    'enable_local_bypass' => env('OTP_ENABLE_LOCAL_BYPASS', true),
    'local_bypass_code' => env('OTP_LOCAL_BYPASS_CODE', '0000'),
];


