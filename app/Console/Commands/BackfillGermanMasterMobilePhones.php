<?php

namespace App\Console\Commands;

use App\Http\Services\Import\GermanMasterMobileBackfillService;
use Illuminate\Console\Command;

class BackfillGermanMasterMobilePhones extends Command
{
    protected $signature = 'masters:backfill-de-mobile
        {--apply : Persist updates to database}
        {--limit= : Optional limit for processed masters}
        {--mode=api : Lookup mode: api, web, or hybrid}
        {--max-api-requests= : Hard API request budget for api/hybrid mode}
    ';

    protected $description = 'Find mobile phones on Google Maps for imported German masters with landline numbers';

    public function handle(GermanMasterMobileBackfillService $service): int
    {
        $apply = (bool) $this->option('apply');
        $limitOption = $this->option('limit');
        $limit = is_numeric($limitOption) ? (int) $limitOption : null;
        $mode = (string) $this->option('mode');
        $maxApiRequestsOption = $this->option('max-api-requests');
        $maxApiRequests = is_numeric($maxApiRequestsOption) ? (int) $maxApiRequestsOption : null;

        if (! in_array($mode, ['api', 'web', 'hybrid'], true)) {
            $this->error('Invalid mode. Allowed values: api, web, hybrid.');
            return self::FAILURE;
        }

        if (! $apply) {
            $this->warn('Running in dry-run mode. Use --apply to persist changes.');
        }

        $stats = $service->run(
            apply: $apply,
            limit: $limit,
            mode: $mode,
            maxApiRequests: $maxApiRequests,
            onProgress: function (array $stats): void {
                if ($stats['processed'] % 10 === 0) {
                    $this->line(sprintf(
                        'Processed: %d | Updated: %d | Not found: %d | Not mobile: %d | Failed: %d | API: %d',
                        $stats['processed'],
                        $stats['updated'],
                        $stats['not_found'],
                        $stats['not_mobile'],
                        $stats['failed'],
                        $stats['api_requests'] ?? 0,
                    ));
                }
            },
        );

        $this->newLine();
        $this->info(sprintf(
            'Done. Processed: %d | Updated: %d | Not found: %d | Not mobile: %d | Failed: %d | API: %d | Budget exhausted: %d',
            $stats['processed'],
            $stats['updated'],
            $stats['not_found'],
            $stats['not_mobile'],
            $stats['failed'],
            $stats['api_requests'] ?? 0,
            $stats['api_budget_exhausted'] ?? 0,
        ));

        return self::SUCCESS;
    }
}
