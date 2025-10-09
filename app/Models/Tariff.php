<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tariff extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'features',
        'currency',
        'apple_product_id',
        'google_product_id',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'float',
    ];

    public function masters(): HasMany
    {
        return $this->hasMany(Master::class);
    }
}
