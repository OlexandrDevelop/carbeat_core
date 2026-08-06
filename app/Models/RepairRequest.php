<?php

namespace App\Models;

use App\Models\Traits\AppScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $app
 * @property int|null $user_id
 * @property int|null $service_id
 * @property string|null $city
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string $car_make
 * @property string|null $car_model
 * @property string|null $car_year
 * @property string $description
 * @property string $phone
 * @property string $name
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Service|null $service
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MasterRepairRequestStatus> $masterStatuses
 */
class RepairRequest extends Model
{
    use HasFactory, AppScoped;

    protected $fillable = [
        'user_id',
        'service_id',
        'city',
        'latitude',
        'longitude',
        'car_make',
        'car_model',
        'car_year',
        'description',
        'phone',
        'name',
        'status',
        'approved_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function masterStatuses(): HasMany
    {
        return $this->hasMany(MasterRepairRequestStatus::class);
    }
}
