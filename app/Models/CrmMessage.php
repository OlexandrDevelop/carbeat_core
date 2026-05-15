<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmMessage extends Model
{
    protected $table = 'crm_messages';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'thread_uuid',
        'direction',
        'kind',
        'body',
        'message_created_at',
    ];

    protected $casts = [
        'message_created_at' => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CrmChatThread::class, 'thread_uuid', 'uuid');
    }
}
