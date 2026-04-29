<?php

namespace App\Http\Services\Import;

use App\Helpers\PhoneHelper;
use App\Helpers\PhotoHelper;
use App\Helpers\ServiceNameMapper;
use App\Http\Services\ClientService;
use App\Http\Services\Master\MasterService;
use App\Models\City;
use App\Models\MasterGallery;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;

class AutoWerkstattImportService implements ImportServiceInterface
{
    private const HOST = 'www.auto-werkstatt.de';

    private const DAY_MAP = [
        'monday'    => 'monday',
        'tuesday'   => 'tuesday',
        'wednesday' => 'wednesday',
        'thursday'  => 'thursday',
        'friday'    => 'friday',
        'saturday'  => 'saturday',
        'sunday'    => 'sunday',
    ];

    public function __construct(
        private readonly MasterService $masterService,
        private readonly ClientService $clientService,
        private readonly PhotoHelper $photoHelper,
    ) {}

    public function canHandle(string $url): bool
    {
        return str_contains($url, 'auto-werkstatt.de');
    }

    public function getDetailLinks(string $listUrl, ?int $maxPages = null): array
    {
        return $this->extractDetailLinks($listUrl, $maxPages);
    }

    public function performImport(
        int $serviceId,
        string $listUrl,
        ?int $limit = null,
        ?callable $onProgress = null,
        ?array $prefetchedDetailUrls = null,
    ): array {
        $imported = 0;
        $skipped  = 0;
        $stopped  = false;

        $detailUrls = $prefetchedDetailUrls ?? $this->extractDetailLinks($listUrl);

        foreach ($detailUrls as $detailUrl) {
            $jobId = $GLOBALS['current_job_id'] ?? '';
            if ($jobId && Cache::store('redis')->get("import_stop_{$jobId}")) {
                Cache::store('redis')->forget("import_stop_{$jobId}");
                $stopped = true;
                break;
            }

            try {
                $dto = $this->scrapeDetail($detailUrl);

                if (empty($dto['phone'])) {
                    $skipped++;
                    Log::info('AutoWerkstatt: kein Telefon', ['url' => $detailUrl]);
                    $onProgress && $onProgress(['imported' => $imported, 'skipped' => $skipped, 'processed' => $imported + $skipped]);
                    continue;
                }

                if (empty($dto['lat']) || empty($dto['lng'])) {
                    $skipped++;
                    Log::warning('AutoWerkstatt import: fehlende Koordinaten', ['url' => $detailUrl]);
                    $onProgress && $onProgress(['imported' => $imported, 'skipped' => $skipped, 'processed' => $imported + $skipped]);
                    continue;
                }

                $dto['phone'] = app(PhoneHelper::class)->normalize($dto['phone'], 'DE');

                // Import only masters with German mobile numbers.
                if (! PhoneHelper::isMobile($dto['phone'])) {
                    $skipped++;
                    Log::info('AutoWerkstatt: non-mobile phone skipped', [
                        'url' => $detailUrl,
                        'phone' => $dto['phone'],
                    ]);
                    $onProgress && $onProgress(['imported' => $imported, 'skipped' => $skipped, 'processed' => $imported + $skipped]);
                    continue;
                }

                $serviceModels = [];
                $seen = [];
                foreach ($dto['services'] as $name) {
                    $canonical = ServiceNameMapper::toCanonical($name);
                    if ($canonical === '' || isset($seen[$canonical])) {
                        continue;
                    }
                    $seen[$canonical] = true;
                    $serviceModels[] = Service::firstOrCreate(['name' => $canonical], ['name' => $canonical]);
                }

                $resolvedServiceId = $serviceId ?: ($serviceModels[0]->id ?? 1);

                // Resolve the German city now so importFromExternal doesn't create
                // a garbage city from the first word of the address string.
                $city = ! empty($dto['city'])
                    ? $this->resolveCity($dto['city'], $dto['postal_code'] ?? null, $dto['lat'], $dto['lng'])
                    : null;

                $payload = [
                    'name'          => $dto['name'],
                    'phone'         => $dto['phone'],
                    'address'       => $dto['address'],
                    'description'   => $dto['description'],
                    'coordinates'   => ['lat' => $dto['lat'], 'lng' => $dto['lng']],
                    'city_id'       => $city?->id,
                    'main_photo'    => $dto['main_photo'],
                    'reviews'       => $dto['reviews'],
                    'working_hours' => $dto['working_hours'],
                    'place_id'      => $dto['place_id'],
                    'rating_google' => $dto['rating'] ?? null,
                ];

                DB::beginTransaction();
                try {
                    $master = $this->masterService->importFromExternal($resolvedServiceId, $payload, $this->clientService);

                    if (! empty($serviceModels)) {
                        $master->services()->syncWithoutDetaching(array_map(fn($s) => $s->id, $serviceModels));
                    }

                    if (! empty($dto['extra_info'])) {
                        $master->extra_info = $dto['extra_info'];
                        $master->saveQuietly();
                    }

                    // Save gallery images
                    if (! empty($dto['gallery'])) {
                        foreach ($dto['gallery'] as $imgUrl) {
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
                                ->where('photo', 'like', "%{$hash}%")
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
                            $path = 'images/' . $fl . '/' . $hash . '.' . strtolower($decoded['extension']);
                            if (! Storage::disk('public')->exists($path)) {
                                Storage::disk('public')->put($path, $decoded['decoded']);
                            }
                            MasterGallery::firstOrCreate(
                                ['master_id' => $master->id, 'photo' => $path],
                                ['master_id' => $master->id, 'photo' => $path],
                            );
                        }
                    }

                    DB::commit();
                    $imported++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('AutoWerkstatt: Master-Import fehlgeschlagen', ['url' => $detailUrl, 'error' => $e->getMessage()]);
                    $skipped++;
                }
            } catch (\Throwable $e) {
                Log::error('AutoWerkstatt: Scraping fehlgeschlagen', ['url' => $detailUrl, 'error' => $e->getMessage()]);
                $skipped++;
            }

            $onProgress && $onProgress(['imported' => $imported, 'skipped' => $skipped, 'processed' => $imported + $skipped]);
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'stopped' => $stopped];
    }

