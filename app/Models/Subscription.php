<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\AppScoped;

/**
 * @property int $id
 * @property string $app
 * @property int $user_id
 * @property string $platform
 * @property string|null $product_id
 * @property string $external_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $last_verified_at
 * @property array<array-key, mixed>|null $raw_payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereLastVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereRawPayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereUserId($value)
 * @mixin \Eloquent
 */
class Subscription extends Model
{
    use HasFactory, AppScoped;

    protected $fillable = [
        'user_id',
        'platform',
        'product_id',
        'external_id',
        'status',
        'expires_at',
        'last_verified_at',
        'raw_payload',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
