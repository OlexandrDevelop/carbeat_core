<?php

namespace App\Jobs;

use App\Http\Services\RepairRequestNotificationService;
use App\Models\RepairRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs App\Http\Services\RepairRequestNotificationService::notify() off the
 * request cycle — a dense-city match can be dozens of Telegram/SMS sends,
 * which would otherwise block the admin's approve() response until every
 * one completes.
 */
class NotifyMastersOfRepairRequestJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $repairRequestId
    ) {}

    public function handle(RepairRequestNotificationService $notificationService): void
    {
        $repairRequest = RepairRequest::find($this->repairRequestId);
        if (! $repairRequest) {
            return;
        }

        $notificationService->notify($repairRequest);
    }
}
