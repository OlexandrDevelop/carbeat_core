<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\AppScoped;

/**
 * @property int $id
 * @property string $app
 * @property string|null $phone
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\ClientFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereVerifiedAt($value)
 * @mixin \Eloquent
 */
class Client extends Model
{
    use HasFactory, AppScoped;

    protected $fillable = [
        'phone',
        'name',
        'verified_at',
        'user_id',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
