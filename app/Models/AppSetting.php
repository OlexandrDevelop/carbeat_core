<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property array<array-key, mixed>|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting whereValue($value)
 * @mixin \Eloquent
 */
class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}
