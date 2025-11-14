<?php

return [
    'trial_enabled' => env('SUBSCRIPTION_TRIAL_ENABLED', true),
    'trial_days' => (int) env('SUBSCRIPTION_TRIAL_DAYS', 30),
];


