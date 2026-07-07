<?php

namespace App\Jobs;

use App\Http\Services\Import\ImportServiceFactory;
use App\Models\ImportRun;
use App\Models\ImportRunMaster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ImportMasters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 3600;

    public $backoff = 60;

    public function __construct(
        private readonly string $jobId,
        private readonly int $serviceId,
        private readonly string $url,
        private readonly ?int $fromPage,
        private readonly ?int $toPage,
        private readonly string $flavor = 'carbeat' // Default to carbeat if not provided
    ) {}

    public function handle(ImportServiceFactory $importFactory): void
    {
        // Set the flavor in config so PhotoHelper and other services can use it
        config(['app.client' => $this->flavor]);

        Log::info('Starting import job', [
            'job_id' => $this->jobId,
            'service_id' => $this->serviceId,
            'url' => $this->url,
            'from_page' => $this->fromPage,
            'to_page' => $this->toPage,
            'flavor' => $this->flavor,
        ]);

        // Set outside the try block so the catch/failed handlers below can always
        // reference it; populated with a real ImportRun as soon as the importer resolves.
        $importRun = null;
        $flushMasterResults = function () {};

        try {
            $importService = $importFactory->getImporter($this->url);

            // History/analytics record for this run (survives past the 1h Redis progress TTL).
            // updateOrCreate + wiping prior master rows keeps retries (job $tries=3) idempotent.
            $source = str_replace('ImportService', '', class_basename($importService));
            $importRun = ImportRun::updateOrCreate(
                ['job_id' => $this->jobId],
                [
                    'source' => $source,
                    'url' => $this->url,
                    'app' => $this->flavor,
                    'status' => 'running',
                    'started_at' => now(),
                    'finished_at' => null,
                    'error' => null,
                ]
            );
            $importRun->masters()->delete();

            $masterResultBuffer = [];
            $flushMasterResults = function () use (&$masterResultBuffer) {
                if (empty($masterResultBuffer)) {
                    return;
                }
                ImportRunMaster::insert($masterResultBuffer);
                $masterResultBuffer = [];
            };
            $onMasterResult = function (array $result) use (&$masterResultBuffer, $flushMasterResults, $importRun) {
                $masterResultBuffer[] = [
                    'import_run_id' => $importRun->id,
                    'master_id' => $result['master_id'] ?? null,
                    'city_id' => $result['city_id'] ?? null,
                    'master_name' => $result['master_name'] ?? null,
                    'city_name' => $result['city_name'] ?? null,
                    'status' => $result['status'],
                    'skip_reason' => $result['skip_reason'] ?? null,
                    'created_at' => now(),
                ];
                if (count($masterResultBuffer) >= 50) {
                    $flushMasterResults();
                }
            };

            // Expose job id for stop checks inside the service
            $GLOBALS['current_job_id'] = $this->jobId;
            $detailUrls = $importService->getDetailLinks($this->url, $this->toPage, $this->fromPage);
            Log::info('Extracted detail URLs', [
                'job_id' => $this->jobId,
                'count' => count($detailUrls),
            ]);

            Cache::store('redis')->put(
                "import_progress_{$this->jobId}",
                [
                    'status' => 'running',
                    'imported' => 0,
                    'skipped' => 0,
                    'processed' => 0,
                    'error' => null,
                    'total_urls' => count($detailUrls),
                ],
                now()->addHour()
            );
            $importRun->update(['total_urls' => count($detailUrls)]);

            $start = microtime(true);
            $result = $importService->performImport(
                $this->serviceId,
                $this->url,
                null,
                function (array $context) use ($detailUrls, $start) {
                    Log::info('Import progress update', [
                        'job_id' => $this->jobId,
                        'context' => $context,
                        'total_urls' => count($detailUrls),
                    ]);

                    $processed = (int) ($context['processed'] ?? 0);
                    $elapsed = microtime(true) - $start;
                    $rate = $processed > 0 ? $elapsed / $processed : 0;
                    $eta = count($detailUrls) > 0 ? max(0, (count($detailUrls) - $processed) * $rate) : null;

                    Cache::store('redis')->put(
                        "import_progress_{$this->jobId}",
                        [
                            'status' => 'running',
                            'imported' => (int) ($context['imported'] ?? 0),
                            'skipped' => (int) ($context['skipped'] ?? 0),
                            'processed' => (int) ($context['processed'] ?? 0),
                            'eta_seconds' => $eta !== null ? (int) $eta : null,
                            'error' => null,
                            'total_urls' => count($detailUrls),
                        ],
                        now()->addHour()
                    );
                },
                $detailUrls,
                $onMasterResult
            );
            $flushMasterResults();

            Log::info('Import completed', [
                'job_id' => $this->jobId,
                'result' => $result,
                'total_urls' => count($detailUrls),
            ]);

            Cache::store('redis')->put(
                "import_progress_{$this->jobId}",
                [
                    'status' => ($result['stopped'] ?? false) ? 'stopped' : 'completed',
                    'imported' => (int) $result['imported'],
                    'skipped' => (int) $result['skipped'],
                    'processed' => (int) ($result['imported'] + $result['skipped']),
                    'eta_seconds' => 0,
                    'error' => null,
                    'total_urls' => count($detailUrls),
                ],
                now()->addHour()
            );
            $importRun->update([
                'status' => ($result['stopped'] ?? false) ? 'stopped' : 'completed',
                'imported_count' => (int) $result['imported'],
                'matched_count' => (int) ($result['matched'] ?? 0),
                'skipped_count' => (int) $result['skipped'],
                'finished_at' => now(),
            ]);

            // Dispatch thumbnail creation job for all affected masters (ids where main photo exists and no thumb yet)
            $masterIds = \App\Models\Master::query()
                ->whereNotNull('photo')
                ->where(function ($q) {
                    $q->where('main_thumb_generated', false)->orWhereNull('main_thumb_generated');
                })
                ->limit(2000)
                ->pluck('id')
                ->all();
            if (! empty($masterIds)) {
                dispatch(new \App\Jobs\CreateMasterThumbnails($masterIds));
            }

        } catch (\Exception $e) {
            Log::error('Import failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
            ]);

            $flushMasterResults();
            $importRun?->update(['status' => 'error', 'error' => $e->getMessage(), 'finished_at' => now()]);

            Cache::store('redis')->put(
                "import_progress_{$this->jobId}",
                [
                    'status' => 'error',
                    'imported' => 0,
                    'skipped' => 0,
                    'processed' => 0,
                    'error' => $e->getMessage(),
                    'total_urls' => 0,
                ],
                now()->addHour()
            );

            throw $e;
        } finally {
            unset($GLOBALS['current_job_id']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Import job failed', [
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
        ]);

        ImportRun::where('job_id', $this->jobId)->update([
            'status' => 'error',
            'error' => $exception->getMessage(),
            'finished_at' => now(),
        ]);

        Cache::store('redis')->put(
            "import_progress_{$this->jobId}",
            [
                'status' => 'error',
                'imported' => 0,
                'skipped' => 0,
                'processed' => 0,
                'error' => $exception->getMessage(),
                'total_urls' => 0,
            ],
            now()->addHour()
        );
    }
}
