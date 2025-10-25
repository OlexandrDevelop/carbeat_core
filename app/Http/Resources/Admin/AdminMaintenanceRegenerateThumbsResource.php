<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMaintenanceRegenerateThumbsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'status' => 'ok',
            'total' => (int) ($this['total'] ?? 0),
            'queued_chunks' => (int) ($this['queued_chunks'] ?? 0),
            'chunk_size' => (int) ($this['chunk_size'] ?? 0),
        ];
    }
}


