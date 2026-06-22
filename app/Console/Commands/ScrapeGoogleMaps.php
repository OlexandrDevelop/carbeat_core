<?php

namespace App\Console\Commands;

use App\Helpers\PhotoHelper;
use App\Models\Master;
use App\Models\MasterGallery;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ScrapeGoogleMaps extends Command
{
    protected $signature = 'masters:scrape-google
        {--id=           : Scrape a specific master by ID}
        {--limit=50      : Maximum masters to process per run}
        {--reviews=30    : Maximum reviews to scrape per master}
        {--photos        : Also scrape and import photos}
        {--force         : Re-scrape even masters that already have reviews}
        {--delay=4       : Seconds to wait between requests (avoid rate limiting)}';

    protected $description = 'Scrape Google Maps reviews (and optionally photos) for masters without using the API.';

    private PhotoHelper $photoHelper;

    public function handle(PhotoHelper $photoHelper): int
    {
        $this->photoHelper = $photoHelper;

        $scraperPath = base_path('scraper/google-maps.js');
        if (! file_exists($scraperPath)) {
            $this->error('Scraper not found: ' . $scraperPath);
            return Command::FAILURE;
        }

        $nodeModules = base_path('scraper/node_modules');
        if (! is_dir($nodeModules)) {
            $this->error('Playwright not installed. Run: cd scraper && npm install && npx playwright install chromium');
            return Command::FAILURE;
        }

        $masters = $this->resolveMasters();
        $total = $masters->count();

        if ($total === 0) {
            $this->info('No masters to process.');
            return Command::SUCCESS;
        }

        $this->info("Майстрів до обробки: {$total}");
        $bar = $this->output->createProgressBar($total);
        $bar->setBarWidth(40);
        $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%%  %message%\n");
        $bar->setMessage('...');
        $bar->start();

        $imported = 0;
        $skipped  = 0;
        $failed   = 0;
        $delay    = max(1, (int) $this->option('delay'));

        foreach ($masters as $master) {
            $label = mb_substr($master->name, 0, 35);
            $bar->setMessage($label);

            $result = $this->runScraper($master, $scraperPath);

            if ($result === null || ! $result['success']) {
                $failed++;
                $bar->advance();
                $this->newLine();
                $this->line("  <fg=red>✗</> [{$master->id}] {$master->name}");
                sleep($delay);
                continue;
            }

            DB::beginTransaction();
            try {
                $reviewsAdded = $this->storeReviews($master, $result['reviews'] ?? []);
                $photosAdded  = 0;
                if ($this->option('photos')) {
                    $photosAdded = $this->storePhotos($master, $result['photos'] ?? []);
                }
                DB::commit();

                $bar->advance();
                $this->newLine();

                $parts = [];
                if ($this->option('photos')) {
                    $parts[] = "<fg=cyan>+{$photosAdded} фото</>";
                }
                if ($result['authenticated'] ?? false) {
                    $parts[] = "<fg=green>+{$reviewsAdded} відгуків</>";
                } else {
                    $parts[] = '<fg=yellow>без авторизації</>';
                }
                $summary = implode('  ', $parts);

                if ($reviewsAdded > 0 || $photosAdded > 0) {
                    $this->line("  <fg=green>✓</> [{$master->id}] {$master->name}  {$summary}");
                    $imported++;
                } else {
                    $this->line("  <fg=gray>–</> [{$master->id}] {$master->name}  (нових даних немає)");
                    $skipped++;
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                $bar->advance();
                $this->newLine();
                $this->line("  <fg=red>✗</> [{$master->id}] {$master->name}: " . $e->getMessage());
                $failed++;
            }

            sleep($delay);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Готово.  ✓ Оновлено: {$imported}  – Без змін: {$skipped}  ✗ Помилок: {$failed}");

        return Command::SUCCESS;
    }

    private function resolveMasters(): \Illuminate\Database\Eloquent\Collection
    {
        $limit = max(1, (int) $this->option('limit'));

        if ($id = $this->option('id')) {
            return Master::where('id', (int) $id)->get();
        }

        // Must have at least name + address to do a meaningful search
        $query = Master::query()
            ->whereNotNull('name')
            ->whereNotNull('address')
            ->where('name', '!=', '')
            ->where('address', '!=', '');

        if (! $this->option('force')) {
            $query->whereDoesntHave('reviews');
        }

        return $query->limit($limit)->get();
    }

    private function runScraper(Master $master, string $scraperPath): ?array
    {
        $maxReviews = (int) $this->option('reviews');

        $cmd = [
            'node',
            $scraperPath,
            $master->place_id ?? '',
            $master->name    ?? '',
            $master->address ?? '',
            (string) $maxReviews,
        ];

        $process = new Process($cmd, base_path(), [], null, 60);

        try {
            $process->run();
            $output = trim($process->getOutput());

            if (empty($output)) {
                return null;
            }

            $data = json_decode($output, true);
            return is_array($data) ? $data : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function storeReviews(Master $master, array $reviews): int
    {
        if (empty($reviews)) {
            return 0;
        }

        // One shared "Google Maps" user for imported reviews
        $googleUser = $this->resolveGoogleUser($master->app ?? 'carbeat');

        $added = 0;
        foreach ($reviews as $review) {
            $rating = (int) ($review['rating'] ?? 0);
            $text   = trim($review['text'] ?? '');

            if ($rating < 1 || $rating > 5) {
                continue;
            }

            // Deduplicate by (review text, rating) — same as existing import logic
            $existing = $master->reviews()
                ->where('rating', $rating)
                ->where('review', $text)
                ->exists();

            if ($existing) {
                continue;
            }

            $master->reviews()->create([
                'review'    => $text,
                'rating'    => $rating,
                'user_id'   => $googleUser->id,
                'master_id' => $master->id,
            ]);
            $added++;
        }

        if ($added > 0) {
            // Recompute cached rating
            $avg = $master->reviews()->avg('rating');
            $master->update(['rating' => round((float) $avg, 2)]);
        }

        return $added;
    }

    private function storePhotos(Master $master, array $photoUrls): int
    {
        if (empty($photoUrls)) {
            return 0;
        }

        $flavor = ! empty($master->app) ? (string) $master->app : 'carbeat';
        $added  = 0;

        foreach (array_slice($photoUrls, 0, 20) as $url) {
            if (! $url || ! str_starts_with($url, 'http')) {
                continue;
            }

            $base64 = $this->photoHelper->downloadAndConvertToBase64($url);
            if (! $base64) {
                continue;
            }

            $decoded = $this->photoHelper->base64ToDecoded($base64);
            if (! $decoded) {
                continue;
            }

            $hash = sha1($decoded['decoded']);

            // Skip duplicates (hash in filename)
            $exists = MasterGallery::where('master_id', $master->id)
                ->where('photo', 'like', "%{$hash}%")
                ->exists();

            if ($exists) {
                continue;
            }

            $path = 'images/' . $flavor . '/' . $hash . '.' . strtolower($decoded['extension']);

            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, $decoded['decoded']);
            }

            MasterGallery::firstOrCreate(
                ['master_id' => $master->id, 'photo' => $path]
            );

            $added++;
        }

        return $added;
    }

    private function resolveGoogleUser(string $app): User
    {
        return User::firstOrCreate(
            ['phone' => 'google_maps_' . $app],
            [
                'name'     => 'Google Maps',
                'phone'    => 'google_maps_' . $app,
                'password' => bcrypt(str()->random(32)),
                'app'      => $app,
            ]
        );
    }
}
