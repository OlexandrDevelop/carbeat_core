<?php

namespace App\Jobs;

use App\Http\Services\Ratelist\RatelistImportService;
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
        private readonly ?int $pages
    ) {}

    public function handle(RatelistImportService $importService): void
    {
        Log::info('Starting import job', [
            'job_id' => $this->jobId,
            'service_id' => $this->serviceId,
            'url' => $this->url,
            'pages' => $this->pages
        ]);

        try {
            // Expose job id for stop checks inside the service
            $GLOBALS['current_job_id'] = $this->jobId;
            $detailUrls = $importService->getDetailLinks($this->url, $this->pages);
            Log::info('Extracted detail URLs', [
                'job_id' => $this->jobId,
                'count' => count($detailUrls)
            ]);

            Cache::store('redis')->put(
                "import_progress_{$this->jobId}",
                [
                    'status' => 'running',
                    'imported' => 0,
                    'skipped' => 0,
                    'processed' => 0,
                    'error' => null,
                    'total_urls' => count($detailUrls)
                ],
                now()->addHour()
            );

            $start = microtime(true);
            $result = $importService->performImport(
                $this->serviceId,
                $this->url,
                null,
                function (array $context) use ($detailUrls, $start) {
                    Log::info('Import progress update', [
                        'job_id' => $this->jobId,
                        'context' => $context,
                        'total_urls' => count($detailUrls)
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
                            'total_urls' => count($detailUrls)
                        ],
                        now()->addHour()
                    );
                },
                $detailUrls
            );

            Log::info('Import completed', [
                'job_id' => $this->jobId,
                'result' => $result,
                'total_urls' => count($detailUrls)
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
                    'total_urls' => count($detailUrls)
                ],
                now()->addHour()
            );

        } catch (\Exception $e) {
            Log::error('Import failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage()
            ]);

            Cache::store('redis')->put(
                "import_progress_{$this->jobId}",
                [
                    'status' => 'error',
                    'imported' => 0,
                    'skipped' => 0,
                    'processed' => 0,
                    'error' => $e->getMessage(),
                    'total_urls' => 0
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
            'error' => $exception->getMessage()
        ]);

        Cache::store('redis')->put(
            "import_progress_{$this->jobId}",
            [
                'status' => 'error',
                'imported' => 0,
                'skipped' => 0,
                'processed' => 0,
                'error' => $exception->getMessage(),
                'total_urls' => 0
            ],
            now()->addHour()
        );
    }
}
