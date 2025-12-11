<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\AppScoped;

/**
 * @property int $id
 * @property string $app
 * @property int $master_id
 * @property int|null $client_id
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $end_time
 * @property string $status
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Client|null $client
 * @property-read \App\Models\Master $master
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereMasterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Booking extends Model
{
    use HasFactory, AppScoped;

    protected $fillable = [
        'master_id',
        'client_id',
        'start_time',
        'end_time',
        'status',
        'note',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
