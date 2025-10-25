<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

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
