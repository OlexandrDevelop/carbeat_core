<?php

namespace App\Console\Commands;

use App\Enums\AppBrand;
use App\Http\Services\SmartRandomStatusService;
use Illuminate\Console\Command;

class SyncSmartRandomStatuses extends Command
{
    protected $signature = 'smart-random-statuses:sync {--app=}';

    protected $description = 'Rotate fake online statuses for imported masters';

    public function handle(SmartRandomStatusService $service): int
    {
        $app = $this->option('app');
        $apps = $app ? [$app] : array_map(fn (AppBrand $brand) => $brand->value, AppBrand::cases());

        foreach ($apps as $brand) {
            $result = $service->sync($brand);

            $this->line(sprintf(
                '[%s] enabled=%s target=%d current=%d on=%d off=%d',
                $brand,
                $result['enabled'] ? 'yes' : 'no',
                $result['target_fake_green'],
                $result['current_fake_green'],
                $result['turned_on'],
                $result['turned_off'],
            ));
        }

        return self::SUCCESS;
    }
}
