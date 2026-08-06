<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $master_id
 * @property int $repair_request_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $called_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property-read \App\Models\Master $master
 * @property-read \App\Models\RepairRequest $repairRequest
 */
class MasterRepairRequestStatus extends Model
{
    protected $fillable = [
        'master_id',
        'repair_request_id',
        'status',
        'called_at',
        'rejected_at',
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function repairRequest(): BelongsTo
    {
        return $this->belongsTo(RepairRequest::class);
    }
}
