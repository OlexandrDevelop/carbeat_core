<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Enums\AppBrand;

trait AppScoped
{
    protected static function bootAppScoped(): void
    {
        static::addGlobalScope('app', function (Builder $builder) {
            $brand = config('app.client');
            // Support both enum and string stored in config
            $brandValue = $brand instanceof AppBrand ? $brand->value : ($brand ?: AppBrand::CARBEAT->value);
            $builder->where($builder->getModel()->getTable() . '.app', $brandValue);
        });

        static::creating(function ($model) {
            if (empty($model->app)) {
                $brand = config('app.client');
                $model->app = $brand instanceof AppBrand ? $brand->value : ($brand ?: AppBrand::CARBEAT->value);
            }
        });
    }
}
