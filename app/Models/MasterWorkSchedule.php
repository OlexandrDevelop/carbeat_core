<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $master_id
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Master $master
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterWorkSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterWorkSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterWorkSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterWorkSchedule whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterWorkSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterWorkSchedule whereDayOfWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterWorkSchedule whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterWorkSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterWorkSchedule whereMasterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterWorkSchedule whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MasterWorkSchedule whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MasterWorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_id',
        'day_of_week',
        'start_time',
        'end_time',
        'active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'active' => 'boolean',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }
}
