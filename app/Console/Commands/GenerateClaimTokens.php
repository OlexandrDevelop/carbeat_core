<?php

namespace App\Console\Commands;

use App\Models\Master;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateClaimTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'masters:generate-claim-tokens {--force : Regenerate tokens even if they already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate or backfill claim tokens for master profiles';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $updated = 0;

        $this->components->info($force
            ? 'Regenerating claim tokens for all masters...'
            : 'Generating claim tokens for masters missing them...');

        Master::query()
            ->when(! $force, fn ($query) => $query->whereNull('claim_token'))
            ->chunkById(500, function ($masters) use (&$updated) {
                foreach ($masters as $master) {
                    $master->claim_token = Str::random(40);
                    if ($master->is_claimed === null) {
                        $master->is_claimed = false;
                    }
                    $master->save();
                    $updated++;
                }
            });

        $this->components->info("Claim tokens generated for {$updated} masters.");

        return self::SUCCESS;
    }
}
