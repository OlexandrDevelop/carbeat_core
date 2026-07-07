<?php

namespace App\Http\Services\Import;

interface ImportServiceInterface
{
    /**
     * Get detail links for progress estimation.
     *
     * @param  int|null  $maxPages  Upper page bound (inclusive)
     * @param  int|null  $fromPage  Page to start from (defaults to 1)
     * @return array<int,string>
     */
    public function getDetailLinks(string $listUrl, ?int $maxPages = null, ?int $fromPage = null): array;

    /**
     * Import items from a list page.
     *
     * @param  callable|null  $onMasterResult  Optional per-item callback invoked with
     *                                         array{status:string,master_id:?int,city_id:?int,master_name:?string,city_name:?string,skip_reason:?string}
     *                                         for analytics/history recording. Not all implementations invoke it.
     * @return array{imported:int, skipped:int}
     */
    public function performImport(int $serviceId, string $listUrl, ?int $limit = null, ?callable $onProgress = null, ?array $prefetchedDetailUrls = null, ?callable $onMasterResult = null): array;

    /**
     * Check if this importer can handle the given URL.
     */
    public function canHandle(string $url): bool;
}
