<?php

namespace App\DTO;

use Carbon\Carbon;

class SubscriptionStatus
{
    public function __construct(
        public bool $active,
        public ?string $platform = null,
        public ?string $product_id = null,
        public ?Carbon $expires_at = null,
    ) {}
}