    // ─── private ───────────────────────────────────────────────────────────────

    private function extractDetailLinks(string $listUrl, ?int $maxPages = null): array
    {
        $baseUrl    = $this->stripPageParam($listUrl);
        $firstResp  = Http::withHeaders($this->defaultHeaders())->retry(2, 300)->get($this->withPage($baseUrl, 1));

        if (! $firstResp->successful()) {
            return [];
        }

        $totalPages = $this->extractTotalPages(new Crawler($firstResp->body(), $baseUrl));
        if ($maxPages > 0) {
            $totalPages = min($totalPages, $maxPages);
        }

        $allUrls = [];

        for ($page = 1; $page <= $totalPages; $page++) {
            $resp    = $page === 1 ? $firstResp : Http::withHeaders($this->defaultHeaders())->retry(2, 300)->get($this->withPage($baseUrl, $page));
            $crawler = new Crawler($resp->body(), $listUrl);

            foreach ($this->extractUrlsFromListingPage($crawler) as $url) {
                $allUrls[] = $url;
            }
        }

        return array_values(array_unique($allUrls));
    }

    /**
     * Extract detail page URLs from listing JSON-LD itemListElement.
     * @return string[]
     */
    private function extractUrlsFromListingPage(Crawler $crawler): array
    {
        $urls = [];

        $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $s) use (&$urls) {
            $data = $this->parseJson($s->text(''));
            if (! $data) {
                return;
            }
            foreach ($data as $node) {
                $items = $node['mainEntity']['itemListElement'] ?? [];
                foreach ($items as $entry) {
                    $id = $entry['item']['@id'] ?? '';
                    if ($id === '') {
                        continue;
                    }
                    $url = strtok($id, '#');
                    if ($url && str_contains($url, self::HOST)) {
                        $urls[] = rtrim($url, '/') . '/';
                    }
                }
            }
        });

        // HTML fallback: anchor links matching /city/slug-aXXXXX/
        if (empty($urls)) {
            $crawler->filter('a[href]')->each(function (Crawler $a) use (&$urls) {
                $href = $a->attr('href') ?? '';
                if (preg_match('#^(?:https?://(?:www\.)?auto-werkstatt\.de)?(/[a-z][a-z\-]+/[a-z][^/]+-a[A-Za-z0-9]{5,})/?$#', $href, $m)) {
                    $urls[] = 'https://' . self::HOST . rtrim($m[1], '/') . '/';
                }
            });
        }

        return array_values(array_unique($urls));
    }

    private function extractTotalPages(Crawler $crawler): int
    {
        $max = 1;
        $crawler->filter('ul.pagination .page-item a.page-link')->each(function (Crawler $a) use (&$max) {
            if (preg_match('/page=(\d+)/', $a->attr('href') ?? '', $m)) {
                $max = max($max, (int) $m[1]);
            }
        });
        return max($max, 1);
    }

    /**
     * Scrape one detail page and return a normalised DTO.
     * @return array<string,mixed>
     */
    private function scrapeDetail(string $detailUrl): array
    {
        $resp    = Http::withHeaders($this->defaultHeaders())->retry(2, 300)->get($detailUrl);
        $crawler = new Crawler($resp->body(), $detailUrl);

        $business = $this->extractBusinessBlock($crawler);

        $name        = $business['name'] ?? null;
        $phone       = $business['telephone'] ?? null;
        $description = $business['description'] ?? null;
        $lat         = $business['geo']['latitude'] ?? null;
        $lng         = $business['geo']['longitude'] ?? null;
        $rating      = $business['aggregateRating']['ratingValue'] ?? null;

        $addrBlock  = $business['address'] ?? [];
        $address    = $this->buildAddress($addrBlock);
        $cityName   = trim((string) ($addrBlock['addressLocality'] ?? '')) ?: null;
        $postalCode = trim((string) ($addrBlock['postalCode'] ?? '')) ?: null;
        $services   = $this->extractServices($business);
        $reviews    = $this->extractReviews($business);
        $placeId    = 'auto-werkstatt:' . md5(rtrim($detailUrl, '/'));
        [$mainPhoto, $gallery] = $this->extractImages($business);
        $workingHours = $this->extractOpeningHours($business);
        $extraInfo    = $this->extractExtraInfo($business, $crawler);

        // Phone fallback from HTML
        if (! $phone) {
            $tel = $crawler->filter('a[href^="tel:"]')->first();
            if ($tel->count()) {
                $phone = ltrim($tel->attr('href'), 'tel:');
            }
        }

        return [
            'name'          => $name ?? 'Keine Angabe',
            'phone'         => $phone,
            'address'       => $address,
            'city'          => $cityName,
            'postal_code'   => $postalCode,
            'description'   => $description,
            'lat'           => $lat,
            'lng'           => $lng,
            'main_photo'    => $mainPhoto,
            'gallery'       => $gallery,
            'services'      => $services,
            'reviews'       => $reviews,
            'place_id'      => $placeId,
            'rating'        => $rating,
            'working_hours' => $workingHours,
            'extra_info'    => $extraInfo,
        ];
    }

    /**
     * Extract the AutoRepair block from the page JSON-LD @graph.
     * @return array<string,mixed>
     */
    private function extractBusinessBlock(Crawler $crawler): array
    {
        $result = [];
        $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $s) use (&$result) {
            if ($result) {
                return;
            }
            $data = $this->parseJson($s->text(''));
            if (! $data) {
                return;
            }
            foreach ($data as $node) {
                $type = $node['@type'] ?? '';
                if (is_array($type)) {
                    $type = implode(',', $type);
                }
                if (str_contains(strtolower((string) $type), 'autorepair')
                    || str_contains(strtolower((string) $type), 'localbusiness')
                ) {
                    $result = $node;
                    return;
                }
            }
        });
        return $result;
    }

    /**
     * Extract main photo URL and gallery URLs from the AutoRepair JSON-LD block.
     * Returns [mainPhotoUrl|null, galleryUrls[]].
     *
     * @return array{0: string|null, 1: string[]}
     */
    private function extractImages(array $business): array
    {
        $urls = [];

        $raw = $business['image'] ?? null;
        if ($raw !== null) {
            foreach ((array) $raw as $item) {
                $url = is_array($item) ? ($item['contentUrl'] ?? $item['url'] ?? null) : $item;
                if ($url && is_string($url)) {
                    $urls[] = $url;
                }
            }
        }

        // logo as fallback when no gallery images exist
        if (empty($urls)) {
            $logo = $business['logo'] ?? null;
            $url = is_array($logo) ? ($logo['contentUrl'] ?? $logo['url'] ?? null) : $logo;
            if ($url && is_string($url)) {
                $urls[] = $url;
            }
        }

        return [
            $urls[0] ?? null,
            array_slice($urls, 1, 12),
        ];
    }

    /**
     * Convert openingHoursSpecification JSON-LD to {monday:[{open,close}],...} format.
     * @return array<string,list<array{open:string,close:string}>>|null
     */
    private function extractOpeningHours(array $business): ?array
    {
        $specs = $business['openingHoursSpecification'] ?? null;
        if (empty($specs)) {
            return null;
        }

        $hours = [];
        foreach ((array) $specs as $spec) {
            $days   = (array) ($spec['dayOfWeek'] ?? []);
            $opens  = $spec['opens']  ?? null;
            $closes = $spec['closes'] ?? null;
            if ($opens === null || $closes === null) {
                continue;
            }
            foreach ($days as $dayUri) {
                // dayOfWeek can be "https://schema.org/Monday" or just "Monday"
                $day = strtolower(basename((string) $dayUri));
                if (isset(self::DAY_MAP[$day])) {
                    $hours[self::DAY_MAP[$day]][] = ['open' => (string) $opens, 'close' => (string) $closes];
                }
            }
        }

        return $hours ?: null;
    }

    /**
     * Collect website, social links, and "Weitere Informationen" HTML key-values.
     * @return array<string,mixed>|null
     */
    private function extractExtraInfo(array $business, Crawler $crawler): ?array
    {
        $info = [];

        if (! empty($business['url'])) {
            $info['website'] = $business['url'];
        }

        if (! empty($business['sameAs'])) {
            $info['social'] = array_values((array) $business['sameAs']);
        }

        // "Weitere Informationen" definition list (dl/dt+dd) or table after a matching h2/h3
        $weitereXpath = '//*[self::h2 or self::h3][contains(translate(., '
            . '"ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÜ", "abcdefghijklmnopqrstuvwxyzäöü"), '
            . '"weitere informationen")]';

        try {
            $headings = $crawler->filterXPath($weitereXpath);
            if ($headings->count() > 0) {
                // Try DL sibling first
                $dl = $crawler->filterXPath($weitereXpath . '/following-sibling::dl[1]');
                if ($dl->count() > 0) {
                    $terms = $dl->filter('dt')->each(fn(Crawler $n) => trim($n->text('')));
                    $defs  = $dl->filter('dd')->each(fn(Crawler $n) => trim($n->text('')));
                    foreach ($terms as $i => $term) {
                        if ($term !== '' && isset($defs[$i]) && $defs[$i] !== '') {
                            $info['weitere_informationen'][$term] = $defs[$i];
                        }
                    }
                }
                // Try table sibling
                $table = $crawler->filterXPath($weitereXpath . '/following-sibling::table[1]');
                if ($table->count() > 0) {
                    $table->filter('tr')->each(function (Crawler $tr) use (&$info) {
                        $cells = $tr->filter('td, th');
                        if ($cells->count() >= 2) {
                            $key = trim($cells->eq(0)->text(''));
                            $val = trim($cells->eq(1)->text(''));
                            if ($key !== '' && $val !== '') {
                                $info['weitere_informationen'][$key] = $val;
                            }
                        }
                    });
                }
                // Try ul/li sibling as "Key: Value" lines
                $ul = $crawler->filterXPath($weitereXpath . '/following-sibling::ul[1]');
                if ($ul->count() > 0) {
                    $ul->filter('li')->each(function (Crawler $li) use (&$info) {
                        $text = trim($li->text(''));
                        if (str_contains($text, ':')) {
                            [$k, $v] = explode(':', $text, 2);
                            $k = trim($k);
                            $v = trim($v);
                            if ($k !== '' && $v !== '') {
                                $info['weitere_informationen'][$k] = $v;
                            }
                        }
                    });
                }
            }
        } catch (\Throwable) {
            // XPath errors should not abort the import
        }

        return $info ?: null;
    }

    private function buildAddress(array $addr): ?string
    {
        $parts = array_filter([
            $addr['streetAddress'] ?? null,
            trim(($addr['postalCode'] ?? '') . ' ' . ($addr['addressLocality'] ?? '')),
        ]);
        $address = implode(', ', $parts);
        return $address !== '' ? $address : null;
    }

    /**
     * @return string[]
     */
    private function extractServices(array $business): array
    {
        $services = [];
        foreach ($business['hasOfferCatalog']['itemListElement'] ?? [] as $offer) {
            $name = $offer['itemOffered']['name'] ?? '';
            if ($name !== '') {
                $services[] = $name;
            }
        }
        return array_values(array_unique($services));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function extractReviews(array $business): array
    {
        $reviews = [];
        foreach ($business['review'] ?? [] as $rev) {
            $author = $rev['author']['name'] ?? ($rev['author'] ?? 'Anonym');
            $text   = $rev['reviewBody'] ?? ($rev['description'] ?? '');
            $rating = $rev['reviewRating']['ratingValue'] ?? null;
            if ($text === '') {
                continue;
            }
            $reviews[] = [
                'author' => (string) $author,
                'text'   => (string) $text,
                'rating' => (string) ($rating ?? ''),
            ];
        }
        return $reviews;
    }

    /**
     * Find or create a German city, deduplicating by postal_code first so that
     * "Weilheim" and "Weilheim in Oberbayern" (same PLZ 82362) resolve to one record.
     */
    private function resolveCity(string $name, ?string $postalCode, mixed $lat, mixed $lng): City
    {
        // 1. Match by postal code — the canonical deduplication key
        if ($postalCode) {
            $city = City::where('postal_code', $postalCode)->first();
            if ($city) {
                $updates = [];
                if (! $city->country_code) {
                    $updates['country_code'] = 'de';
                }
                if ($lat && ! $city->latitude) {
                    $updates['latitude']  = $lat;
                    $updates['longitude'] = $lng;
                }
                if ($updates) {
                    $city->update($updates);
                }
                return $city;
            }
        }

        // 2. Match by exact name (restrict to German cities to avoid collision with e.g. "München" in UA data)
        $city = City::where('name', $name)->where('country_code', 'de')->first();
        if (! $city) {
            $city = City::where('name', $name)->whereNull('country_code')->first();
        }
        if ($city) {
            $updates = [];
            if ($postalCode && ! $city->postal_code) {
                $updates['postal_code'] = $postalCode;
            }
            if (! $city->country_code) {
                $updates['country_code'] = 'de';
            }
            if ($lat && ! $city->latitude) {
                $updates['latitude']  = $lat;
                $updates['longitude'] = $lng;
            }
            if ($updates) {
                $city->update($updates);
            }
            return $city;
        }

        // 3. Create new city with all available data
        return City::create(array_filter([
            'name'         => $name,
            'postal_code'  => $postalCode,
            'country_code' => 'de',
            'latitude'     => $lat,
            'longitude'    => $lng,
        ], fn($v) => $v !== null && $v !== ''));
    }

    private function stripPageParam(string $url): string
    {
        $parts = parse_url($url);
        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            unset($query['page']);
        }
        $qs = http_build_query($query);
        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . ($parts['path'] ?? '')
            . ($qs ? "?{$qs}" : '');
    }

    private function withPage(string $baseUrl, int $page): string
    {
        $parts = parse_url($baseUrl);
        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        if ($page > 1) {
            $query['page'] = $page;
        }
        $qs = http_build_query($query);
        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . ($parts['path'] ?? '')
            . ($qs ? "?{$qs}" : '');
    }

    private function defaultHeaders(): array
    {
        return [
            'User-Agent'      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            'Accept-Language' => 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ];
    }

    /**
     * Parse JSON-LD string and return the @graph array, or null on failure.
     * @return array<int,array<string,mixed>>|null
     */
    private function parseJson(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }
        if (! is_array($decoded)) {
            return null;
        }
        return $decoded['@graph'] ?? [$decoded];
    }
}
