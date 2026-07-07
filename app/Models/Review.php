<?php

namespace App\Models;

use App\Models\Traits\AppScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $app
 * @property string $review
 * @property int $rating
 * @property int $master_id
 * @property int|null $parent_id
 * @property int|null $user_id
 * @property string|null $guest_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\Master $master
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Review|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $replies
 *
 * @method static \Database\Factories\ReviewFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review recent($days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review verified()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereMasterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereReview($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Review extends Model
{
    use AppScoped, HasFactory;

    protected $fillable = [
        'review',
        'rating',
        'reviewed_at',
        'master_id',
        'parent_id',
        'user_id',
        'guest_name',
    ];

    protected $casts = [
        'rating' => 'integer',
        'master_id' => 'integer',
        'parent_id' => 'integer',
        'user_id' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the master that owns the review
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    /**
     * Get the user that wrote the review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The review this one replies to, if any
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'parent_id');
    }

    /**
     * Replies posted to this review
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Review::class, 'parent_id')->orderBy('id');
    }

    /**
     * Scope for verified reviews only
     */
    public function scopeVerified($query)
    {
        return $query->where('verified_phone', true);
    }

    /**
     * Scope for recent reviews
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
