<?php

namespace App\Models;

use App\Models\Traits\AppScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $app
 * @property int $visit_session_id
 * @property string $type
 * @property string|null $path
 * @property string|null $full_url
 * @property string|null $referrer
 * @property string|null $element_tag
 * @property string|null $element_text
 * @property string|null $element_id
 * @property string|null $element_classes
 * @property string|null $element_href
 * @property array|null $meta
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\VisitSession $session
 */
class VisitEvent extends Model
{
    use AppScoped;

    public const UPDATED_AT = null;

    public const TYPE_PAGEVIEW = 'pageview';

    public const TYPE_CLICK = 'click';

    protected $fillable = [
        'visit_session_id',
        'type',
        'path',
        'full_url',
        'referrer',
        'element_tag',
        'element_text',
        'element_id',
        'element_classes',
        'element_href',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitSession::class, 'visit_session_id');
    }
}
