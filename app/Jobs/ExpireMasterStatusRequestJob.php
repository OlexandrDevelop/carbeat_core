<?php

namespace App\Jobs;

use App\Http\Services\MasterStatusRequestService;
use App\Models\MasterStatusRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireMasterStatusRequestJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $statusRequestId
    ) {}

    public function handle(MasterStatusRequestService $service): void
    {
        $statusRequest = MasterStatusRequest::find($this->statusRequestId);
        if (! $statusRequest) {
            return;
        }

        $service->expire($statusRequest);
    }
}
