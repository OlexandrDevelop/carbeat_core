<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $master_id
 * @property string $photo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Master $master
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterGallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterGallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterGallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterGallery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterGallery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterGallery whereMasterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterGallery wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterGallery whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MasterGallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_id',
        'photo',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }
}
