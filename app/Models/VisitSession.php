<?php

namespace App\Models;

use App\Models\Traits\AppScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $app
 * @property string $session_token
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $device_type
 * @property string|null $referrer
 * @property string|null $referrer_host
 * @property string|null $landing_path
 * @property int $pageviews_count
 * @property int $clicks_count
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon $last_seen_at
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VisitEvent> $events
 */
class VisitSession extends Model
{
    use AppScoped;

    protected $fillable = [
        'session_token',
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'referrer',
        'referrer_host',
        'landing_path',
        'pageviews_count',
        'clicks_count',
        'started_at',
        'last_seen_at',
    ];

    protected $casts = [
        'pageviews_count' => 'integer',
        'clicks_count' => 'integer',
        'started_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(VisitEvent::class)->orderBy('created_at');
    }
}
