<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\AppScoped;

class CrmGarageVehicle extends Model
{
    use AppScoped;

    protected $table = 'crm_garage_vehicles';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'master_id',
        'garage_client_uuid',
        'model_name',
        'plate_number',
        'app',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function garageClient(): BelongsTo
    {
        return $this->belongsTo(CrmGarageClient::class, 'garage_client_uuid', 'uuid');
    }
}
