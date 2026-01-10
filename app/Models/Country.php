<?php
}
    }
        return $this->hasMany(User::class);
    {
    public function users()

    }
        return $this->hasMany(Client::class);
    {
    public function clients()

    }
        return $this->hasMany(Service::class);
    {
    public function services()

    }
        return $this->hasMany(Master::class);
    {
    public function masters()

    }
        return $this->hasMany(City::class);
    {
    public function cities()

    ];
        'is_active' => 'boolean',
    protected $casts = [

    ];
        'is_active',
        'timezone',
        'locale',
        'currency',
        'phone_code',
        'name',
        'code',
    protected $fillable = [

    use HasFactory;
{
class Country extends Model

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

namespace App\Models;


