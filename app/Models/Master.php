<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Master extends Model
{
    use HasFactory;

    protected $hidden = [
        'created_at',
        'updated_at',
        'password',
        'photo',
    ];

    protected $casts = [
        'rating' => 'float',
        'experience' => 'integer',
        'available' => 'boolean',
        'age' => 'integer',
        'reviews_count' => 'integer',
        'working_hours' => 'array',
        'main_thumb_generated' => 'boolean',
        'is_premium' => 'boolean',
        'premium_until' => 'datetime',
        // 'address' => 'json',
        // 'phone' => CustomRawPhoneNumberCast::class.':INTERNATIONAL',
    ];

    protected $fillable = [
        'name',
        'password',
        'contact_phone',
        'address',
        'latitude',
        'longitude',
        'description',
        'age',
        'photo',
        'main_photo',
        'main_thumb_generated',
        'main_thumb_url',
        'service_id',
        'city_id',
        'slug',
        'user_id',
        'place_id',
        'rating_google',
        'rating',
        'experience',
        'available',
        'city',
        'reviews_count',
        'working_hours',
        'is_premium',
        'premium_until',
    ];

    // Virtual attribute to keep backward compatibility
    protected $appends = ['phone', 'main_photo'];

    // Mutator: allow setting phone
    public function setPhoneAttribute($value): void
    {
        $this->attributes['contact_phone'] = $value;
    }

    // Accessor: provide phone
    public function getPhoneAttribute(): ?string
    {
        return $this->contact_phone ?? ($this->user->phone ?? null);
    }

    // Accessor: provide main_photo
    public function getMainPhotoAttribute(): ?string
    {
        return $this->photo ?? '/images/default-master.jpg';
    }

    public function getIsPremiumAttribute($value): bool
    {
        $until = $this->premium_until;
        if ($until instanceof Carbon) {
            return $until->isFuture();
        }

        return (bool) $value;
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'master_services');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(MasterGallery::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Tariff relation removed in favor of is_premium / premium_until flags

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
