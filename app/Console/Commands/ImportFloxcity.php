<?php

namespace App\Console\Commands;

use App\Enums\AppBrand;
use App\Jobs\CreateMasterThumbnails;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ImportFloxcity extends Command
{
    protected $signature = 'import:floxcity
        {--dry-run : Do not write anything, only simulate}
        {--batch-size=500 : Number of rows to process per chunk}
        {--only= : Comma-separated list of entities to import (services,users,clients,masters,master_services,gallery,reviews)}
        {--src-driver=mysql : Source DB driver}
        {--src-host=127.0.0.1 : Source DB host}
        {--src-port=3306 : Source DB port}
        {--src-database= : Source DB name}
        {--src-username= : Source DB user}
        {--src-password= : Source DB password}
        {--src-prefix= : Source DB table prefix}
        {--src-storage-path= : Absolute base path where gallery files are stored (for local FS)}
        {--src-base-url= : Base URL to fetch gallery files via HTTP if not using local path}
    ';

    protected $description = 'Import data for FLOXCITY brand from an external, identical database and migrate masters gallery files.';

    private string $brand;
    private bool $dryRun = false;
    private int $batchSize = 500;
    private array $only = [];

    // ID maps old_id => new_id
    private array $maps = [
        'services' => [],
        'users' => [],
        'clients' => [],
        'masters' => [],
        'cities' => [],
    ];

    public function handle(): int
    {
        $this->brand = AppBrand::FLOXCITY->value;
        $this->dryRun = (bool) $this->option('dry-run');
        $this->batchSize = (int) $this->option('batch-size');
        $this->only = $this->parseOnly((string) $this->option('only'));

        $this->setupLogging();

        // Build source connection at runtime
        if (! $this->configureSourceConnection()) {
            $this->error('Failed to configure source DB connection. Provide all required --src-* options.');
            return self::FAILURE;
        }

        $this->info('Starting FLOXCITY import'.($this->dryRun ? ' (dry-run)' : ''));

        // Quick schema sanity check
        $requiredTables = [
            'services', 'users', 'clients', 'masters', 'master_services', 'master_galleries', 'reviews',
        ];
        foreach ($requiredTables as $table) {
            if (! $this->sourceHasTable($table)) {
                $this->warn("Source DB is missing table: {$table}. Skipping related steps.");
            }
        }

        // Import order matters
        $this->importServices();
        $this->importUsers();
        $this->importClients();
        $this->importMasters();
        $this->importMasterServices();
        $this->importReviews();
        $this->importGallery();

        $this->line('Saving id-maps to storage/logs for reference...');
        Log::channel('stack')->info('[floxcity-import] id-maps', $this->maps);

        $this->info('FLOXCITY import finished.');
        return self::SUCCESS;
    }

    private function parseOnly(string $only): array
    {
        if (trim($only) === '') {
            return [];
        }
        return array_values(array_filter(array_map(fn($s) => trim(strtolower($s)), explode(',', $only))));
    }

    private function setupLogging(): void
    {
        // Use default logging; important steps will also be printed to console
    }

    private function configureSourceConnection(): bool
    {
        $database = (string) $this->option('src-database');
        $username = (string) $this->option('src-username');
        $password = (string) $this->option('src-password');
        if ($database === '' || $username === '') {
            return false;
        }

        $config = [
            'driver' => (string) $this->option('src-driver'),
            'host' => (string) $this->option('src-host'),
            'port' => (string) $this->option('src-port'),
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => (string) $this->option('src-prefix'),
            'prefix_indexes' => true,
        ];

        Config::set('database.connections.floxcity_source', $config);
        try {
            DB::connection('floxcity_source')->getPdo();
            $this->info('Source DB connection established.');
            return true;
        } catch (\Throwable $e) {
            $this->error('Cannot connect to source DB: '.$e->getMessage());
            Log::error('[floxcity-import] source connect failed', ['e' => $e]);
            return false;
        }
    }

    private function shouldRun(string $entity): bool
    {
        return empty($this->only) || in_array($entity, $this->only, true);
    }

    private function sourceHasTable(string $table): bool
    {
        try {
            return DB::connection('floxcity_source')->getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function importServices(): void
    {
        if (! $this->shouldRun('services') || ! $this->sourceHasTable('services')) {
            return;
        }
        $this->task('Importing services', function () {
            $targetColumns = Schema::getColumnListing('services');
            $source = DB::connection('floxcity_source');
            $count = 0; $inserted = 0; $skipped = 0;
            $source->table('services')->orderBy('id')->chunk($this->batchSize, function ($rows) use (&$count, &$inserted, &$skipped, $targetColumns) {
                $payload = [];
                foreach ($rows as $row) {
                    $count++;
                    $data = (array) $row;
                    // Keep only intersecting columns
                    $data = Arr::only($data, $targetColumns);
                    $data['app'] = $this->brand;
                    // Deduplicate by name+brand
                    $existing = DB::table('services')->where('app', $this->brand)->where('name', $data['name'] ?? null)->first();
                    if ($existing) {
                        $this->maps['services'][$row->id] = $existing->id;
                        $skipped++;
                        continue;
                    }
                    if (! $this->dryRun) {
                        $newId = DB::table('services')->insertGetId(Arr::except($data, ['id']));
                        $this->maps['services'][$row->id] = $newId;
                    }
                    $inserted++;
                }
                $this->line("  processed: {$count}, inserted: {$inserted}, skipped: {$skipped}");
            });
            return true;
        });
    }

    private function importUsers(): void
    {
        if (! $this->shouldRun('users') || ! $this->sourceHasTable('users')) {
            return;
        }
        $this->task('Importing users', function () {
            $targetColumns = Schema::getColumnListing('users');
            $source = DB::connection('floxcity_source');
            $count = 0; $inserted = 0; $matched = 0;
            $source->table('users')->orderBy('id')->chunk($this->batchSize, function ($rows) use (&$count, &$inserted, &$matched, $targetColumns) {
                foreach ($rows as $row) {
                    $count++;
                    $src = (array) $row;
                    $src = Arr::only($src, $targetColumns);
                    $src['app'] = $this->brand;
                    // match ONLY by phone+brand if phone present
                    $existing = null;
                    if (!empty($src['phone'] ?? null)) {
                        $existing = DB::table('users')
                            ->where('app', $this->brand)
                            ->where('phone', $src['phone'])
                            ->first();
                    }
                    if ($existing) {
                        $this->maps['users'][$row->id] = $existing->id;
                        $matched++;
                        continue;
                    }
                    if (! $this->dryRun) {
                        $newId = DB::table('users')->insertGetId(Arr::except($src, ['id', 'password']));
                        $this->maps['users'][$row->id] = $newId;
                    }
                    $inserted++;
                }
                $this->line("  processed: {$count}, inserted: {$inserted}, matched: {$matched}");
            });
            return true;
        });
    }

    private function importClients(): void
    {
        if (! $this->shouldRun('clients') || ! $this->sourceHasTable('clients')) {
            return;
        }
        $this->task('Importing clients', function () {
            $targetColumns = Schema::getColumnListing('clients');
            $source = DB::connection('floxcity_source');
            $count = 0; $inserted = 0; $matched = 0;
            $source->table('clients')->orderBy('id')->chunk($this->batchSize, function ($rows) use (&$count, &$inserted, &$matched, $targetColumns) {
                foreach ($rows as $row) {
                    $count++;
                    $src = (array) $row;
                    $src = Arr::only($src, $targetColumns);
                    $src['app'] = $this->brand;
                    // match by phone+brand if column exists
                    $query = DB::table('clients')->where('app', $this->brand);
                    if (array_key_exists('phone', $src) && $src['phone']) {
                        $query->where('phone', $src['phone']);
                    }
                    $existing = $query->first();
                    if ($existing) {
                        $this->maps['clients'][$row->id] = $existing->id;
                        $matched++;
                        continue;
                    }
                    if (! $this->dryRun) {
                        $newId = DB::table('clients')->insertGetId(Arr::except($src, ['id']));
                        $this->maps['clients'][$row->id] = $newId;
                    }
                    $inserted++;
                }
                $this->line("  processed: {$count}, inserted: {$inserted}, matched: {$matched}");
            });
            return true;
        });
    }

    private function importMasters(): void
    {
        if (! $this->shouldRun('masters') || ! $this->sourceHasTable('masters')) {
            return;
        }
        $this->task('Importing masters', function () {
            $targetColumns = Schema::getColumnListing('masters');
            $source = DB::connection('floxcity_source');
            $count = 0; $inserted = 0; $matched = 0;
            $source->table('masters')->orderBy('id')->chunk($this->batchSize, function ($rows) use (&$count, &$inserted, &$matched, $targetColumns) {
                foreach ($rows as $row) {
                    $count++;
                    $src = (array) $row;
                    $src = Arr::only($src, $targetColumns);
                    $src['app'] = $this->brand;
                    // Map primary service if we imported services
                    if (isset($src['service_id']) && $src['service_id']) {
                        $src['service_id'] = $this->maps['services'][$src['service_id']] ?? $src['service_id'];
                    }
                    // Resolve city by NAME from source, not by id
                    if (!empty($src['city_id'])) {
                        $resolvedCityId = $this->maps['cities'][$src['city_id']] ?? $this->resolveCityIdFromSource((int) $src['city_id']);
                        $src['city_id'] = $resolvedCityId ?: null;
                    }
                    // Deduplicate by global slug first (unique index), then by contact_phone within brand.
                    if (!empty($src['slug'] ?? null)) {
                        $existingBySlug = DB::table('masters')->where('slug', $src['slug'])->first();
                        if ($existingBySlug) {
                            // Treat as matched and map to existing master to keep relations consistent
                            $this->maps['masters'][$row->id] = $existingBySlug->id;
                            $matched++;
                            continue;
                        }
                    }
                    if (!empty($src['contact_phone'] ?? null)) {
                        $existingByPhone = DB::table('masters')
                            ->where('app', $this->brand)
                            ->where('contact_phone', $src['contact_phone'])
                            ->first();
                        if ($existingByPhone) {
                            $this->maps['masters'][$row->id] = $existingByPhone->id;
                            $matched++;
                            continue;
                        }
                    }
                    if (! $this->dryRun) {
                        $newId = DB::table('masters')->insertGetId(Arr::except($src, ['id']));
                        $this->maps['masters'][$row->id] = $newId;
                    }
                    $inserted++;
                }
                $this->line("  processed: {$count}, inserted: {$inserted}, matched: {$matched}");
            });
            return true;
        });
    }

    private function importMasterServices(): void
    {
        if (! $this->shouldRun('master_services') || ! $this->sourceHasTable('master_services')) {
            return;
        }
        $this->task('Importing master_services (pivot)', function () {
            $source = DB::connection('floxcity_source');
            $count = 0; $inserted = 0; $skipped = 0;
            $source->table('master_services')->orderBy('master_id')->chunk($this->batchSize, function ($rows) use (&$count, &$inserted, &$skipped) {
                $batch = [];
                foreach ($rows as $row) {
                    $count++;
                    $oldMaster = $row->master_id; $oldService = $row->service_id;
                    $newMaster = $this->maps['masters'][$oldMaster] ?? null;
                    $newService = $this->maps['services'][$oldService] ?? null;
                    if (!$newMaster || !$newService) { $skipped++; continue; }
                    $batch[] = [
                        'master_id' => $newMaster,
                        'service_id' => $newService,
                    ];
                }
                if (! $this->dryRun && !empty($batch)) {
                    // Avoid duplicates
                    $batch = collect($batch)->unique(fn($r) => $r['master_id'].'-'.$r['service_id'])->values()->all();
                    DB::table('master_services')->insertOrIgnore($batch);
                }
                $inserted += count($batch);
                $this->line("  processed: {$count}, inserted: {$inserted}, skipped: {$skipped}");
            });
            return true;
        });
    }

    private function importReviews(): void
    {
        if (! $this->shouldRun('reviews') || ! $this->sourceHasTable('reviews')) {
            return;
        }
        $this->task('Importing reviews', function () {
            $targetColumns = Schema::getColumnListing('reviews');
            $source = DB::connection('floxcity_source');
            $count = 0; $inserted = 0; $skipped = 0;
            $source->table('reviews')->orderBy('id')->chunk($this->batchSize, function ($rows) use (&$count, &$inserted, &$skipped, $targetColumns) {
                $batch = [];
                foreach ($rows as $row) {
                    $count++;
                    $src = (array) $row;
                    $src = Arr::only($src, $targetColumns);
                    $src['app'] = $this->brand;
                    // Map FKs
                    if (isset($src['master_id'])) {
                        $newMid = $this->maps['masters'][$src['master_id']] ?? $this->resolveMasterIdFromSource((int) $src['master_id']);
                        if (!$newMid) { $skipped++; continue; }
                        $src['master_id'] = $newMid;
                    }
                    if (isset($src['user_id'])) {
                        $newUid = $this->maps['users'][$src['user_id']] ?? 1;
                        $src['user_id'] = $newUid; // nullable allowed
                    }
                    $batch[] = Arr::except($src, ['id']);
                }
                if (! $this->dryRun && !empty($batch)) {
                    // Be tolerant: skip rows that still fail constraints (e.g., FK) instead of aborting the whole batch
                    DB::table('reviews')->insertOrIgnore($batch);
                }
                $inserted += count($batch);
                $this->line("  processed: {$count}, inserted: {$inserted}, skipped: {$skipped}");
            });
            return true;
        });
    }

    private function importGallery(): void
    {
        if (! $this->shouldRun('gallery') || ! $this->sourceHasTable('master_galleries')) {
            return;
        }
        $this->task('Importing master galleries (files + DB)', function () {
            $source = DB::connection('floxcity_source');
            $targetColumns = Schema::getColumnListing('master_galleries');
            $basePath = (string) $this->option('src-storage-path');
            $baseUrl = (string) $this->option('src-base-url');
            $useHttp = $baseUrl !== '';

            $count = 0; $inserted = 0; $skipped = 0; $fileErrors = 0;
            $affectedMasters = [];

            $source->table('master_galleries')->orderBy('id')->chunk($this->batchSize, function ($rows) use (&$count, &$inserted, &$skipped, &$fileErrors, &$affectedMasters, $targetColumns, $basePath, $baseUrl, $useHttp) {
                foreach ($rows as $row) {
                    $count++;
                    $oldMaster = $row->master_id;
                    $newMaster = $this->maps['masters'][$oldMaster] ?? $this->resolveMasterIdFromSource((int) $oldMaster);
                    if (! $newMaster) { $skipped++; continue; }

                    $srcPath = (string) $row->photo; // relative path in source
                    $contents = null;
                    if ($this->dryRun) {
                        $contents = 'skip';
                    } else {
                        try {
                            if ($useHttp) {
                                $url = rtrim($baseUrl, '/').'/'.ltrim($srcPath, '/');
                                $contents = @file_get_contents($url);
                            } else {
                                $full = rtrim($basePath, '/').'/'.ltrim($srcPath, '/');
                                $contents = @file_get_contents($full);
                            }
                        } catch (\Throwable $e) {
                            $contents = false;
                        }
                    }

                    if ($contents === false || $contents === null) {
                        $fileErrors++;
                        Log::warning('[floxcity-import] gallery file not found', ['photo' => $row->photo]);
                        continue;
                    }

                    // Save under public/images/imports/{hash or uniq}
                    $ext = pathinfo($srcPath, PATHINFO_EXTENSION) ?: 'jpg';
                    $newRel = 'images/imports/'.uniqid('mg_', true).'.'.$ext;
                    if (! $this->dryRun) {
                        Storage::disk('public')->put($newRel, $contents);
                    }

                    // Insert DB row
                    $payload = Arr::only((array) $row, $targetColumns);
                    $payload['master_id'] = $newMaster;
                    $payload['photo'] = $newRel;
                    if (! $this->dryRun) {
                        DB::table('master_galleries')->insert(Arr::only($payload, ['master_id', 'photo']));
                    }
                    $affectedMasters[$newMaster] = true;
                    $inserted++;
                }
                $this->line("  processed: {$count}, inserted: {$inserted}, skipped: {$skipped}, fileErrors: {$fileErrors}");
            });

            // Generate thumbnails for affected masters
            if (! $this->dryRun && !empty($affectedMasters)) {
                try {
                    (new CreateMasterThumbnails(array_keys($affectedMasters)))->handle();
                } catch (\Throwable $e) {
                    Log::warning('[floxcity-import] thumbnail generation failed', ['e' => $e->getMessage()]);
                }
            }

            return true;
        });
    }

    /**
     * Resolve and cache target master id by looking up source master and matching by slug/app or contact_phone/app.
     */
    private function resolveMasterIdFromSource(int $oldMasterId): ?int
    {
        // Already mapped?
        if (isset($this->maps['masters'][$oldMasterId])) {
            return (int) $this->maps['masters'][$oldMasterId];
        }

        try {
            $srcMaster = DB::connection('floxcity_source')
                ->table('masters')
                ->select(['id', 'slug', 'contact_phone'])
                ->where('id', $oldMasterId)
                ->first();
        } catch (\Throwable $e) {
            $srcMaster = null;
        }

        if (! $srcMaster) {
            return null;
        }

        // Try match by global slug (slug is unique globally)
        $candidateId = null;
        if (!empty($srcMaster->slug)) {
            $candidateId = DB::table('masters')
                ->where('slug', $srcMaster->slug)
                ->value('id');
        }
        // Fallback: match by contact_phone within brand
        if (!$candidateId && !empty($srcMaster->contact_phone)) {
            $candidateId = DB::table('masters')
                ->where('app', $this->brand)
                ->where('contact_phone', $srcMaster->contact_phone)
                ->value('id');
        }

        if ($candidateId) {
            $this->maps['masters'][$oldMasterId] = (int) $candidateId;
            return (int) $candidateId;
        }

        return null;
    }

    /**
     * Resolve and cache target city id by looking up source city name and matching by name (global unique).
     */
    private function resolveCityIdFromSource(int $oldCityId): ?int
    {
        // Already mapped?
        if (isset($this->maps['cities'][$oldCityId])) {
            return (int) $this->maps['cities'][$oldCityId];
        }

        try {
            $srcCity = DB::connection('floxcity_source')
                ->table('cities')
                ->select(['id', 'name'])
                ->where('id', $oldCityId)
                ->first();
        } catch (\Throwable $e) {
            $srcCity = null;
        }

        if (! $srcCity || empty($srcCity->name)) {
            return null;
        }

        // Match by name (cities.name is globally unique in target schema)
        $candidateId = DB::table('cities')->where('name', trim((string) $srcCity->name))->value('id');

        if ($candidateId) {
            $this->maps['cities'][$oldCityId] = (int) $candidateId;
            return (int) $candidateId;
        }

        return null;
    }

    private function task(string $title, \Closure $callback): void
    {
        $this->info($title.'...');
        try {
            DB::beginTransaction();
            $result = $callback();
            if ($this->dryRun) {
                DB::rollBack();
                $this->line('  [dry-run] rolled back.');
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('  failed: '.$e->getMessage());
            Log::error('[floxcity-import] task failed', ['title' => $title, 'e' => $e]);
        }
    }
}
