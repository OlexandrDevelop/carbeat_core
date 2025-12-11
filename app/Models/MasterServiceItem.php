<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Master|null $master
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterServiceItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterServiceItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterServiceItem query()
 * @mixin \Eloquent
 */
class MasterServiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_id',
        'source',
        'name',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }
}
