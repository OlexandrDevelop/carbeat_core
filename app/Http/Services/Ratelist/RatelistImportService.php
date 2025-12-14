<?php

namespace App\Http\Services\Ratelist;

use App\Helpers\AutomotiveServiceClassifier;
use App\Helpers\PhoneHelper;
use App\Helpers\PhotoHelper;
use App\Http\Services\ClientService;
use App\Http\Services\Master\MasterService;
use App\Models\MasterGallery;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class RatelistImportService
{
    public function __construct(
        private readonly MasterService $masterService,
        private readonly ClientService $clientService,
        private readonly PhotoHelper $photoHelper,
    ) {}

    /**
     * Public wrapper to get detail links for progress estimation.
     * @return array<int,string>
     */
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
                    Log::warning('Ratelist import: missing coordinates', ['url' => $detailUrl]);
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
                    Log::info('Ratelist import: missing required main photo from business image block', ['url' => $detailUrl]);
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
                        $normalized = $this->normalizeServiceName($serviceName);
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
                    if (preg_match('#^https?://ratelist\.top/\d{4,}$#', $abs)) { $urls[] = $abs; }
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
        $query['page'] = $page;
        $qs = http_build_query($query);
        return $scheme . '://' . $host . $path . '?' . $qs;
    }

    /**
     * Scrape a business detail page into a DTO.
     * @return array<string,mixed>
     */
    private function scrapeDetail(string $detailUrl): array
    {
        $resp = Http::withHeaders($this->defaultHeaders())->retry(2, 300)->get($detailUrl);
        $html = $resp->body();
        $crawler = new Crawler($html, $detailUrl);

        $name = $this->firstText($crawler, 'h1, h2') ?: $this->firstText($crawler, 'title') ?: 'No name';

        // Prefer JSON-LD LocalBusiness block
        $ld = $this->parseJsonLd($crawler);
        $phone = $ld['telephone'] ?? null;
        if (! $phone) {
            $phone = $this->firstAttr($crawler, 'a[href^="tel:"]', 'href');
            if ($phone && str_starts_with($phone, 'tel:')) { $phone = substr($phone, 4); }
            if (! $phone && preg_match('/\+?\d[\d\s\-\(\)]{8,}/u', $html, $m)) {
                $phone = $m[0];
            }
        }

        // Address
        $address = null;
        if (! empty($ld['address'])) {
            $addr = $ld['address'];
            $address = trim(($addr['addressLocality'] ?? '') . ' ' . ($addr['streetAddress'] ?? ''));
        }
        if (! $address) {
            $address = $this->firstText($crawler, 'address, .address, [itemprop="address"]');
        }

        // Coordinates
        $lat = $ld['geo']['latitude'] ?? null;
        $lng = $ld['geo']['longitude'] ?? null;
        if (empty($lat) || empty($lng)) {
            [$lat, $lng] = $this->extractLatLng($html, $crawler);
        }

        // Description
        $description = $this->firstText($crawler, '.description, [itemprop="description"], .about, .content p');
        if (! $description) {
            $description = $this->firstAttr($crawler, 'meta[name="description"]', 'content');
            $description = preg_replace('/👉.*🔥/u', '', $description);
        }

        // Services (best-effort)
        $services = [];
        // New RateList markup
        $crawler->filter('ul.company_page_cat_links li a')->each(function (Crawler $node) use (&$services) {
            $t = trim($node->text(''));
            if ($t !== '') { $services[] = $t; }
        });

		// Photos: prefer business image block; fallback to right slider block
		$imageUrls = [];
		// Primary block
		$crawler->filter('.bussiness_page_image_link_img img.img_flow')->each(function (Crawler $img) use (&$imageUrls, $detailUrl) {
			$src = $img->attr('src') ?? '';
			if ($src) { $imageUrls[] = $this->absoluteUrl($src, $detailUrl); }
		});
		// Fallback block: right slider (data-src on <a>, and <img> within)
		if (empty($imageUrls)) {
			$crawler->filter('#bussiness_page_right a[data-src]')->each(function (Crawler $a) use (&$imageUrls, $detailUrl) {
				$src = $a->attr('data-src') ?? '';
				if ($src) { $imageUrls[] = $this->absoluteUrl($src, $detailUrl); }
			});
			$crawler->filter('#bussiness_page_right img.bussiness_page_one_slide')->each(function (Crawler $img) use (&$imageUrls, $detailUrl) {
				$src = $img->attr('src') ?? '';
				if ($src) { $imageUrls[] = $this->absoluteUrl($src, $detailUrl); }
			});
		}
		$imageUrls = array_values(array_unique($imageUrls));

		$mainPhoto = $imageUrls[0] ?? null;
		$gallery = array_slice($imageUrls, 1, 12);

		// Reviews
        $reviews = [];
        if (! empty($ld['review']) && is_array($ld['review'])) {
            foreach ($ld['review'] as $rev) {
                $author = $rev['author']['name'] ?? ($rev['author'] ?? 'Anonymous');
                $ratingValue = $rev['reviewRating']['ratingValue'] ?? ($rev['ratingValue'] ?? null);
                $text = $rev['description'] ?? '';
                if ($text !== '') {
                    $reviews[] = [
                        'author' => (string) $author,
                        'text' => (string) $text,
                        'rating' => (string) ($ratingValue ?? ''),
                    ];
                }
            }
        }
        if (empty($reviews)) {
            $crawler->filter('.reviews .review, .review-item, [itemprop="review"]')->each(function (Crawler $node) use (&$reviews) {
                $author = trim($this->firstText($node, '.author, .name, [itemprop="author"]')) ?: 'Anonymous';
                $text = trim($this->firstText($node, '.text, .content, [itemprop="reviewBody"]'));
                $ratingText = trim($this->firstText($node, '.rating, [itemprop="ratingValue"]'));
                if ($text !== '') {
                    $reviews[] = [
                        'author' => $author,
                        'text' => $text,
                        'rating' => $ratingText,
                    ];
                }
            });
        }

		// Working hours block
		$workingHours = $this->extractWorkingHours($crawler);

		$placeId = 'ratelist:' . md5($detailUrl);

        return [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'description' => $description,
            'lat' => $lat,
            'lng' => $lng,
            'main_photo' => $mainPhoto,
            'gallery' => $gallery,
            'reviews' => $reviews,
            'services' => $services,
			'place_id' => $placeId,
			'working_hours' => $workingHours,
        ];
    }

	private function extractWorkingHours(Crawler $crawler): array
	{
		$hours = [];
		$dayMap = [
			'Понеділок' => 'monday',
			'Вівторок' => 'tuesday',
			'Середа' => 'wednesday',
			'Четвер' => 'thursday',
			"П'ятниця" => 'friday',
			'Субота' => 'saturday',
			'Неділя' => 'sunday',
		];

		// Initialize all days to empty (closed)
		foreach ($dayMap as $key) {
			$hours[$key] = [];
		}

		$crawler->filter('.company_info_working_hours table tr')->each(function (Crawler $tr) use (&$hours, $dayMap) {
			$tds = $tr->filter('td');
			if ($tds->count() < 2) { return; }
			$dayUa = trim(preg_replace('/\s+/u', ' ', $tds->eq(0)->text('')));
			$timeText = trim(preg_replace('/\s+/u', ' ', $tds->eq(1)->text('')));
			if ($dayUa === '' || ! isset($dayMap[$dayUa])) { return; }
			$key = $dayMap[$dayUa];
			if (mb_stripos($timeText, 'Закрито') !== false) {
				$hours[$key] = [];
				return;
			}
			if (preg_match('/(\d{1,2}:\d{2})\s*[–\-]\s*(\d{1,2}:\d{2})/u', $timeText, $m)) {
				$open = $m[1];
				$close = $m[2];
				$hours[$key] = [[
					'open' => $open,
					'close' => $close,
				]];
			}
		});

		return $hours;
	}

    private function extractLatLng(string $html, Crawler $crawler): array
    {
        // Try common patterns in links (Google Maps, etc.)
        if (preg_match('#@([\d\.\-]+),([\d\.\-]+)#', $html, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }
        if (preg_match('#[?&](?:lat|latitude)=([\d\.\-]+)&(?:lon|lng|longitude)=([\d\.\-]+)#', $html, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }
        // data attributes
        $node = $crawler->filter('[data-lat][data-lng]')->first();
        if ($node->count()) {
            return [
                (float) ($node->attr('data-lat') ?? 0),
                (float) ($node->attr('data-lng') ?? 0),
            ];
        }
        return [null, null];
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

    private function parseJsonLd(Crawler $crawler): array
    {
        $data = [];
        $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $s) use (&$data) {
            $json = trim($s->text(''));
            if ($json === '') { return; }
            try {
                $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                return;
            }
            // Some pages wrap in @graph
            $items = [];
            if (isset($decoded['@type'])) { $items = [$decoded]; }
            elseif (isset($decoded['@graph']) && is_array($decoded['@graph'])) { $items = $decoded['@graph']; }
            elseif (is_array($decoded)) { $items = $decoded; }

            foreach ($items as $item) {
                if (! is_array($item)) { continue; }
                $type = $item['@type'] ?? '';
                if (is_array($type)) { $type = implode(',', $type); }
                if (str_contains(strtolower((string)$type), 'localbusiness')) {
                    $data = $item;
                    return; // take first local business block
                }
            }
        });
        return $data;
    }

    /**
     * Remove Ukrainian city names occurrences from service name and normalize spaces.
     */
    private function normalizeServiceName(string $raw): string
    {
        $name = trim($raw);
        if ($name === '') { return ''; }

        $cities = [
            'Київ','Киев','Києві','Киеве',
            'Львів','Львове',
            'Одеса','Одесі','Одесса','Одессе',
            'Дніпро','Дніпрі','Днепр','Днепре',
            'Харків','Харкові','Харьков','Харькове',
            'Вінниця','Вінниці','Винница','Виннице',
            'Житомир','Житомирі',
            'Запоріжжя','Запоріжжі','Запорожье','Запорожье',
            'Івано-Франківськ','Івано-Франківську','Ивано-Франковск','Ивано-Франковске',
            'Кропивницький','Кропивницькому','Кропивницкий','Кропивницком',
            'Луцьк','Луцьку',
            'Полтава','Полтаві',
            'Тернопіль','Тернополі',
            'Ужгород','Ужгороді',
            'Чернівці','Чернівцях','Черновцы','Черновцах',
            'Черкаси','Черкасах',
            'Чернігів','Чернігові','Чернигов','Чернигове',
            'Хмельницький','Хмельницькому','Хмельницкий','Хмельницком',
            'Суми','Сумах',
            'Рівне','Рівному','Ровно','Ровном',
        ];

        // Remove city tokens case-insensitively
        foreach ($cities as $city) {
            $name = preg_replace('/\b' . preg_quote($city, '/') . '\b/ui', '', $name);
        }

        // Remove extra delimiters and prepositions around removed cities
        $name = preg_replace('/\s{2,}/u', ' ', $name);
        $name = preg_replace('/\s*,\s*/u', ', ', $name);
        $name = trim($name, " \t\n\r\0\x0B-,");

        return trim($name);
    }
}
