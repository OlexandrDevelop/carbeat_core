<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_id
 * @property string $locale
 * @property string $name
 */
class ServiceTranslation extends Model
{
    protected $fillable = ['service_id', 'locale', 'name'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
