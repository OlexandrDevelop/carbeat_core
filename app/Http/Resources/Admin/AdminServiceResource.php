<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => (int) ($this['id'] ?? 0),
            'name'         => (string) ($this['name'] ?? ''),
            'canonical'    => (string) ($this['canonical'] ?? ''),
            'translations' => $this['translations'] ?? [],
            'providers'    => $this['providers'] ?? [],
            'all_masters'  => $this['all_masters'] ?? [],
        ];
    }
}
