<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterStatusRequest extends Model
{
    protected $fillable = [
        'driver_user_id',
        'master_id',
        'status',
        'channel',
        'answer',
        'responded_at',
        'expires_at',
        'notification_message',
        'meta',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }
}
