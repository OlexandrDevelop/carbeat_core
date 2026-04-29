<?php

namespace App\Http\Services\Import;

use App\Helpers\PhoneHelper;
use App\Helpers\PhotoHelper;
use App\Helpers\ServiceNameMapper;
use App\Http\Services\ClientService;
use App\Http\Services\Master\MasterService;
use App\Models\MasterGallery;
use App\Models\Service;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class MechanicAdvisorImportService implements ImportServiceInterface
{
    private const XPATH_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const XPATH_LOWER = 'abcdefghijklmnopqrstuvwxyz';

    private const URL = 'mechanicadvisor';

    public function __construct(
        private readonly MasterService $masterService,
        private readonly ClientService $clientService,
        private readonly PhotoHelper $photoHelper,
    ) {}

    public function canHandle(string $url): bool
    {
        return str_contains($url, 'mechanicadvisor.com');
    }

    public function getDetailLinks(string $listUrl, ?int $maxPages = null): array
    {
        return $this->extractDetailLinks($listUrl, $maxPages);
    }


    /**
     * Import masters from a RateList rating page.
     *
     * @param int $serviceId Service id to assign (0 to auto-detect per item)
     * @param string $listUrl Full URL to the list page (e.g. https://ratelist.top/l/kyiv/rating-435)
     * @param int|null $limit Optional max items to import
     * @param callable|null $onProgress Optional callback reporting progress
     * @return array{imported:int, skipped:int}
     */
    public function performImport(int $serviceId, string $listUrl, ?int $limit = null, ?callable $onProgress = null, ?array $prefetchedDetailUrls = null): array
    {
        $imported = 0;
        $skipped = 0;
        $stopped = false;

        // Use pre-fetched URLs if provided (prevents double processing)
        $detailUrls = $prefetchedDetailUrls ?? $this->extractDetailLinks($listUrl);
        foreach ($detailUrls as $detailUrl) {
            // Stop flag via redis cache for admin-initiated jobs
            $jobId = $GLOBALS['current_job_id'] ?? '';
            if ($jobId) {
                $stopKey = "import_stop_{$jobId}";
                if (Cache::store('redis')->get($stopKey)) {
                    // clear the flag and stop
                    Cache::store('redis')->forget($stopKey);
                    $stopped = true;
                    break;
                }
            }
            try {
                $dto = $this->scrapeDetail($detailUrl);
                if (empty($dto['phone'])) {
                    $skipped++;
                    if ($onProgress) {
                        $onProgress([
                            'imported' => $imported,
                            'skipped' => $skipped,
                            'processed' => $imported + $skipped
                        ]);
                    }
                    continue;
                }
                // Coordinates are required by DB schema; skip if missing
                if (empty($dto['lat']) || empty($dto['lng'])) {
                    $skipped++;
                    Log::warning('Mechanicadvisor import: missing coordinates', ['url' => $detailUrl]);
                    if ($onProgress) {
                        $onProgress([
                            'imported' => $imported,
                            'skipped' => $skipped,
                            'processed' => $imported + $skipped
                        ]);
                    }
                    continue;
                }

                // Require main photo from specific blocks; skip if missing
                if (empty($dto['main_photo'])) {
                    $skipped++;
                    Log::info('Mechanicadvisor import: missing required main photo from business image block', ['url' => $detailUrl]);
                    if ($onProgress) {
                        $onProgress([
                            'imported' => $imported,
                            'skipped' => $skipped,
                            'processed' => $imported + $skipped
                        ]);
                    }
                    continue;
                }

                // Normalize phone & decide service id if needed
                $dto['phone'] = app(PhoneHelper::class)->normalize($dto['phone']);
                // Prepare Service models for scraped services (create if not exists)
                $serviceModels = [];
                $seenNormalized = [];
                if (! empty($dto['services'])) {
                    foreach ($dto['services'] as $serviceName) {
                        $normalized = ServiceNameMapper::toCanonical($serviceName);
                        if ($normalized === '') { continue; }
                        if (isset($seenNormalized[$normalized])) { continue; }
                        $seenNormalized[$normalized] = true;
                        $serviceModels[] = Service::firstOrCreate(['name' => $normalized], ['name' => $normalized]);
                    }
                }
                // Determine primary service id: user-provided or first scraped service, fallback to 1
                $detectedServiceId = $serviceId ?: ($serviceModels[0]->id ?? 1);

                // Build DTO expected by MasterService::importFromExternal
                $payload = [
                    'name' => $dto['name'] ?? 'No name',
                    'phone' => $dto['phone'] ?? null,
                    'address' => $dto['address'] ?? null,
                    'description' => $dto['description'] ?? null,
                    'coordinates' => [
                        'lat' => $dto['lat'] ?? null,
                        'lng' => $dto['lng'] ?? null,
                    ],
                    'main_photo' => $dto['main_photo'] ?? null,
                    'reviews' => $dto['reviews'] ?? [],
                    'working_hours' => $dto['working_hours'] ?? null,
                    'place_id' => $dto['place_id'] ?? null,
                    'rating_google' => null,
                ];

                DB::beginTransaction();
                try {
                    $master = $this->masterService->importFromExternal($detectedServiceId, $payload, $this->clientService);
                    // Attach services via pivot
                    if (! empty($serviceModels)) {
                        $ids = array_map(fn($s) => $s->id, $serviceModels);
                        $master->services()->syncWithoutDetaching($ids);
                    }
                    // Save gallery photos if any (dedupe by content hash per master)
                    if (! empty($dto['gallery'])) {
                        foreach ($dto['gallery'] as $imgUrl) {
                            $base64 = $this->photoHelper->downloadAndConvertToBase64($imgUrl);
                            if (! $base64) { continue; }
                            $decoded = $this->photoHelper->base64ToDecoded($base64);
                            if (! $decoded) { continue; }
                            $hash = sha1($decoded['decoded']);
                            // Skip if this master already has an image with same hash (stored in filename)
                            $exists = MasterGallery::where('master_id', $master->id)
                                ->where('photo', 'like', "%$hash%")
                                ->exists();
                            if ($exists) { continue; }

                            // Use hash in filename but place under flavor-specific directory to ensure isolation
                            $fl = !empty($master->app) ? (string) $master->app : null;
                            // Resolve runtime config fallback if needed
                            if (empty($fl)) {
                                $cfg = config('app.client');
                                if ($cfg instanceof \App\Enums\AppBrand) $fl = $cfg->value;
                                elseif (is_string($cfg) && $cfg !== '') $fl = $cfg;
                                else $fl = 'carbeat';
                            }
                            $path = 'images/' . $fl . '/' . $hash . '.' . strtolower($decoded['extension']);
                            if (! \Storage::disk('public')->exists($path)) {
                                \Storage::disk('public')->put($path, $decoded['decoded']);
                            }

                            MasterGallery::firstOrCreate([
                                'master_id' => $master->id,
                                'photo' => $path,
                            ], [
                                'master_id' => $master->id,
                                'photo' => $path,
                            ]);
                        }
                    }
                    DB::commit();
                    $imported++;

                    if ($onProgress) {
                        $onProgress([
                            'imported' => $imported,
                            'skipped' => $skipped,
                            'processed' => $imported + $skipped
                        ]);
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Failed to import master', [
                        'url' => $detailUrl,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $skipped++;
                    if ($onProgress) {
                        $onProgress([
                            'imported' => $imported,
                            'skipped' => $skipped,
                            'processed' => $imported + $skipped
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Failed to scrape master', [
                    'url' => $detailUrl,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $skipped++;
                if ($onProgress) {
                    $onProgress([
                        'imported' => $imported,
                        'skipped' => $skipped,
                        'processed' => $imported + $skipped
                    ]);
                }
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'stopped' => $stopped];
    }

    /**
     * Extract business detail links from a listing page.
     * @return array<int,string>
     */
    private function extractDetailLinks(string $listUrl, ?int $maxPages = null): array
    {
        $allUrls = [];

        // Determine total pages from first page
        $baseUrl = $this->stripPageParam($listUrl);
        $firstResp = Http::withHeaders($this->defaultHeaders())->retry(2, 200)->get($this->withPage($baseUrl, 1));
        if (! $firstResp->successful()) { return $allUrls; }
        $firstCrawler = new Crawler($firstResp->body(), $baseUrl);
        $totalPages = $this->extractTotalPages($firstCrawler) ?: 1;
        if ($maxPages && $maxPages > 0) {
            $totalPages = min($totalPages, $maxPages);
        }

        for ($page = 1; $page <= $totalPages; $page++) {
            $pageUrl = $this->withPage($baseUrl, $page);
            $resp = $page === 1 ? $firstResp : Http::withHeaders($this->defaultHeaders())->retry(2, 200)->get($pageUrl);
            if (! $resp->successful()) { continue; }
            $crawler = new Crawler($resp->body(), $pageUrl);

            $urls = [];
            // Primary: compose from list item ids
            $crawler->filter('li.company_card[data-id]')->each(function (Crawler $li) use (&$urls) {
                $id = trim($li->attr('data-id') ?? '');
                if ($id !== '' && ctype_digit($id)) { $urls[] = 'https://ratelist.top/' . $id; }
            });
            // Fallback: explicit hidden link attribute
            if (empty($urls)) {
                $crawler->filter('a[data-hidden-link]')->each(function (Crawler $a) use (&$urls, $pageUrl) {
                    $href = trim($a->attr('data-hidden-link') ?? '');
                    if ($href !== '') { $urls[] = $this->absoluteUrl($href, $pageUrl); }
                });
            }
            // Final fallback: any anchors that look like detail pages with numeric path
            if (empty($urls)) {
                $crawler->filter('a')->each(function (Crawler $a) use (&$urls, $pageUrl) {
                    $href = $a->attr('href') ?? '';
                    if (! $href) { return; }
                    $abs = $this->absoluteUrl($href, $pageUrl);
                    if (preg_match('#^https?://'.self::URL.'\.com/\d{4,}$#', $abs)) { $urls[] = $abs; }
                });
            }

            foreach ($urls as $u) { $allUrls[] = $u; }
        }

        return array_values(array_unique($allUrls));
    }

    private function extractTotalPages(Crawler $crawler): int
    {
        $maxPage = 1;
        // Look for pagination block
        $crawler->filter('.pagination.pagination_js a[data-ci-pagination-page]')->each(function (Crawler $a) use (&$maxPage) {
            $num = (int) ($a->attr('data-ci-pagination-page') ?? 0);
            if ($num > $maxPage) { $maxPage = $num; }
        });
        return $maxPage > 0 ? $maxPage : 1;
    }

    private function stripPageParam(string $url): string
    {
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';
        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            unset($query['page']);
        }
        $qs = http_build_query($query);
        return $scheme . '://' . $host . $path . ($qs ? ('?' . $qs) : '');
    }

    private function withPage(string $baseUrl, int $page): string
    {
        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';
        $query = [];
        if (! empty($parts['query'])) { parse_str($parts['query'], $query); }
        $query['p'] = $page;
        $qs = http_build_query($query);
        return $scheme . '://' . $host . $path . '?' . $qs;
    }

    /**
     * Scrape a business detail page into a DTO.
     * @return array<string,mixed>
     * @throws ConnectionException
     */
    private function scrapeDetail(string $detailUrl): array
    {
        $resp = Http::withHeaders($this->defaultHeaders())->retry(2, 300)->get($detailUrl);
        $html = $resp->body();
        $crawler = new Crawler($html, $detailUrl);

        $name = $this->getName($crawler);
        $phone = $this->getPhone($crawler);
        $address = $this->getAddress($crawler);
        $description = $this->getDescription($crawler);
        $services = $this->getServices($crawler);
        $imageUrls = $this->getImageUrls($crawler);
        $mainPhoto = $this->getMainPhoto($imageUrls);
        $reviews = $this->getReviews($crawler);
        $workingHours = $this->getWorkingHours($crawler);
        $placeId = 'mechadvisor:' . md5($detailUrl);
        $gallery = array_slice($imageUrls, 1, 12);
        return [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'description' => $description,
            'lat' => 0,
            'lng' => 0,
            'main_photo' => $mainPhoto,
            'gallery' => $gallery,
            'reviews' => $reviews,
            'services' => $services,
            'place_id' => $placeId,
            'working_hours' => $workingHours,
        ];
    }

    private function firstText(Crawler $crawler, string $selector): ?string
    {
        $node = $crawler->filter($selector)->first();
        return $node->count() ? trim($node->text('')) : null;
    }

    private function firstAttr(Crawler $crawler, string $selector, string $attr): ?string
    {
        $node = $crawler->filter($selector)->first();
        return $node->count() ? ($node->attr($attr) ?? null) : null;
    }

    private function absoluteUrl(string $href, string $base): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }
        $baseHost = parse_url($base, PHP_URL_SCHEME) . '://' . parse_url($base, PHP_URL_HOST);
        if (! str_starts_with($href, '/')) {
            $path = parse_url($base, PHP_URL_PATH) ?? '/';
            $dir = rtrim(dirname($path), '/');
            return $baseHost . $dir . '/' . $href;
        }
        return $baseHost . $href;
    }

    private function defaultHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            'Accept-Language' => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ];
    }

    private function getName(Crawler $crawler): ?string
    {
        $text = $this->firstText($crawler, 'h1');
        if (! $text) {
            $text = $this->firstAttr($crawler, 'meta[property="og:title"]', 'content')
                ?? $this->firstAttr($crawler, 'meta[name="twitter:title"]', 'content')
                ?? $this->firstAttr($crawler, 'meta[name="title"]', 'content');
        }
        return $this->normalizeWhitespace($text);
    }

    private function getPhone(Crawler $crawler): ?string
    {
        $tel = $this->firstAttr($crawler, 'a[href^="tel:"]', 'href');
        if ($tel && str_starts_with($tel, 'tel:')) {
            $tel = substr($tel, 4);
        }
        if (! $tel) {
            $candidate = $this->firstText($crawler, '.text-neutral-900.font-medium');
            if ($candidate && preg_match('/\+?[\d\s\-\(\)]{8,}/', $candidate, $match)) {
                $tel = $match[0];
            }
        }
        if (! $tel) {
            $textBlob = $crawler->count() ? $crawler->text('') : '';
            if ($textBlob && preg_match('/\+?[\d\s\-\(\)]{8,}/', $textBlob, $match)) {
                $tel = $match[0];
            }
        }
        return $this->normalizeWhitespace($tel);
    }

    private function getAddress(Crawler $crawler): ?string
    {
        $node = $crawler->filterXPath('//span[contains(@class,"text-neutral-900") and contains(@class,"font-semibold") and contains(normalize-space(.), ",")]')->first();
        $address = $node->count() ? $node->text('') : null;
        if (! $address) {
            $meta = $this->firstAttr($crawler, 'meta[property="og:description"]', 'content')
                ?? $this->firstAttr($crawler, 'meta[name="description"]', 'content');
            if ($meta && preg_match('/\d{2,}[^\n]+/', $meta, $match)) {
                $address = $match[0];
            }
        }
        return $this->normalizeWhitespace($address);
    }

    private function getDescription(Crawler $crawler): ?string
    {
        $condition = $this->xpathContainsCaseInsensitive('normalize-space(.)', 'about');
        $node = $crawler->filterXPath(sprintf('//h2[%s]/following-sibling::p[1]', $condition))->first();
        $desc = $node->count() ? $node->text('') : null;
        if (! $desc) {
            $desc = $this->firstAttr($crawler, 'meta[name="description"]', 'content');
        }
        return $this->normalizeWhitespace($desc);
    }

    private function getServices(Crawler $crawler): array
    {
        $services = [];
        $condition = $this->xpathContainsCaseInsensitive('normalize-space(.)', 'services offered');
        $crawler->filterXPath(sprintf('//h3[%s]/following-sibling::*[(self::div or self::section) and contains(@class,"flex") and contains(@class,"flex-wrap")][1]//p', $condition))
            ->each(function (Crawler $node) use (&$services) {
                $text = $this->normalizeWhitespace(str_replace(',', '', $node->text('')));
                if ($text) {
                    $services[] = $text;
                }
            });
        return array_values(array_unique($services));
    }

    private function getImageUrls(Crawler $crawler): array
    {
        $urls = [];
        $condition = $this->xpathContainsCaseInsensitive('normalize-space(.)', 'photos');
        $base = $crawler->getUri() ?? '';
        $crawler->filterXPath(sprintf('//h2[%s]/following-sibling::*[(self::div or self::section)][1]//img', $condition))
            ->each(function (Crawler $img) use (&$urls, $base) {
                $src = $img->attr('src') ?? '';
                if ($src === '') { return; }
                $urls[] = $base ? $this->absoluteUrl($src, $base) : trim($src);
            });
        $urls = array_values(array_filter(array_unique($urls)));
        return $urls;
    }

    private function getMainPhoto(array $imageUrls): ?string
    {
        foreach ($imageUrls as $url) {
            $clean = $this->normalizeWhitespace($url);
            if ($clean) {
                return $clean;
            }
        }
        return null;
    }

    private function getReviews(Crawler $crawler): array
    {
        $reviews = [];
        $condition = $this->xpathContainsCaseInsensitive('normalize-space(.)', 'reviews for');
        $crawler->filterXPath(sprintf('//div[div/h2[%s]]/div[contains(@class,"flex") and contains(@class,"flex-col") and contains(@class,"gap-4")]//div[contains(@class,"md:flex-row")]', $condition))
            ->each(function (Crawler $reviewNode) use (&$reviews) {
                $author = $this->normalizeWhitespace($this->firstText($reviewNode, 'h3') ?? $this->firstText($reviewNode, '.text-neutral-900.font-medium'));
                $title = $this->normalizeWhitespace($this->firstText($reviewNode, 'h4'));
                $body = $this->normalizeWhitespace($this->firstText($reviewNode, 'p'));
                $date = $this->normalizeWhitespace($this->firstText($reviewNode, '.text-sm.text-gray-500'));
                if (! $author && ! $body) { return; }
                $reviews[] = array_filter([
                    'author_name' => $author,
                    'author_text' => $body,
                    'title' => $title,
                    'relative_time_description' => $date,
                ]);
            });
        return $reviews;
    }

    private function getWorkingHours(Crawler $crawler): ?array
    {
        $hours = [];
        $condition = $this->xpathContainsCaseInsensitive('normalize-space(.)', 'business hours');
        $crawler->filterXPath(sprintf('//h3[%s]/following-sibling::div[contains(@class,"flex") and contains(@class,"justify-between")]', $condition))
            ->each(function (Crawler $line) use (&$hours) {
                $dayNode = $line->filter('p');
                if ($dayNode->count() < 2) { return; }
                $day = $this->normalizeWhitespace($dayNode->eq(0)->text(''));
                $time = $this->normalizeWhitespace($dayNode->eq(1)->text(''));
                if ($day && $time) {
                    $hours[] = ['day' => $day, 'time' => $time];
                }
            });
        return $hours ?: null;
    }

    private function normalizeWhitespace(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        return $value === '' ? null : $value;
    }

    private function xpathContainsCaseInsensitive(string $expression, string $needle): string
    {
        return sprintf(
            'contains(translate(%s, "%s", "%s"), "%s")',
            $expression,
            self::XPATH_UPPER,
            self::XPATH_LOWER,
            strtolower($needle)
        );
    }
}
