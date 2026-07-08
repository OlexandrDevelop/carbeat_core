<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * A plain `'value' => 'array'` cast (Laravel's default `json_encode`) escapes
     * non-ASCII characters as `\uXXXX`. Any non-Latin content stored here (e.g. the
     * Ukrainian SEO overrides in `seo_content_overrides_{brand}`) then produces a
     * SQL binding packed with backslashes — which crashes Laravel Telescope's
     * `QueryWatcher::replaceBindings()` (it substitutes bound values into the logged
     * SQL via `preg_replace()`, and a literal backslash in the *replacement* string
     * is treated as an escape/backreference sequence there, not literal text). Storing
     * raw UTF-8 instead of `\uXXXX` escapes avoids that entirely.
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : json_decode($value, true),
            set: fn (?array $value) => $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }
}
