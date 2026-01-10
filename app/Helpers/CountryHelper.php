<?php
namespace App\Helpers;
use App\Models\Country;



class CountryHelper
{
    public static function currentCountry(): Country
    {
        return request()->attributes->get('country');
    }
}
