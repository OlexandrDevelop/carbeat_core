<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMaintenanceTruncateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'status' => 'ok',
            'tables' => (array) ($this['tables'] ?? []),
        ];
    }
}


