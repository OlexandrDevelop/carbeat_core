<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $master_id
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $end_time
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Master $master
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterTimeOff newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterTimeOff newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterTimeOff query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterTimeOff whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterTimeOff whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterTimeOff whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterTimeOff whereMasterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterTimeOff whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterTimeOff whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterTimeOff whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MasterTimeOff extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_id',
        'start_time',
        'end_time',
        'reason',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }
}
