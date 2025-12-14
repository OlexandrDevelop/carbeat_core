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
use Throwable;
use Illuminate\Support\Carbon;

class ImportFloxcity extends Command
{
    protected $signature = 'import:floxcity
        {--dry-run : Do not write anything, only simulate}
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
        // Hardcoded defaults as per one-time command requirements
        $this->batchSize = 500;
        $this->only = [];

        $this->setupLogging();

        // Move existing images and thumbnails into carbeat flavor folders
        $this->moveExistingFilesToCarbeat();

        // Build source connection at runtime (hardcoded config)
        if (! $this->configureSourceConnection()) {
            $this->error('Failed to configure source DB connection.');
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

    private function setupLogging(): void
    {
        // Use default logging; important steps will also be printed to console
    }

    private function configureSourceConnection(): bool
    {
        // Hardcoded source DB config for one-time import
        $config = [
            'driver' => 'mysql',
            'host' => '185.233.45.137',
            'port' => 3398,
            'database' => 'flox_city',
            'username' => 'flox',
            'password' => 'DatumSoft1C1!',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
        ];

        Config::set('database.connections.floxcity_source', $config);
        try {
            DB::connection('floxcity_source')->getPdo();
            $this->info('Source DB connection established.');
            return true;
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
                    // Preserve timestamps from source pivot if available, otherwise use now()
                    $created = isset($row->created_at) && $row->created_at ? $row->created_at : Carbon::now();
                    $updated = isset($row->updated_at) && $row->updated_at ? $row->updated_at : $created;
                    $batch[] = [
                        'master_id' => $newMaster,
                        'service_id' => $newService,
                        'created_at' => $created,
                        'updated_at' => $updated,
                    ];
                }
                if (! $this->dryRun && !empty($batch)) {
                    // Avoid duplicates
                    $batch = collect($batch)->unique(fn($r) => $r['master_id'].'-'.$r['service_id'])->values()->all();
                    DB::table('master_services')->insertOrIgnore($batch);
                }
                $inserted += count($batch);
                $this->line("  processed: {$count}, inserted: {$inserted}, skipped: {$skipped}");
                Log::info('[floxcity-import][master_services] chunk summary', ['processed' => $count, 'inserted' => $inserted, 'skipped' => $skipped]);
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
        $this->task('Importing master galleries (DB only, files must be copied manually)', function () {
            $source = DB::connection('floxcity_source');
            $targetColumns = Schema::getColumnListing('master_galleries');

            $count = 0; $inserted = 0; $skipped = 0;

            $source->table('master_galleries')->orderBy('id')->chunk($this->batchSize, function ($rows) use (&$count, &$inserted, &$skipped, $targetColumns) {
                foreach ($rows as $row) {
                    $count++;

                    $oldMaster = $row->master_id;
                    $newMaster = $this->maps['masters'][$oldMaster] ?? $this->resolveMasterIdFromSource((int) $oldMaster);

                    // Only insert DB row if we resolved a master mapping
                    if ($newMaster) {
                        // NOTE: photo path must be set manually when files are copied
                        // For now, we just track that this gallery record needs to be created
                        $payload = Arr::only((array) $row, $targetColumns);
                        $payload['master_id'] = $newMaster;
                        // Keep original photo path as reference; you'll update it manually after copying files
                        // Or set it to null/placeholder for now
                        $payload['photo'] = $payload['photo'] ?? null; // Keep source path as placeholder

                        $created = $payload['created_at'] ?? Carbon::now();
                        $updated = $payload['updated_at'] ?? $created;
                        $insertRow = [
                            'master_id' => $newMaster,
                            'photo' => $payload['photo'], // Will need manual update after file copy
                            'created_at' => $created,
                            'updated_at' => $updated,
                        ];
                        try {
                            DB::table('master_galleries')->insert($insertRow);
                            $inserted++;
                        } catch (Throwable $e) {
                            $skipped++;
                            Log::error('[floxcity-import] failed to insert gallery row', ['e' => $e->getMessage(), 'master_id' => $newMaster, 'source_photo' => $row->photo]);
                            continue;
                        }
                    } else {
                        $skipped++;
                        Log::info('[floxcity-import] skipped gallery (no master mapping)', ['old_master' => $oldMaster, 'source_photo' => $row->photo]);
                    }
                }
                $this->line("  processed: {$count}, inserted: {$inserted}, skipped: {$skipped}");
                Log::info('[floxcity-import][gallery] chunk summary', ['processed' => $count, 'inserted' => $inserted, 'skipped' => $skipped]);
            });

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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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

    /**
     * @throws Throwable
     */
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
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('  failed: '.$e->getMessage());
            Log::error('[floxcity-import] task failed', ['title' => $title, 'e' => $e]);
        }
    }

    /**
     * Move existing master photos and thumbnails into carbeat-flavor folders.
     * This ensures legacy files live under a flavor-specific path before we start importing.
     */
    private function moveExistingFilesToCarbeat(): void
    {
        $this->info('Rehoming existing master photos and thumbnails into carbeat flavor folders...');
        $disk = Storage::disk('public');
        $moved = 0; $thumbsMoved = 0; $errors = 0; $checked = 0;

        // Process masters in batches to avoid memory spikes
        DB::table('masters')->select(['id','photo','main_thumb_url','app'])->orderBy('id')->chunk(500, function ($rows) use (&$moved, &$thumbsMoved, &$errors, &$checked, $disk) {
            foreach ($rows as $r) {
                $checked++;
                $id = $r->id;
                $currentPhoto = $r->photo;
                $currentThumb = $r->main_thumb_url;

                // Move photo if present and not already under carbeat import path
                if (!empty($currentPhoto) && $disk->exists($currentPhoto)) {
                    // If path already contains /carbeat/ assume ok
                    if (strpos($currentPhoto, '/carbeat/') === false && strpos($currentPhoto, 'images/imports/') !== 0) {
                        try {
                            $ext = pathinfo($currentPhoto, PATHINFO_EXTENSION) ?: 'jpg';
                            $newRel = 'images/carbeat/' . uniqid('m_', true) . '.' . $ext;
                            if ($this->dryRun) {
                                $this->line("[dry-run] would move photo {$currentPhoto} -> {$newRel} for master {$id}");
                            } else {
                                $contents = $disk->get($currentPhoto);
                                $ok = $disk->put($newRel, $contents);
                                if ($ok) {
                                    // delete old
                                    try { $disk->delete($currentPhoto); } catch (\Throwable $_) {}
                                    DB::table('masters')->where('id', $id)->update(['photo' => $newRel]);
                                    $moved++;
                                }
                            }
                        } catch (\Throwable $e) {
                            $errors++;
                            Log::warning('[floxcity-import] failed moving photo', ['master' => $id, 'from' => $currentPhoto, 'e' => $e->getMessage()]);
                        }
                    }
                }

                // Move thumbnail if present
                if (!empty($currentThumb) && $disk->exists($currentThumb)) {
                    if (strpos($currentThumb, '/carbeat/') === false && strpos($currentThumb, 'thumbnails/') === 0) {
                        try {
                            $ext = pathinfo($currentThumb, PATHINFO_EXTENSION) ?: 'png';
                            $newThumb = rtrim((string) config('images.thumb.dir', 'thumbnails'), '/') . '/carbeat/' . $id . '.' . $ext;
                            if ($this->dryRun) {
                                $this->line("[dry-run] would move thumb {$currentThumb} -> {$newThumb} for master {$id}");
                            } else {
                                $contents = $disk->get($currentThumb);
                                $ok = $disk->put($newThumb, $contents);
                                if ($ok) {
                                    try { $disk->delete($currentThumb); } catch (\Throwable $_) {}
                                    DB::table('masters')->where('id', $id)->update(['main_thumb_url' => $newThumb, 'main_thumb_generated' => 1]);
                                    $thumbsMoved++;
                                }
                            }
                        } catch (\Throwable $e) {
                            $errors++;
                            Log::warning('[floxcity-import] failed moving thumb', ['master' => $id, 'from' => $currentThumb, 'e' => $e->getMessage()]);
                        }
                    }
                }
            }
        });

        $this->line("Rehomed files: photos={$moved}, thumbs={$thumbsMoved}, checked={$checked}, errors={$errors}");
    }
}
