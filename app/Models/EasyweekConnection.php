<?php

namespace App\Models;

use App\Models\Traits\AppScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EasyweekConnection extends Model
{
    use AppScoped;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ERROR = 'error';

    public const MAX_CONSECUTIVE_FAILURES = 5;

    protected $fillable = [
        'master_id',
        'app',
        'easyweek_company_slug',
        'easyweek_company_id',
        'primary_branch_id',
        'primary_product_id',
        'timezone',
        'sync_status',
        'last_synced_at',
        'last_availability_synced_at',
        'last_error',
        'consecutive_failures',
        'failure_notified_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'last_availability_synced_at' => 'datetime',
        'failure_notified_at' => 'datetime',
        'consecutive_failures' => 'integer',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }
}
