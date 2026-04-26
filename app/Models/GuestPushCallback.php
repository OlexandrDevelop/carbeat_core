<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestPushCallback extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'guest_device_id',
        'token',
        'platform',
        'app',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
