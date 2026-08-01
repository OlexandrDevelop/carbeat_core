<?php

namespace App\Http\Services\EasyWeek;

use App\Exceptions\EasyWeekApiException;
use App\Http\Services\FcmService;
use App\Models\EasyweekConnection;
use App\Models\Master;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class EasyWeekConnectionService
{
    public function __construct(
        private readonly EasyWeekPartnerApiClient $client,
        private readonly EasyWeekSlugParser $slugParser,
        private readonly FcmService $fcmService,
    ) {}

    /**
     * Register (or re-point) a master's EasyWeek connection from a pasted
     * booking-page URL or bare slug. Does not sync yet — caller is
     * responsible for dispatching the sync job.
     *
     * @throws InvalidArgumentException
     */
    public function connect(Master $master, string $easyweekUrlOrSlug): EasyweekConnection
    {
        $slug = $this->slugParser->parse($easyweekUrlOrSlug);
        if (! $slug) {
            throw new InvalidArgumentException('invalid_easyweek_url');
        }

        return EasyweekConnection::updateOrCreate(
            ['master_id' => $master->id],
            [
                'easyweek_company_slug' => $slug,
                'sync_status' => EasyweekConnection::STATUS_PENDING,
                'last_error' => null,
                'consecutive_failures' => 0,
            ]
        );
    }

    /**
     * Pull the company config from EasyWeek and cache just enough (primary
     * branch/product id + timezone) to drive the frequent availability
     * poll without re-fetching the whole widget config every time.
     * Never throws — failures are recorded on the connection so a bad slug
     * or an EasyWeek outage can't break the sync schedule.
     */
    public function syncConfig(EasyweekConnection $connection): void
    {
        try {
            $data = $this->client->getCompanyConfig($connection->easyweek_company_slug)['data'] ?? [];
        } catch (EasyWeekApiException $e) {
            $this->recordFailure($connection, $e->getMessage());

            return;
        }

        $branch = $data['branches'][0] ?? null;
        $product = $data['products'][0] ?? null;

        if (! $branch || ! $product) {
            $this->recordFailure($connection, 'EasyWeek company has no branches/products configured');

            return;
        }

        $connection->update([
            'easyweek_company_id' => $data['company']['id'] ?? $connection->easyweek_company_id,
            'primary_branch_id' => $branch['id'],
            'primary_product_id' => $product['id'],
            'timezone' => $branch['timezone'] ?? ($data['company']['profile']['timezone'] ?? null),
            'sync_status' => EasyweekConnection::STATUS_ACTIVE,
            'last_synced_at' => now(),
            'last_error' => null,
            'consecutive_failures' => 0,
        ]);
    }

    private function recordFailure(EasyweekConnection $connection, string $message): void
    {
        $connection->increment('consecutive_failures');
        $connection->update([
            'sync_status' => EasyweekConnection::STATUS_ERROR,
            'last_error' => $message,
        ]);

        Log::warning('EasyWeek connection sync failed', [
            'connection_id' => $connection->id,
            'master_id' => $connection->master_id,
            'consecutive_failures' => $connection->consecutive_failures,
            'error' => $message,
        ]);

        $recentlyNotified = $connection->failure_notified_at
            && $connection->failure_notified_at->isAfter(now()->subDay());

        if ($connection->consecutive_failures >= EasyweekConnection::MAX_CONSECUTIVE_FAILURES && ! $recentlyNotified) {
            $this->notifyMasterOfFailure($connection);
        }
    }

    private function notifyMasterOfFailure(EasyweekConnection $connection): void
    {
        $user = Master::withoutGlobalScope('app')->find($connection->master_id)?->user;

        if ($user) {
            $this->fcmService->sendToUser(
                $user,
                'EasyWeek: проблема із синхронізацією',
                'Не вдається оновити статус зайнятості через EasyWeek. Перевірте посилання на профіль у налаштуваннях.',
                ['type' => 'easyweek_sync_error', 'connection_id' => (string) $connection->id]
            );
        }

        $connection->update(['failure_notified_at' => now()]);
    }
}
