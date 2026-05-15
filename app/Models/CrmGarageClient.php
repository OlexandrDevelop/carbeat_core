<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\AppScoped;

class CrmGarageClient extends Model
{
    use AppScoped;

    protected $table = 'crm_garage_clients';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'master_id',
        'platform_client_id',
        'name',
        'phone',
        'app',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function platformClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'platform_client_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(CrmGarageVehicle::class, 'garage_client_uuid', 'uuid');
    }
}
