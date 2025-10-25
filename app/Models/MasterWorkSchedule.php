<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
