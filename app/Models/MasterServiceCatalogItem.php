<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\AppScoped;

class MasterServiceCatalogItem extends Model
{
    use AppScoped;

    protected $table = 'master_service_catalog';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'master_id',
        'name_uk',
        'name_en',
        'duration_minutes',
        'price_uah',
        'display_order',
        'app',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'price_uah' => 'integer',
        'display_order' => 'integer',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }
}
