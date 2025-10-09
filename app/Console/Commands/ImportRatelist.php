<?php

namespace App\Console\Commands;

use App\Http\Services\Ratelist\RatelistImportService;
use Illuminate\Console\Command;

class ImportRatelist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'masters:import-ratelist {service_id : Service ID (0 for auto-detect)} {url : RateList rating URL} {--pages= : Optional limit of pages to parse}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import masters from RateList rating page (e.g., https://ratelist.top/l/kyiv/rating-435). Shows progress.';

    /**
     * Execute the console command.
     */
    public function handle(RatelistImportService $importService): int
    {
        $serviceId = (int) $this->argument('service_id');
        $url = (string) $this->argument('url');
        $pagesOpt = $this->option('pages');
        $pages = is_null($pagesOpt) || $pagesOpt === '' ? null : (int) $pagesOpt;

        $this->info('Starting import from RateList...');
        $this->line('Service ID: ' . $serviceId . '; URL: ' . $url . '; Pages: ' . ($pages ?? 'all'));

        $processed = 0;
        $detailUrls = $importService->getDetailLinks($url, $pages);
        $total = count($detailUrls);
        $bar = $this->output->createProgressBar($total > 0 ? $total : 500);
        $bar->setBarWidth(50);
        $bar->start();

        $start = microtime(true);
        $result = $importService->performImport($serviceId, $url, null, function (array $context) use (&$processed, $bar, $total, $start) {
            $processed = $context['processed'] ?? ($processed + 1);
            if ($total) { $bar->setMaxSteps($total); }
            $bar->advance();
            // Show ETA in console title
            $elapsed = microtime(true) - $start;
            $rate = $processed > 0 ? $elapsed / $processed : 0;
            $remaining = $total > 0 ? max(0, ($total - $processed) * $rate) : 0;
            $this->output->write("\rETA: " . gmdate('H:i:s', (int)$remaining) . '   ');
        }, $detailUrls);

        $bar->finish();
        $this->newLine(2);
        $this->info('Imported: ' . $result['imported'] . '; Skipped: ' . $result['skipped']);

        return Command::SUCCESS;
    }
}
