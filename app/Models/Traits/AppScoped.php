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
            
            // If brand is not set, we might be in a CLI or early boot process.
            // But for safety, we fallback to CARBEAT only if brand is truly null.
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
