<?php

use App\Enums\AppBrand;

if (! function_exists('appBrand')) {
    function appBrand(): AppBrand
    {
        $brand = config('app.client');
        return $brand instanceof AppBrand ? $brand : AppBrand::fromHeader($brand);
    }
}

if (! function_exists('appBrandValue')) {
    function appBrandValue(): string
    {
        return appBrand()->value;
    }
}
