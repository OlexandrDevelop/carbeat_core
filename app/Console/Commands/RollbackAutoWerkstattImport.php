<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RollbackAutoWerkstattImport extends Command
{
    protected $signature = 'import:rollback-auto-werkstatt
        {--dry-run : Show what would be deleted without touching the DB}
        {--force : Skip confirmation prompt}
        {--keep-cities : Do not delete orphaned German cities}
    ';

    protected $description = 'Delete all masters imported from auto-werkstatt.de (place_id LIKE "auto-werkstatt:%") and optionally their orphaned cities';

    public function handle(): int
    {
        $dryRun     = (bool) $this->option('dry-run');
        $keepCities = (bool) $this->option('keep-cities');

        $ids = DB::table('masters')
            ->where('place_id', 'like', 'auto-werkstatt:%')
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->info('Keine auto-werkstatt.de Einträge gefunden.');
            return self::SUCCESS;
        }

        $reviewCount = DB::table('reviews')->whereIn('master_id', $ids)->count();

        // Orphaned German cities: country_code='de' and not referenced by any other master
        $cityIds = DB::table('masters')
            ->where('place_id', 'like', 'auto-werkstatt:%')
            ->whereNotNull('city_id')
            ->pluck('city_id')
            ->unique();

        $orphanCityIds = $keepCities ? collect() : $cityIds->filter(function (int $cityId) use ($ids) {
            // Keep city if referenced by masters outside this import batch
            return ! DB::table('masters')
                ->where('city_id', $cityId)
                ->whereNotIn('id', $ids)
                ->exists();
        })->filter(function (int $cityId) {
            // Only delete cities explicitly tagged as German
            return DB::table('cities')->where('id', $cityId)->where('country_code', 'de')->exists();
        });

        $this->line(sprintf(
            'Gefunden: %d Master, %d Bewertungen, %d verwaiste DE-Städte%s',
            $ids->count(),
            $reviewCount,
            $orphanCityIds->count(),
            $dryRun ? ' [dry-run, keine Änderungen]' : '',
        ));

        if ($dryRun) {
            if ($orphanCityIds->isNotEmpty()) {
                $names = DB::table('cities')->whereIn('id', $orphanCityIds)->pluck('name');
                $this->line('Städte die gelöscht würden: ' . $names->implode(', '));
            }
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Wirklich löschen?', false)) {
            $this->line('Abgebrochen.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($ids, $orphanCityIds) {
            DB::table('reviews')->whereIn('master_id', $ids)->delete();
            DB::table('masters')->whereIn('id', $ids)->delete();
            if ($orphanCityIds->isNotEmpty()) {
                DB::table('cities')->whereIn('id', $orphanCityIds)->delete();
            }
        });

        $this->info(sprintf(
            'Fertig. %d Master%s gelöscht.',
            $ids->count(),
            $orphanCityIds->isNotEmpty() ? ' und ' . $orphanCityIds->count() . ' Städte' : '',
        ));

        return self::SUCCESS;
    }
}
