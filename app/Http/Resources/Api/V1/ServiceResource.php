<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $name
 * @property mixed $masters
 */
class ServiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->tempTranslateUkraineMap($this->name),
            'masters_count' => $this->masters_count ?? 0,
        ];
    }

    public function tempTranslateUkraineMap(String $name)
    {
        $map = [
            'barbershop'        => 'Барбершоп',
            'hairdresser_women' => 'Жіночий перукар',
            'hairdresser_unisex'=> 'Стрижка',
        ];
        return $map[$name] ?? $name;
    }
}
