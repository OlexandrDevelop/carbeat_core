<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\AppScoped;

class CrmChatThread extends Model
{
    use AppScoped;

    protected $table = 'crm_chat_threads';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'master_id',
        'garage_client_uuid',
        'customer_name',
        'car_model',
        'plate_number',
        'last_message_preview',
        'unread_count',
        'has_photo_request',
        'thread_updated_at',
        'app',
    ];

    protected $casts = [
        'unread_count' => 'integer',
        'has_photo_request' => 'boolean',
        'thread_updated_at' => 'datetime',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CrmMessage::class, 'thread_uuid', 'uuid');
    }
}
