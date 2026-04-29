<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\ServiceNameMapper;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeServiceNames extends Command
{
    protected $signature = 'services:normalize
        {--dry-run : Show planned changes only}
        {--force : Apply without confirmation prompt}
    ';

    protected $description = 'Normalize multilingual service names to canonical English keys and merge duplicates';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $services = Service::query()->orderBy('id')->get();

        if ($services->isEmpty()) {
            $this->info('No services found.');
            return self::SUCCESS;
        }

        $byCanonical = [];
        foreach ($services as $service) {
            $canonical = ServiceNameMapper::toCanonical($service->name);
            if ($canonical === '') {
                continue;
            }
            $byCanonical[$canonical][] = $service;
        }

        $plan = [];
        foreach ($byCanonical as $canonical => $group) {
            /** @var \App\Models\Service $keeper */
            $keeper = collect($group)
                ->first(fn(Service $service) => $service->name === $canonical)
                ?? $group[0];

            $duplicates = array_values(array_filter($group, fn(Service $item) => $item->id !== $keeper->id));
            $renameKeeper = $keeper->name !== $canonical;

            if ($renameKeeper || ! empty($duplicates)) {
                $plan[] = [
                    'canonical' => $canonical,
                    'keeper' => $keeper,
                    'rename_keeper' => $renameKeeper,
                    'duplicates' => $duplicates,
                ];
            }
        }

        if (empty($plan)) {
            $this->info('All services are already normalized.');
            return self::SUCCESS;
        }

        $renameCount = count(array_filter($plan, fn(array $item) => $item['rename_keeper']));
        $duplicateCount = array_sum(array_map(fn(array $item) => count($item['duplicates']), $plan));

        $this->line(sprintf(
            'Planned: %d canonical groups, %d keeper renames, %d duplicate records to merge%s',
            count($plan),
            $renameCount,
            $duplicateCount,
            $dryRun ? ' [dry-run]' : '',
        ));

        foreach ($plan as $item) {
            /** @var \App\Models\Service $keeper */
            $keeper = $item['keeper'];
            /** @var array<int,\App\Models\Service> $duplicates */
            $duplicates = $item['duplicates'];
            $this->line(sprintf(
                '- %s => keeper #%d "%s"%s; duplicates: %s',
                $item['canonical'],
                $keeper->id,
                $keeper->name,
                $item['rename_keeper'] ? ' (rename)' : '',
                empty($duplicates) ? 'none' : implode(', ', array_map(fn(Service $s) => "#{$s->id} \"{$s->name}\"", $duplicates)),
            ));
        }

        if ($dryRun) {
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Apply normalization and merge duplicates?', false)) {
            $this->warn('Aborted.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($plan): void {
            foreach ($plan as $item) {
                /** @var \App\Models\Service $keeper */
                $keeper = $item['keeper'];
                $canonical = $item['canonical'];
                /** @var array<int,\App\Models\Service> $duplicates */
                $duplicates = $item['duplicates'];

                if ($item['rename_keeper']) {
                    $keeper->name = $canonical;
                    $keeper->save();
                }

                foreach ($duplicates as $duplicate) {
                    DB::table('masters')
                        ->where('service_id', $duplicate->id)
                        ->update(['service_id' => $keeper->id]);

                    DB::table('master_services')
                        ->where('service_id', $duplicate->id)
                        ->update(['service_id' => $keeper->id]);

                    $duplicate->delete();
                }

                // Remove accidental duplicate pivot rows after merges.
                DB::statement(
                    'DELETE ms1 FROM master_services ms1
                     INNER JOIN master_services ms2
                       ON ms1.master_id = ms2.master_id
                      AND ms1.service_id = ms2.service_id
                      AND ms1.id > ms2.id'
                );
            }
        });

        $this->info('Service names normalized successfully.');
        return self::SUCCESS;
    }
}
