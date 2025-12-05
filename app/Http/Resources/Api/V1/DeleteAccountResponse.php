<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class DeleteAccountResponse extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'status' => 'ok',
            'message' => 'Account deleted',
        ];
    }
}





