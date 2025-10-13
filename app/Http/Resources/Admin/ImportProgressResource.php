<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ImportProgressResource extends JsonResource
{
    public function toArray($request): array
    {
        // Normalizes progress structure
        return [
            'status' => $this->resource['status'] ?? 'unknown',
            'imported' => (int) ($this->resource['imported'] ?? 0),
            'skipped' => (int) ($this->resource['skipped'] ?? 0),
            'processed' => (int) ($this->resource['processed'] ?? 0),
            'eta_seconds' => $this->resource['eta_seconds'] ?? null,
            'error' => $this->resource['error'] ?? null,
            'total_urls' => (int) ($this->resource['total_urls'] ?? 0),
        ];
    }
}


