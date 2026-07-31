<?php

namespace App\Models;

use App\Models\Traits\AppScoped;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $app
 * @property string $slug
 * @property string $title
 * @property string|null $intro
 * @property array<int, array{heading: string, body: string}>|null $sections
 * @property array<int, array{q: string, a: string}>|null $faq
 * @property string|null $related_service_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoArticle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoArticle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeoArticle query()
 * @mixin \Eloquent
 */
class SeoArticle extends Model
{
    use AppScoped;

    protected $fillable = [
        'app',
        'slug',
        'title',
        'intro',
        'sections',
        'faq',
        'related_service_name',
    ];

    protected $casts = [
        'sections' => 'array',
        'faq' => 'array',
    ];
}
