<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master;
use App\Models\MasterRepairRequestStatus;
use App\Models\RepairRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Repair-request leads for a logged-in master: requests are broadcast to
 * every master offering the matching service
 * (App\Http\Services\RepairRequestNotificationService), not assigned to one,
 * so there's no ownership check beyond "service matches" — but the
 * called/rejected status (App\Models\MasterRepairRequestStatus) is private
 * per master.
 */
class RepairRequestController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Master/RepairRequests/Index', [
            'initialSelectedId' => $request->integer('selected') ?: null,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $master = $this->resolveMaster($request);
        $serviceIds = $this->matchingServiceIds($master);

        $tab = $request->string('tab', 'all')->toString();

        $query = RepairRequest::whereIn('service_id', $serviceIds)
            ->with('service:id,name');

        match ($tab) {
            'new' => $query->whereDoesntHave(
                'masterStatuses',
                fn ($q) => $q->where('master_id', $master->id)
            ),
            'active' => $query->whereDoesntHave(
                'masterStatuses',
                fn ($q) => $q->where('master_id', $master->id)->where('status', 'rejected')
            ),
            'called' => $query->whereHas(
                'masterStatuses',
                fn ($q) => $q->where('master_id', $master->id)->where('status', 'called')
            ),
            'rejected' => $query->whereHas(
                'masterStatuses',
                fn ($q) => $q->where('master_id', $master->id)->where('status', 'rejected')
            ),
            default => null,
        };

        $paginated = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $statusByRequestId = MasterRepairRequestStatus::where('master_id', $master->id)
            ->whereIn('repair_request_id', $paginated->pluck('id'))
            ->get()
            ->keyBy('repair_request_id');

        $items = $paginated->getCollection()->map(fn (RepairRequest $repairRequest) => [
            'id' => $repairRequest->id,
            'car_make' => $repairRequest->car_make,
            'car_model' => $repairRequest->car_model,
            'car_year' => $repairRequest->car_year,
            'description' => $repairRequest->description,
            'name' => $repairRequest->name,
            'phone' => $repairRequest->phone,
            'service_name' => $repairRequest->service?->name,
            'created_at' => $repairRequest->created_at?->toIso8601String(),
            'status' => $statusByRequestId->has($repairRequest->id)
                ? $statusByRequestId->get($repairRequest->id)->status
                : 'pending',
        ]);

        return response()->json([
            'data' => $items,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'total' => $paginated->total(),
            'counts' => $this->tabCounts($master, $serviceIds),
        ]);
    }

    public function updateStatus(Request $request, RepairRequest $repairRequest): JsonResponse
    {
        $master = $this->resolveMaster($request);

        abort_unless(
            in_array($repairRequest->service_id, $this->matchingServiceIds($master), true),
            403
        );

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'called', 'rejected'])],
        ]);

        $now = now();

        $status = MasterRepairRequestStatus::updateOrCreate(
            ['master_id' => $master->id, 'repair_request_id' => $repairRequest->id],
            [
                'status' => $validated['status'],
                'called_at' => $validated['status'] === 'called' ? $now : null,
                'rejected_at' => $validated['status'] === 'rejected' ? $now : null,
            ]
        );

        return response()->json(['status' => $status->status]);
    }

    /**
     * @return array<int, int>
     */
    private function matchingServiceIds(Master $master): array
    {
        return $master->services()
            ->pluck('services.id')
            ->push($master->service_id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $serviceIds
     * @return array<string, int>
     */
    private function tabCounts(Master $master, array $serviceIds): array
    {
        $base = fn () => RepairRequest::whereIn('service_id', $serviceIds);

        return [
            'all' => $base()->count(),
            'new' => $base()->whereDoesntHave(
                'masterStatuses',
                fn ($q) => $q->where('master_id', $master->id)
            )->count(),
            'active' => $base()->whereDoesntHave(
                'masterStatuses',
                fn ($q) => $q->where('master_id', $master->id)->where('status', 'rejected')
            )->count(),
            'called' => $base()->whereHas(
                'masterStatuses',
                fn ($q) => $q->where('master_id', $master->id)->where('status', 'called')
            )->count(),
            'rejected' => $base()->whereHas(
                'masterStatuses',
                fn ($q) => $q->where('master_id', $master->id)->where('status', 'rejected')
            )->count(),
        ];
    }

    private function resolveMaster(Request $request): Master
    {
        /** @var Master $master */
        $master = $request->attributes->get('master');

        return $master;
    }
}
