<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the live Nominatim/Photon city-search dependency for the
 * repair-request form's autocomplete (App\Http\Controllers\RepairRequestController::citySuggestions())
 * with a fully local, comprehensive settlement list — imported once, at
 * deploy time, from a GeoNames country export (database/data/geonames-ua.txt,
 * tab-separated, no header, standard 19-column GeoNames dump format:
 * https://download.geonames.org/export/dump/). A plain SQL `LIKE 'query%'`
 * against this table is a true prefix match (unlike Nominatim's word-only
 * search) and needs no external service at request time.
 */
return new class extends Migration
{
    private const BATCH_SIZE = 2000;

    public function up(): void
    {
        Schema::create('geonames_places', function (Blueprint $table) {
            $table->unsignedBigInteger('geoname_id')->primary();
            $table->string('name', 200);
            $table->string('ascii_name', 200)->nullable();
            $table->text('alternate_names')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->char('feature_class', 1)->nullable();
            $table->string('feature_code', 10)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('cc2', 200)->nullable();
            $table->string('admin1_code', 20)->nullable();
            $table->string('admin2_code', 80)->nullable();
            $table->string('admin3_code', 20)->nullable();
            $table->string('admin4_code', 20)->nullable();
            $table->unsignedBigInteger('population')->nullable();
            $table->integer('elevation')->nullable();
            $table->integer('dem')->nullable();
            $table->string('timezone', 40)->nullable();
            $table->date('modification_date')->nullable();

            $table->index('name');
            $table->index('country_code');
        });

        $this->importFromFile(database_path('data/geonames-ua.txt'));
    }

    public function down(): void
    {
        Schema::dropIfExists('geonames_places');
    }

    private function importFromFile(string $path): void
    {
        if (! is_readable($path)) {
            // Don't fail the whole deploy over a missing fixture — the table
            // just stays empty and city autocomplete degrades gracefully.
            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return;
        }

        $batch = [];

        while (($line = fgets($handle)) !== false) {
            $fields = explode("\t", rtrim($line, "\n"));
            if (count($fields) < 19) {
                continue;
            }

            $batch[] = [
                'geoname_id' => (int) $fields[0],
                'name' => $fields[1],
                'ascii_name' => $fields[2] !== '' ? $fields[2] : null,
                'alternate_names' => $fields[3] !== '' ? $fields[3] : null,
                'latitude' => $fields[4],
                'longitude' => $fields[5],
                'feature_class' => $fields[6] !== '' ? $fields[6] : null,
                'feature_code' => $fields[7] !== '' ? $fields[7] : null,
                'country_code' => $fields[8] !== '' ? $fields[8] : null,
                'cc2' => $fields[9] !== '' ? $fields[9] : null,
                'admin1_code' => $fields[10] !== '' ? $fields[10] : null,
                'admin2_code' => $fields[11] !== '' ? $fields[11] : null,
                'admin3_code' => $fields[12] !== '' ? $fields[12] : null,
                'admin4_code' => $fields[13] !== '' ? $fields[13] : null,
                'population' => $fields[14] !== '' ? (int) $fields[14] : null,
                'elevation' => $fields[15] !== '' ? (int) $fields[15] : null,
                'dem' => $fields[16] !== '' ? (int) $fields[16] : null,
                'timezone' => $fields[17] !== '' ? $fields[17] : null,
                'modification_date' => $fields[18] !== '' ? $fields[18] : null,
            ];

            if (count($batch) >= self::BATCH_SIZE) {
                DB::table('geonames_places')->insertOrIgnore($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('geonames_places')->insertOrIgnore($batch);
        }

        fclose($handle);
    }
};
