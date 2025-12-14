<?php

namespace App\Console\Commands;

use App\Jobs\CreateMasterThumbnails;
use App\Models\Master;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class GenerateMasterThumbnails extends Command
{
    protected $signature = 'masters:generate-thumbnails {--chunk=200 : How many masters per batch} {--reset : Reset flags to regenerate all thumbnails}';

    protected $description = 'Generate square thumbnails for masters main photos with progress and ETA';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));

        if ($this->option('reset')) {
            $this->line('Resetting thumbnail flags...');
            $resetCount = (int) Master::query()
                ->whereNotNull('photo')
                ->update(['main_thumb_generated' => false, 'main_thumb_url' => null]);
            $this->info('Reset masters: '.$resetCount);
        }

        $query = Master::query()
            ->whereNotNull('photo')
            ->where(function ($q) {
                $q->where('main_thumb_generated', false)->orWhereNull('main_thumb_generated');
            });

        $total = $query->count();
        if ($total === 0) {
            $this->info('Nothing to generate.');
            return CommandAlias::SUCCESS;
        }

        $this->info('Generating thumbnails... Total: '.$total.'; Chunk: '.$chunk);
        $bar = $this->output->createProgressBar($total);
        $bar->setBarWidth(50);
        $bar->start();

        $processed = 0;
        $doneTotal = 0;
        $start = microtime(true);

        $query->orderBy('id')->chunk($chunk, function ($masters) use (&$processed, &$doneTotal, $total, $bar, $start) {
            $ids = $masters->pluck('id')->all();
            // Run synchronously to get return value (count of generated thumbnails)
            $done = new CreateMasterThumbnails($ids)->handle();
            $doneTotal += $done;
            $processed += count($ids);
            $bar->advance(count($ids));

            $elapsed = microtime(true) - $start;
            $rate = $processed > 0 ? $elapsed / $processed : 0;
            $remaining = max(0, ($total - $processed) * $rate);
            $this->output->write("\rETA: " . gmdate('H:i:s', (int)$remaining) . '   ');
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('Processed: '.$processed.'; Generated: '.$doneTotal.';');

        return CommandAlias::SUCCESS;
    }
}


