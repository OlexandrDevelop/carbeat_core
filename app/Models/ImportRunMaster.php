<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRunMaster extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_run_id',
        'master_id',
        'city_id',
        'master_name',
        'city_name',
        'status',
        'skip_reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function importRun(): BelongsTo
    {
        return $this->belongsTo(ImportRun::class);
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
