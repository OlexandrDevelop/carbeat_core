<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\Ratelist\RatelistImportService;
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

    public function startImport(Request $request, RatelistImportService $importService): JsonResponse
    {
        $request->validate([
            'service_id' => 'required|integer|min:0',
            'url' => 'required|url',
            'pages' => 'nullable|integer|min:1',
        ]);

        $jobId = Str::uuid()->toString();

        // Initialize progress in Cache (redis store)
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

        // Enqueue job
        Queue::connection('redis')->push(new \App\Jobs\ImportMasters(
            $jobId,
            (int) $request->input('service_id'),
            (string) $request->input('url'),
            $request->input('pages') !== null ? (int) $request->input('pages') : null,
        ));

        return response()->json([
            'job_id' => $jobId,
        ]);
    }

    public function getProgress(string $jobId): JsonResponse
    {
        $progress = Cache::store('redis')->get("import_progress_{$jobId}");

        if (! $progress) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($progress);
    }

    public function stop(string $jobId): JsonResponse
    {
        Cache::store('redis')->put("import_stop_{$jobId}", true, now()->addHour());
        return response()->json(['status' => 'ok']);
    }
}
