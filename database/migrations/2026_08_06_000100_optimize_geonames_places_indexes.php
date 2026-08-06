<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The city-suggestions query (App\Http\Controllers\RepairRequestController::citySuggestions())
 * filters by `country_code` and `feature_class` (equality) then `name`
 * (LIKE 'query%' prefix range) — MySQL can only use a composite index
 * efficiently for a trailing range scan if the equality columns come
 * first, so replace the separate single-column indexes from the create
 * migration with one composite index matching that exact access pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('geonames_places', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['country_code']);
            $table->index(['country_code', 'feature_class', 'name'], 'geonames_places_search_index');
        });
    }

    public function down(): void
    {
        Schema::table('geonames_places', function (Blueprint $table) {
            $table->dropIndex('geonames_places_search_index');
            $table->index('name');
            $table->index('country_code');
        });
    }
};
