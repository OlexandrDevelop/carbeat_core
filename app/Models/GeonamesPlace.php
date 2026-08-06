<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A settlement imported from a GeoNames country export (see the
 * 2026_08_06_000000_create_geonames_places_table migration and
 * database/data/geonames-ua.txt) — powers the repair-request form's city
 * autocomplete (App\Http\Controllers\RepairRequestController::citySuggestions())
 * with a fast, true prefix-matching local search.
 *
 * @property int $geoname_id
 * @property string $name
 * @property string|null $display_name
 * @property string|null $ascii_name
 * @property numeric $latitude
 * @property numeric $longitude
 * @property string|null $feature_class
 * @property string|null $country_code
 * @property int|null $population
 */
class GeonamesPlace extends Model
{
    protected $table = 'geonames_places';

    protected $primaryKey = 'geoname_id';

    public $incrementing = false;

    public $timestamps = false;
}
