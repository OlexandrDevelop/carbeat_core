<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Country;

class SameCountry implements Rule
{
    protected $modelClass;
    protected $otherId;

    public function __construct(string $modelClass, $otherId)
    {
        $this->modelClass = $modelClass;
        $this->otherId = $otherId;
    }

    public function passes($attribute, $value)
    {
        $model = ($this->modelClass)::find($value);
        $other = ($this->modelClass)::find($this->otherId);

        if (!$model || !$other) {
            return false;
        }

        return $model->country_id === $other->country_id;
    }

    public function message()
    {
        return 'Resource must belong to the same country.';
    }
}

