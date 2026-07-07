<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportRun;
use App\Models\ImportRunMaster;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ImportRunController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Import/Runs/Index');
    }

    public function show(int $importRun): Response
    {
        return Inertia::render('Admin/Import/Runs/Show', ['runId' => $importRun]);
    }

    public function list(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);

        $query = ImportRun::query()->orderByDesc('created_at');
        if ($request->filled('source')) {
            $query->where('source', $request->get('source'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('app')) {
            $query->where('app', $request->get('app'));
        }

        /** @var LengthAwarePaginator $items */
        $items = $query->paginate($perPage);

        return response()->json($items);
    }

    public function summary(int $importRun): JsonResponse
    {
        $run = ImportRun::findOrFail($importRun);

        $byCity = ImportRunMaster::query()
            ->where('import_run_id', $importRun)
            ->select([
                'city_name',
                DB::raw("SUM(CASE WHEN status = 'created' THEN 1 ELSE 0 END) as created_count"),
                DB::raw("SUM(CASE WHEN status = 'matched' THEN 1 ELSE 0 END) as matched_count"),
                DB::raw("SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) as skipped_count"),
                DB::raw('COUNT(*) as total'),
            ])
            ->groupBy('city_name')
            ->orderByDesc('total')
            ->get();

        $bySkipReason = ImportRunMaster::query()
            ->where('import_run_id', $importRun)
            ->where('status', 'skipped')
            ->select(['skip_reason', DB::raw('COUNT(*) as total')])
            ->groupBy('skip_reason')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'run' => $run,
            'by_city' => $byCity,
            'by_skip_reason' => $bySkipReason,
        ]);
    }

    public function masters(int $importRun, Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->get('per_page', 25), 1), 200);

        $query = ImportRunMaster::query()
            ->where('import_run_id', $importRun)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('city_name')) {
            $query->where('city_name', $request->get('city_name'));
        }
        if ($request->filled('q')) {
            $query->where('master_name', 'like', '%'.$request->get('q').'%');
        }

        /** @var LengthAwarePaginator $items */
        $items = $query->paginate($perPage);

        return response()->json($items);
    }
}
