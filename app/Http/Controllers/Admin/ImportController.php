<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportStartRequest;
use App\Http\Resources\Admin\ImportProgressResource;
use App\Http\Resources\Admin\ImportStartResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Import/Index');
    }

    public function startImport(ImportStartRequest $request): JsonResponse
    {
        $validated = $request->validated();

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
            ));

            $jobs[] = [
                'job_id' => $jobId,
                'url' => $url,
            ];
        }

        return (new ImportStartResponse(['jobs' => $jobs]))->response();
    }

    public function getProgress(string $jobId): JsonResponse
    {
        $progress = Cache::store('redis')->get("import_progress_{$jobId}");

        if (! $progress) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json((new ImportProgressResource($progress))->toArray(request()));
    }

    public function stop(string $jobId): JsonResponse
    {
        Cache::store('redis')->put("import_stop_{$jobId}", true, now()->addHour());
        return response()->json(['status' => 'ok']);
    }
}
