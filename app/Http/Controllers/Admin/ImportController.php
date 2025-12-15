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
                $validated['pages'] ?? null,
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
