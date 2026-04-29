<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Traits\AppScoped;

/**
 * @property int $id
 * @property string $app
 * @property string|null $place_id
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $contact_phone
 * @property string $name
 * @property int $age
 * @property int $service_id
 * @property int|null $city_id
 * @property numeric $longitude
 * @property numeric $latitude
 * @property string $description
 * @property string|null $address
 * @property array<array-key, mixed>|null $working_hours
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $status_expires_at
 * @property bool $is_premium
 * @property \Illuminate\Support\Carbon|null $premium_until
 * @property bool $is_claimed
 * @property string|null $claim_token
 * @property \Illuminate\Support\Carbon|null $phone_verified_at
 * @property string $photo
 * @property bool $main_thumb_generated
 * @property string|null $main_thumb_url
 * @property string|null $slug
 * @property int $sms_invites_sent
 * @property float|null $rating_google
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \App\Models\City|null $city
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MasterGallery> $gallery
 * @property-read int|null $gallery_count
 * @property-read string|null $main_photo
 * @property string|null $phone
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Service> $services
 * @property-read int|null $services_count
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\MasterFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereClaimToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereIsClaimed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereIsPremium($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereMainThumbGenerated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereMainThumbUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master wherePhoneVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master wherePlaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master wherePremiumUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereRatingGoogle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Master whereWorkingHours($value)
 * @mixin \Eloquent
 */
class Master extends Model
{
    use HasFactory, AppScoped;

    protected $hidden = [
        'created_at',
        'updated_at',
        'password',
        'photo',
        'claim_token',
    ];

    protected $casts = [
        'rating' => 'float',
        'experience' => 'integer',
        'available' => 'boolean',
        'age' => 'integer',
        'reviews_count' => 'integer',
        'working_hours' => 'array',
        'extra_info' => 'array',
        'status_expires_at' => 'datetime',
        'main_thumb_generated' => 'boolean',
        'sms_invites_sent' => 'integer',
        'is_premium' => 'boolean',
        'premium_until' => 'datetime',
        'is_claimed' => 'boolean',
        'phone_verified_at' => 'datetime',
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
        'sms_invites_sent',
        'user_id',
        'place_id',
        'rating_google',
        'rating',
        'experience',
        'available',
        'city',
        'reviews_count',
        'working_hours',
        'extra_info',
        'status',
        'status_expires_at',
        'is_premium',
        'premium_until',
        'is_claimed',
        'claim_token',
        'phone_verified_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Master $master) {
            if (empty($master->claim_token)) {
                $master->claim_token = Str::random(40);
            }

            if ($master->is_claimed === null) {
                $master->is_claimed = false;
            }
        });
    }

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
