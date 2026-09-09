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
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 * @property-read int $duration_seconds
 * @property-read int $requests_count
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

    protected $appends = [
        'duration_seconds',
        'requests_count',
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

    /**
     * Wall-clock time between the first and most recent event. A
     * single-event session (still browsing, or a one-page bounce) is 0.
     *
     * Because this is in $appends, it's evaluated for every VisitSession
     * instance during JSON serialization — including ones from aggregate
     * queries (e.g. stats()'s top-referrers/by-device breakdowns) that
     * select only a couple of columns and never loaded started_at/
     * last_seen_at. Guard against that instead of crashing on null.
     */
    public function getDurationSecondsAttribute(): int
    {
        if (! $this->started_at || ! $this->last_seen_at) {
            return 0;
        }

        return (int) abs($this->last_seen_at->diffInSeconds($this->started_at));
    }

    /**
     * Every beacon the browser sent for this session — pageviews and clicks
     * combined — i.e. how many requests this visitor made to the server.
     */
    public function getRequestsCountAttribute(): int
    {
        return (int) $this->pageviews_count + (int) $this->clicks_count;
    }

    public function events(): HasMany
    {
        return $this->hasMany(VisitEvent::class)->orderBy('created_at');
    }
}
