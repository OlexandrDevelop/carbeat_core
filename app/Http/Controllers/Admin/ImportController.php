<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppBrand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportStartRequest;
use App\Http\Resources\Admin\ImportProgressResource;
use App\Http\Resources\Admin\ImportStartResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Psr\SimpleCache\InvalidArgumentException;

class ImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Import/Index');
    }

    public function startImport(ImportStartRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Determine the current brand/flavor from middleware context (admin.brand sets config['app.client'])
        $currentBrand = config('app.client');
        $flavor = $currentBrand instanceof AppBrand ? $currentBrand->value : 'carbeat';

        [$fromPage, $toPage] = $this->parsePageRange($validated['pages'] ?? null);

        $jobs = [];
        foreach ((array) $validated['urls'] as $url) {
            $jobId = Str::uuid()->toString();

            Cache::store('redis')->put(
                "import_progress_{$jobId}",
                [
                    'status' => 'queued',
                    'imported' => 0,
                    'skipped' => 0,
                    'processed' => 0,
                    'total_urls' => 0,
                    'eta_seconds' => null,
                    'error' => null,
                ],
                now()->addHour()
            );

            Queue::connection('redis')->push(new \App\Jobs\ImportMasters(
                $jobId,
                (int) $validated['service_id'],
                (string) $url,
                $fromPage,
                $toPage,
                $flavor, // Pass flavor to job
            ));

            $jobs[] = [
                'job_id' => $jobId,
                'url' => $url,
            ];
        }

        return new ImportStartResponse(['jobs' => $jobs])->response();
    }

    /**
     * Parse the "pages" form field into a [fromPage, toPage] pair.
     * Accepts a plain count ("10" => [1, 10]), a range ("5-10" => [5, 10]), or empty ([null, null]).
     *
     * @return array{0:?int,1:?int}
     */
    private function parsePageRange(?string $pages): array
    {
        if (! $pages) {
            return [null, null];
        }
        if (preg_match('/^(\d+)-(\d+)$/', $pages, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        if (ctype_digit($pages)) {
            return [1, (int) $pages];
        }

        return [null, null];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getProgress(string $jobId): JsonResponse
    {
        $progress = Cache::store('redis')->get("import_progress_{$jobId}");

        if (! $progress) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json(new ImportProgressResource($progress)->toArray(request()));
    }

    public function stop(string $jobId): JsonResponse
    {
        Cache::store('redis')->put("import_stop_{$jobId}", true, now()->addHour());

        return response()->json(['status' => 'ok']);
    }
}
