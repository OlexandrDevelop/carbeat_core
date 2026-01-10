<?php
use App\Models\Country;

function currentCountry(): Country
{
    return request()->attributes->get('country');
}
