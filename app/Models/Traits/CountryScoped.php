<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\App;

class CountryScoped implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // If a country is bound in the container, scope queries to it
        if (App::bound('country') && ($country = App::make('country'))) {
            if (!empty($country->id)) {
                $builder->where($model->getTable() . '.country_id', $country->id);
            }
        }
    }
}

