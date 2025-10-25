<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ImportStartResponse extends JsonResource
{
    public function toArray($request): array
    {
        // $this->resource is an array like ['jobs' => [ ['job_id' => '...', 'url' => '...'], ... ]]
        return $this->resource;
    }
}


