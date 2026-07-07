<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportRun extends Model
{
    protected $fillable = [
        'job_id',
        'source',
        'url',
        'app',
        'status',
        'total_urls',
        'imported_count',
        'matched_count',
        'skipped_count',
        'error',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function masters(): HasMany
    {
        return $this->hasMany(ImportRunMaster::class);
    }
}
