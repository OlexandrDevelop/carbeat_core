<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\AppScoped;

class MasterBay extends Model
{
    use AppScoped;

    protected $table = 'master_bays';

    protected $fillable = [
        'uuid',
        'master_id',
        'title',
        'technician_name',
        'is_active',
        'display_order',
        'status',
        'app',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_photo_request' => 'boolean',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'bay_id');
    }
}
