<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EasyWeek\ConnectEasyWeekRequest;
use App\Http\Services\EasyWeek\EasyWeekConnectionService;
use App\Jobs\SyncEasyweekConnectionJob;
use App\Models\Master;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class EasyWeekController extends Controller
{
    public function __construct(private readonly EasyWeekConnectionService $connectionService) {}

    /**
     * Master submits their EasyWeek booking-page link so we can start
     * mirroring their real busy/available status on the map. Validates the
     * slug and queues a config sync; the availability poll picks it up
     * automatically once the connection goes active.
     */
    public function connect(ConnectEasyWeekRequest $request): JsonResponse
    {
        $master = $this->resolveAuthenticatedMaster($request);
        if (! $master) {
            return response()->json(['error' => 'master_not_found'], 404);
        }

        try {
            $connection = $this->connectionService->connect($master, $request->validated()['easyweek_url']);
        } catch (InvalidArgumentException) {
            return response()->json(['error' => 'invalid_easyweek_url'], 422);
        }

        SyncEasyweekConnectionJob::dispatch($connection->id);

        return response()->json([
            'status' => 'pending',
            'easyweek_company_slug' => $connection->easyweek_company_slug,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $master = $this->resolveAuthenticatedMaster($request);
        if (! $master) {
            return response()->json(['error' => 'master_not_found'], 404);
        }

        $connection = $master->easyweekConnection;
        if (! $connection) {
            return response()->json(['status' => 'not_connected']);
        }

        return response()->json([
            'status' => $connection->sync_status,
            'easyweek_company_slug' => $connection->easyweek_company_slug,
            'last_synced_at' => optional($connection->last_synced_at)->toIso8601String(),
            'last_availability_synced_at' => optional($connection->last_availability_synced_at)->toIso8601String(),
            'last_error' => $connection->last_error,
        ]);
    }

    private function resolveAuthenticatedMaster(Request $request): ?Master
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return Master::where('user_id', $user->id)->first();
    }
}
