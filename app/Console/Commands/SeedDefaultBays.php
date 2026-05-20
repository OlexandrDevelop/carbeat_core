<?php

namespace App\Console\Commands;

use App\Models\Master;
use App\Models\MasterBay;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedDefaultBays extends Command
{
    protected $signature = 'crm:seed-default-bays {--dry-run : Show what would be created without writing}';

    protected $description = 'Create a default bay/chair for every master that has none';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $mastersWithoutBays = Master::withoutGlobalScope('app')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('master_bays')
                    ->whereColumn('master_bays.master_id', 'masters.id');
            })
            ->select(['id', 'name', 'app'])
            ->get();

        if ($mastersWithoutBays->isEmpty()) {
            $this->info('All masters already have at least one bay.');
            return self::SUCCESS;
        }

        $this->info("Found {$mastersWithoutBays->count()} master(s) without bays.");

        if ($dryRun) {
            $this->table(['ID', 'Name', 'App', 'Default title'], $mastersWithoutBays->map(fn ($m) => [
                $m->id,
                $m->name,
                $m->app,
                $m->app === 'floxcity' ? 'Крісло 1' : 'Бокс 1',
            ]));
            $this->warn('Dry run — nothing was written.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($mastersWithoutBays->count());
        $bar->start();

        foreach ($mastersWithoutBays as $master) {
            $title = $master->app === 'floxcity' ? 'Крісло 1' : 'Бокс 1';

            MasterBay::withoutGlobalScopes()->create([
                'uuid'            => Str::uuid()->toString(),
                'master_id'       => $master->id,
                'title'           => $title,
                'technician_name' => $master->name ?? '',
                'is_active'       => true,
                'display_order'   => 0,
                'status'          => 'free',
                'app'             => $master->app ?? 'carbeat',
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Created {$mastersWithoutBays->count()} default bay(s).");

        return self::SUCCESS;
    }
}
