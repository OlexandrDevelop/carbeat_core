<?php

namespace App\Http\Services\Import;

use App\Helpers\PhoneHelper;
use App\Helpers\PhotoHelper;
use App\Helpers\ServiceNameMapper;
use App\Http\Services\ClientService;
use App\Http\Services\Master\MasterService;
use App\Models\City;
use App\Models\Master;
use App\Models\MasterGallery;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class VseStoImportService implements ImportServiceInterface
{
    /**
     * Maps vse-sto settlement names to their oblast (region) capital, so that
     * satellite towns roll up under the same city as the rest of their region.
     * Regional capitals not listed here map to themselves.
     */
    private const UA_OBLAST_CAPITALS = [
        'павлоград' => 'Дніпро',
        'кривий ріг' => 'Дніпро',
        'нікополь' => 'Дніпро',
        "кам'янське" => 'Дніпро',
        'дніпродзержинськ' => 'Дніпро',
        'бердянськ' => 'Запоріжжя',
        'енергодар' => 'Запоріжжя',
        'токмак' => 'Запоріжжя',
        'мелітополь' => 'Запоріжжя',
        'бориспіль' => 'Київ',
        'бровари' => 'Київ',
        'біла церква' => 'Київ',
        'фастів' => 'Київ',
        'переяслав' => 'Київ',
        'переяслав-хмельницький' => 'Київ',
        'євпаторія' => 'Сімферополь',
        'джанкой' => 'Сімферополь',
        'керч' => 'Сімферополь',
        'судак' => 'Сімферополь',
        'феодосія' => 'Сімферополь',
        'ялта' => 'Сімферополь',
        'горлівка' => 'Донецьк',
        'краматорськ' => 'Донецьк',
        'макіївка' => 'Донецьк',
        'маріуполь' => 'Донецьк',
        'кременчук' => 'Полтава',
        'золотоноша' => 'Черкаси',
        'умань' => 'Черкаси',
        'гайсин' => 'Вінниця',
        'мукачево' => 'Ужгород',
    ];

    public function __construct(
        private readonly MasterService $masterService,
        private readonly ClientService $clientService,
        private readonly PhotoHelper $photoHelper,
        private readonly MasterMatcher $masterMatcher,
        private readonly NominatimGeocoder $geocoder,
    ) {}

    public function canHandle(string $url): bool
    {
        return str_contains($url, 'vse-sto.com.ua');
    }

    /**
     * @return array<int,string>
     */
    public function getDetailLinks(string $listUrl, ?int $maxPages = null, ?int $fromPage = null): array
    {
        return $this->extractDetailLinks($listUrl, $maxPages, $fromPage);
    }

    /**
     * @return array{imported:int, skipped:int, matched:int}
     */
    public function performImport(int $serviceId, string $listUrl, ?int $limit = null, ?callable $onProgress = null, ?array $prefetchedDetailUrls = null, ?callable $onMasterResult = null): array
    {
        $imported = 0;
        $skipped = 0;
        $matched = 0;
        $stopped = false;

        $detailUrls = $prefetchedDetailUrls ?? $this->extractDetailLinks($listUrl);
        if ($limit) {
            $detailUrls = array_slice($detailUrls, 0, $limit);
        }

        foreach ($detailUrls as $detailUrl) {
            $jobId = $GLOBALS['current_job_id'] ?? '';
            if ($jobId) {
                $stopKey = "import_stop_{$jobId}";
                if (Cache::store('redis')->get($stopKey)) {
                    Cache::store('redis')->forget($stopKey);
                    $stopped = true;
                    break;
                }
            }

            try {
                $dto = $this->scrapeDetail($detailUrl);

                if (empty($dto['phone'])) {
                    $skipped++;
                    $this->reportProgress($onProgress, $imported, $skipped, $matched);
                    $this->reportMasterResult($onMasterResult, 'skipped', $dto, null, null, 'no_phone');

                    continue;
                }
                if (empty($dto['lat']) || empty($dto['lng'])) {
                    $skipped++;
                    Log::warning('VseSTO import: missing coordinates', ['url' => $detailUrl]);
                    $this->reportProgress($onProgress, $imported, $skipped, $matched);
                    $this->reportMasterResult($onMasterResult, 'skipped', $dto, null, null, 'no_coordinates');

                    continue;
                }
                if (! empty($dto['address']) && str_contains($dto['address'], 'Дивіться адреси')) {
                    // Network entry without a single physical address (e.g. franchise landing page)
                    $skipped++;
                    Log::info('VseSTO import: skipping network entry without a single address', ['url' => $detailUrl]);
                    $this->reportProgress($onProgress, $imported, $skipped, $matched);
                    $this->reportMasterResult($onMasterResult, 'skipped', $dto, null, null, 'network_entry');

                    continue;
                }
                if (empty($dto['main_photo'])) {
                    $skipped++;
                    $this->reportProgress($onProgress, $imported, $skipped, $matched);
                    $this->reportMasterResult($onMasterResult, 'skipped', $dto, null, null, 'no_photo');

                    continue;
                }

                $dto['phone'] = app(PhoneHelper::class)->normalize($dto['phone']);

                $serviceModels = [];
                $seenNormalized = [];
                if (! empty($dto['services'])) {
                    foreach ($dto['services'] as $serviceName) {
                        $normalized = ServiceNameMapper::toCanonical(trim($serviceName));
                        if ($normalized === '') {
                            continue;
                        }
                        if (isset($seenNormalized[$normalized])) {
                            continue;
                        }
                        $seenNormalized[$normalized] = true;
                        $serviceModels[] = Service::firstOrCreate(['name' => $normalized], ['name' => $normalized]);
                    }
                }
                $detectedServiceId = $serviceId ?: ($serviceModels[0]->id ?? 1);

                DB::beginTransaction();
                try {
                    $existingByPhone = Master::where('contact_phone', $dto['phone'])->exists();
                    $matchedMaster = $existingByPhone ? null : $this->masterMatcher->findMatch(
                        (float) $dto['lat'],
                        (float) $dto['lng'],
                        (string) $dto['name']
                    );

                    if ($matchedMaster) {
                        $this->enrichMatchedMaster($matchedMaster, $dto, $serviceModels);
                        DB::commit();
                        $matched++;
                        $this->reportProgress($onProgress, $imported, $skipped, $matched);
                        $this->reportMasterResult($onMasterResult, 'matched', $dto, $matchedMaster, $matchedMaster->city);

                        continue;
                    }

                    $city = ! empty($dto['city']) ? $this->resolveCity($dto['city']) : null;

                    $payload = [
                        'name' => $dto['name'] ?? 'No name',
                        'phone' => $dto['phone'],
                        'address' => $dto['address'] ?? null,
                        'description' => $dto['description'] ?? null,
                        'city_id' => $city?->id,
                        'coordinates' => [
                            'lat' => $dto['lat'],
                            'lng' => $dto['lng'],
                        ],
                        'main_photo' => $dto['main_photo'],
                        // Reviews are attached ourselves via attachReviews() below (per-author identity),
                        // not through importFromExternal's shared path (which would collapse every
                        // reviewer onto the master's own phone number).
                        'reviews' => [],
                        'working_hours' => $dto['working_hours'] ?? null,
                        'place_id' => $dto['place_id'] ?? null,
                        'rating_google' => null,
                    ];

                    $master = $this->masterService->importFromExternal($detectedServiceId, $payload, $this->clientService);
                    if ($city && $master->city_id !== $city->id) {
                        // importFromExternal's nearest-distance fallback can override city_id after save; our
                        // oblast-aware resolution should win for vse-sto data.
                        $master->city_id = $city->id;
                        $master->save();
                    }
                    if (! empty($serviceModels)) {
                        $ids = array_map(fn ($s) => $s->id, $serviceModels);
                        $master->services()->syncWithoutDetaching($ids);
                    }
                    $this->attachGallery($master, $dto['gallery'] ?? []);
                    $this->attachReviews($master, $dto['reviews'] ?? [], $dto['place_id'] ?? '');

                    DB::commit();
                    $imported++;
                    $this->reportProgress($onProgress, $imported, $skipped, $matched);
                    $this->reportMasterResult($onMasterResult, 'created', $dto, $master, $city);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Failed to import VseSTO master', [
                        'url' => $detailUrl,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $skipped++;
                    $this->reportProgress($onProgress, $imported, $skipped, $matched);
                    $this->reportMasterResult($onMasterResult, 'skipped', $dto, null, null, 'db_error');
                }
            } catch (\Throwable $e) {
                Log::error('Failed to scrape VseSTO master', [
                    'url' => $detailUrl,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $skipped++;
                $this->reportProgress($onProgress, $imported, $skipped, $matched);
                $this->reportMasterResult($onMasterResult, 'skipped', [], null, null, 'scrape_error');
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'matched' => $matched, 'stopped' => $stopped];
    }

    /**
     * @param  array<string,mixed>  $dto
     */
    private function reportMasterResult(?callable $onMasterResult, string $status, array $dto, ?Master $master, ?City $city, ?string $skipReason = null): void
    {
        if (! $onMasterResult) {
            return;
        }
        $onMasterResult([
            'status' => $status,
            'master_id' => $master?->id,
            'city_id' => $master?->city_id ?? $city?->id,
            'master_name' => $master?->name ?? ($dto['name'] ?? null),
            'city_name' => $city?->name ?? ($dto['city'] ?? null),
            'skip_reason' => $skipReason,
        ]);
    }

    private function reportProgress(?callable $onProgress, int $imported, int $skipped, int $matched): void
    {
        if (! $onProgress) {
            return;
        }
        $onProgress([
            'imported' => $imported,
            'skipped' => $skipped,
            'matched' => $matched,
            'processed' => $imported + $skipped + $matched,
        ]);
    }

    /**
     * Enrich an existing master matched by proximity/name instead of creating a duplicate.
     */
    private function enrichMatchedMaster(Master $master, array $dto, array $serviceModels): void
    {
        if (! $master->is_claimed) {
            $dirty = false;
            if (empty($master->address) && ! empty($dto['address'])) {
                $master->address = $dto['address'];
                $dirty = true;
            }
            if (empty($master->description) && ! empty($dto['description'])) {
                $master->description = $dto['description'];
                $dirty = true;
            }
            if (empty($master->working_hours) && ! empty($dto['working_hours'])) {
                $master->working_hours = $dto['working_hours'];
                $dirty = true;
            }
            if (empty($master->city_id) && ! empty($dto['city'])) {
                $city = $this->resolveCity($dto['city']);
                if ($city) {
                    $master->city_id = $city->id;
                    $dirty = true;
                }
            }
            if ($dirty) {
                $master->save();
            }
        }

        if (! empty($serviceModels)) {
            $ids = array_map(fn ($s) => $s->id, $serviceModels);
            $master->services()->syncWithoutDetaching($ids);
        }

        $this->attachGallery($master, $dto['gallery'] ?? []);
        $this->attachReviews($master, $dto['reviews'] ?? [], $dto['place_id'] ?? '');
    }

    /**
     * Attach scraped reviews to a master, preserving the real author name per review
     * (each author gets its own deterministic synthetic-phone Client AND User, see
     * reviewerClientPhone()) and deduplicating on repeated imports via firstOrCreate.
     *
     * Both the admin panel and the public API resolve the displayed reviewer name via
     * review.user_id -> users.name (not client.name), so the User itself must carry
     * the scraped author name rather than being hardcoded to the admin account.
     *
     * @param  array<int,array{author?:string,text?:string,rating?:string,date?:?Carbon}>  $reviews
     */
    private function attachReviews(Master $master, array $reviews, string $placeId): void
    {
        foreach ($reviews as $review) {
            $author = $review['author'] ?? 'Anonymous';
            $syntheticPhone = $this->reviewerClientPhone($placeId, $author);

            $user = User::firstOrCreate(
                ['phone' => $syntheticPhone],
                ['name' => $author]
            );

            $client = $this->clientService->createOrUpdate([
                'name' => $author,
                'phone' => $syntheticPhone,
                'user_id' => $user->id,
            ]);
            $parsedRating = 0;
            if (! empty($review['rating']) && preg_match('/(\d+)/', $review['rating'], $m)) {
                $parsedRating = (int) $m[1];
            }
            $master->reviews()->firstOrCreate([
                'review' => $review['text'] ?? '',
                'rating' => $parsedRating,
                'user_id' => $client->user_id ?? $user->id,
                'master_id' => $master->id,
            ], [
                'reviewed_at' => $review['date'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int,string>  $gallery
     */
    private function attachGallery(Master $master, array $gallery): void
    {
        foreach ($gallery as $imgUrl) {
            $base64 = $this->photoHelper->downloadAndConvertToBase64($imgUrl);
            if (! $base64) {
                continue;
            }
            $decoded = $this->photoHelper->base64ToDecoded($base64);
            if (! $decoded) {
                continue;
            }
            $hash = sha1($decoded['decoded']);
            $exists = MasterGallery::where('master_id', $master->id)
                ->where('photo', 'like', "%$hash%")
                ->exists();
            if ($exists) {
                continue;
            }

            $fl = ! empty($master->app) ? (string) $master->app : null;
            if (empty($fl)) {
                $cfg = config('app.client');
                if ($cfg instanceof \App\Enums\AppBrand) {
                    $fl = $cfg->value;
                } elseif (is_string($cfg) && $cfg !== '') {
                    $fl = $cfg;
                } else {
                    $fl = 'carbeat';
                }
            }
            $path = 'images/'.$fl.'/'.$hash.'.'.strtolower($decoded['extension']);
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

    /**
     * @return array<int,string>
     */
    private function extractDetailLinks(string $listUrl, ?int $maxPages = null, ?int $fromPage = null): array
    {
        if (str_contains($listUrl, '.xml')) {
            return $this->extractDetailLinksFromSitemap($listUrl, $maxPages, $fromPage);
        }

        return $this->extractDetailLinksFromListing($listUrl, $maxPages, $fromPage);
    }

    /**
     * @return array<int,string>
     */
    private function extractDetailLinksFromSitemap(string $sitemapUrl, ?int $maxPages = null, ?int $fromPage = null): array
    {
        $resp = Http::withHeaders($this->defaultHeaders())->retry(2, 300)->get($sitemapUrl);
        if (! $resp->successful()) {
            return [];
        }

        $xml = @simplexml_load_string($resp->body());
        if ($xml === false) {
            return [];
        }

        $urls = [];
        foreach ($xml->url as $entry) {
            $loc = trim((string) $entry->loc);
            if ($loc !== '') {
                $urls[] = $loc;
            }
        }
        $urls = array_values(array_unique($urls));

        // Treat sitemap entries as if paginated in chunks of 20 (matching real listing page size)
        if ($fromPage || $maxPages) {
            $from = max(1, $fromPage ?: 1);
            $offset = ($from - 1) * 20;
            $length = $maxPages ? max(0, ($maxPages - $from + 1) * 20) : null;
            $urls = array_slice($urls, $offset, $length);
        }

        return $urls;
    }

    /**
     * @return array<int,string>
     */
    private function extractDetailLinksFromListing(string $listUrl, ?int $maxPages = null, ?int $fromPage = null): array
    {
        $allUrls = [];

        $baseUrl = $this->stripPageParam($listUrl);
        $firstResp = Http::withHeaders($this->defaultHeaders())->retry(2, 200)->get($this->withPage($baseUrl, 1));
        if (! $firstResp->successful()) {
            return $allUrls;
        }
        $firstCrawler = new Crawler($firstResp->body(), $baseUrl);
        $totalPages = $this->extractTotalPages($firstCrawler) ?: 1;
        if ($maxPages && $maxPages > 0) {
            $totalPages = min($totalPages, $maxPages);
        }

        for ($page = $fromPage ?: 1; $page <= $totalPages; $page++) {
            $pageUrl = $this->withPage($baseUrl, $page);
            $resp = $page === 1 ? $firstResp : Http::withHeaders($this->defaultHeaders())->retry(2, 200)->get($pageUrl);
            if (! $resp->successful()) {
                continue;
            }
            $crawler = new Crawler($resp->body(), $pageUrl);

            $crawler->filter('li.service-item a.service-item-link[href]')->each(function (Crawler $a) use (&$allUrls, $pageUrl) {
                $href = trim($a->attr('href') ?? '');
                if ($href !== '') {
                    $allUrls[] = $this->absoluteUrl($href, $pageUrl);
                }
            });
        }

        return array_values(array_unique($allUrls));
    }

    private function extractTotalPages(Crawler $crawler): int
    {
        $maxPage = 1;
        $crawler->filter('.pagination-links .pagination a')->each(function (Crawler $a) use (&$maxPage) {
            $text = trim($a->text(''));
            if (ctype_digit($text)) {
                $num = (int) $text;
                if ($num > $maxPage) {
                    $maxPage = $num;
                }
            }
        });

        return $maxPage;
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

        return $scheme.'://'.$host.$path.($qs ? ('?'.$qs) : '');
    }

    private function withPage(string $baseUrl, int $page): string
    {
        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';
        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $query['page'] = $page;
        $qs = http_build_query($query);

        return $scheme.'://'.$host.$path.'?'.$qs;
    }

    /**
     * @return array<string,mixed>
     */
    private function scrapeDetail(string $detailUrl): array
    {
        $resp = Http::withHeaders($this->defaultHeaders())->retry(2, 300)->get($detailUrl);
        $html = $resp->body();
        $crawler = new Crawler($html, $detailUrl);

        $name = $this->firstText($crawler, '.company-title') ?: 'No name';

        $phone = $this->firstAttr($crawler, 'a[href^="tel:"]', 'href');
        if ($phone && str_starts_with($phone, 'tel:')) {
            $phone = substr($phone, 4);
        }

        $mapNode = $crawler->filter('#companyMap')->first();
        $address = $mapNode->count() ? trim($mapNode->attr('data-address') ?? '') : null;
        $lat = $mapNode->count() ? (float) ($mapNode->attr('data-lat') ?? 0) : null;
        $lng = $mapNode->count() ? (float) ($mapNode->attr('data-lng') ?? 0) : null;
        if (! $lat || ! $lng) {
            $lat = null;
            $lng = null;
        }

        $description = $this->firstText($crawler, '.company-desc');
        if (! $description) {
            $description = $this->firstAttr($crawler, 'meta[name="description"]', 'content');
        }

        $services = [];
        $crawler->filter('#anchor-works .company-info-list a.btn')->each(function (Crawler $node) use (&$services) {
            $t = trim($node->text(''));
            if ($t !== '') {
                $services[] = $t;
            }
        });

        $mainPhoto = null;
        $mainNode = $crawler->filter('.main-image a[data-fancybox]')->first();
        if ($mainNode->count()) {
            $mainPhoto = $this->absoluteUrl($mainNode->attr('href') ?? '', $detailUrl);
        }

        $gallery = [];
        $crawler->filter('.other-images a[data-fancybox]')->each(function (Crawler $a) use (&$gallery, $detailUrl) {
            $href = $a->attr('href') ?? '';
            if ($href) {
                $gallery[] = $this->absoluteUrl($href, $detailUrl);
            }
        });
        $gallery = array_values(array_unique($gallery));
        $gallery = array_slice($gallery, 0, 12);

        $reviews = [];
        $crawler->filter('ul.reviews li.review.reviewItem')->each(function (Crawler $node) use (&$reviews) {
            $author = trim($this->firstText($node, '.review-name')) ?: 'Anonymous';
            $text = trim($this->firstText($node, '.review-content'));
            $rating = '';
            $starsNode = $node->filter('span.stars')->first();
            if ($starsNode->count()) {
                $class = $starsNode->attr('class') ?? '';
                if (preg_match('/stars-([\d.]+)/', $class, $m)) {
                    $rating = $m[1];
                }
            }
            $date = null;
            $dateText = trim($this->firstText($node, '.review-date') ?? '');
            if ($dateText !== '') {
                try {
                    $date = Carbon::createFromFormat('d.m.Y', $dateText)->startOfDay();
                } catch (\Throwable) {
                    $date = null;
                }
            }
            if ($text !== '') {
                $reviews[] = ['author' => $author, 'text' => $text, 'rating' => $rating, 'date' => $date];
            }
        });

        $city = $this->firstText($crawler, '.breadcrumbs-list li:first-child a');

        // vse-sto serves city/street in Russian (and sometimes pre-renaming street names).
        // Reverse-geocoding the scraped coordinates gives the current official Ukrainian names;
        // fall back to the scraped text above if geocoding is unavailable or finds nothing.
        if ($lat && $lng) {
            $geocoded = $this->geocoder->reverse($lat, $lng);
            if (! empty($geocoded['city'])) {
                $city = $geocoded['city'];
            }
            if (! empty($geocoded['road'])) {
                $address = trim($geocoded['road'].(! empty($geocoded['house_number']) ? ', '.$geocoded['house_number'] : ''));
            }
        }

        $workingHours = $this->extractWorkingHours($crawler);

        $internalId = null;
        if (preg_match('#/sto/(\d+)-#', $detailUrl, $m)) {
            $internalId = $m[1];
        }
        $placeId = 'vse-sto:'.($internalId ?: md5($detailUrl));

        return [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'description' => $description,
            'city' => $city,
            'lat' => $lat,
            'lng' => $lng,
            'main_photo' => $mainPhoto,
            'gallery' => $gallery,
            'reviews' => $reviews,
            'services' => $services,
            'working_hours' => $workingHours,
            'place_id' => $placeId,
        ];
    }

    private function extractWorkingHours(Crawler $crawler): array
    {
        $dayMap = [
            'понеділок' => 'monday',
            'вівторок' => 'tuesday',
            'середа' => 'wednesday',
            'четвер' => 'thursday',
            'четверг' => 'thursday',
            "п'ятниця" => 'friday',
            'субота' => 'saturday',
            'неділя' => 'sunday',
        ];

        $hours = [];
        foreach (array_unique(array_values($dayMap)) as $key) {
            $hours[$key] = [];
        }

        $crawler->filter('.schedule-list .schedule-list-item')->each(function (Crawler $item) use (&$hours, $dayMap) {
            $dayUa = mb_strtolower(trim($this->firstText($item, '.day') ?? ''));
            $timeText = trim($this->firstText($item, '.time') ?? '');
            if ($dayUa === '' || ! isset($dayMap[$dayUa])) {
                return;
            }
            $key = $dayMap[$dayUa];
            if (mb_stripos($timeText, 'Закрито') !== false || $timeText === '') {
                $hours[$key] = [];

                return;
            }
            if (preg_match('/(\d{1,2}:\d{2})\s*[–\-]\s*(\d{1,2}:\d{2})/u', $timeText, $m)) {
                $hours[$key] = [['open' => $m[1], 'close' => $m[2]]];
            }
        });

        return $hours;
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
        if ($href === '') {
            return $href;
        }
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }
        $baseHost = parse_url($base, PHP_URL_SCHEME).'://'.parse_url($base, PHP_URL_HOST);
        if (! str_starts_with($href, '/')) {
            $path = parse_url($base, PHP_URL_PATH) ?? '/';
            $dir = rtrim(dirname($path), '/');

            return $baseHost.$dir.'/'.$href;
        }

        return $baseHost.$href;
    }

    /**
     * Resolve (or create) the Ukrainian city for a scraped settlement name, grouping
     * satellite towns under their oblast capital via UA_OBLAST_CAPITALS.
     */
    private function resolveCity(string $settlementName): ?City
    {
        $settlementName = trim($settlementName);
        if ($settlementName === '') {
            return null;
        }

        $capital = self::UA_OBLAST_CAPITALS[mb_strtolower($settlementName)] ?? $settlementName;

        $city = City::where('name', $capital)->where('country_code', 'ua')->first();
        if (! $city) {
            $city = City::where('name', $capital)->whereNull('country_code')->first();
        }
        if ($city) {
            if (! $city->country_code) {
                $city->update(['country_code' => 'ua']);
            }

            return $city;
        }

        return City::create(['name' => $capital, 'country_code' => 'ua']);
    }

    /**
     * Deterministic synthetic phone key so each distinct review author gets their
     * own Client record (ClientService::createOrUpdate dedupes strictly by phone),
     * instead of every review collapsing onto the master's own phone number.
     */
    private function reviewerClientPhone(string $placeId, string $author): string
    {
        return 'vsereview:'.substr(md5($placeId.'|'.mb_strtolower(trim($author))), 0, 20);
    }

    private function defaultHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            'Accept-Language' => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ];
    }
}
