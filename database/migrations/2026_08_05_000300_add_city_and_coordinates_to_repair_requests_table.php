<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Driver-entered city (picked from the self-hosted Nominatim instance —
 * see App\Http\Services\Import\NominatimGeocoder::search()) plus its
 * coordinates, used to match the request to masters within a 50km radius
 * (App\Http\Services\RepairRequestNotificationService), the same
 * Haversine approach as App\Http\Services\Master\MasterSearchService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_requests', function (Blueprint $table) {
            $table->string('city')->nullable()->after('service_id');
            $table->decimal('latitude', 10, 7)->nullable()->after('city');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('repair_requests', function (Blueprint $table) {
            $table->dropColumn(['city', 'latitude', 'longitude']);
        });
    }
};
