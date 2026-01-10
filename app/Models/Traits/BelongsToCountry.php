<?php
namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Country;
use App\Models\Traits\CountryScoped;

trait BelongsToCountry
{
    /**
     * Country relation helper.
     *
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Apply the global country scope for models using this trait.
     */
    protected static function bootBelongsToCountry(): void
    {
        // Apply CountryScoped so queries automatically filter by request country when available
        static::addGlobalScope(new CountryScoped());
    }

    /**
     * Assert that this model and the given model belong to the same country.
     * Throws InvalidArgumentException when country_id is missing or differs.
     *
     * @param Model $other
     * @return void
     * @throws \InvalidArgumentException
     */
    public function assertSameCountry(Model $other): void
    {
        $left = $this->country_id ?? null;
        $right = $other->country_id ?? null;

        if ($left === null || $right === null) {
            throw new \InvalidArgumentException('Country ID missing for comparison.');
        }

        if ($left !== $right) {
            throw new \InvalidArgumentException('Cross-country relation is not allowed.');
        }
    }
}
