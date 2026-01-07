<?php

namespace App\Http\Services\Import;

interface ImportServiceInterface
{
    /**
     * Get detail links for progress estimation.
     * @param string $listUrl
     * @param int|null $maxPages
     * @return array<int,string>
     */
    public function getDetailLinks(string $listUrl, ?int $maxPages = null): array;

    /**
     * Import items from a list page.
     *
     * @param int $serviceId
     * @param string $listUrl
     * @param int|null $limit
     * @param callable|null $onProgress
     * @param array|null $prefetchedDetailUrls
     * @return array{imported:int, skipped:int}
     */
    public function performImport(int $serviceId, string $listUrl, ?int $limit = null, ?callable $onProgress = null, ?array $prefetchedDetailUrls = null): array;

    /**
     * Check if this importer can handle the given URL.
     *
     * @param string $url
     * @return bool
     */
    public function canHandle(string $url): bool;
}

