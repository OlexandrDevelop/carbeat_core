<?php

declare(strict_types=1);

namespace App\Http\Services\Seo;

use App\Enums\AppBrand;
use App\Models\AppSetting;

class SeoOverridesService
{
    public function getAll(?AppBrand $brand = null): array
    {
        return AppSetting::query()
            ->where('key', $this->settingsKey($brand))
            ->value('value') ?? [];
    }

    public function get(string $entryKey, ?AppBrand $brand = null): array
    {
        $all = $this->getAll($brand);

        return is_array($all[$entryKey] ?? null) ? $all[$entryKey] : [];
    }

    public function put(string $entryKey, array $payload, ?AppBrand $brand = null): array
    {
        $all = $this->getAll($brand);
        $this->applyEntry($all, $entryKey, $payload);

        AppSetting::updateOrCreate(
            ['key' => $this->settingsKey($brand)],
            ['value' => $all],
        );

        return $all[$entryKey] ?? [];
    }

    /**
     * Write many entries in a single read-modify-write instead of one round trip per
     * entry. All entries for a brand live in one `AppSetting` row/JSON blob, so writing
     * them one at a time (as a bulk backfill naturally would, entry by entry) means
     * every single write re-reads and re-writes the *entire*, ever-growing blob —
     * `n` round trips moving O(n²) bytes in total for `n` entries. Batching collapses
     * that into one read and one write per brand.
     *
     * @param array<string, array<string, mixed>> $entries entryKey => payload
     */
    public function putMany(array $entries, ?AppBrand $brand = null): void
    {
        if ($entries === []) {
            return;
        }

        $all = $this->getAll($brand);

        foreach ($entries as $entryKey => $payload) {
            $this->applyEntry($all, $entryKey, $payload);
        }

        AppSetting::updateOrCreate(
            ['key' => $this->settingsKey($brand)],
            ['value' => $all],
        );
    }

    private function applyEntry(array &$all, string $entryKey, array $payload): void
    {
        $normalized = array_filter([
            'title' => $this->nullableString($payload['title'] ?? null),
            'description' => $this->nullableString($payload['description'] ?? null),
            'intro' => $this->nullableString($payload['intro'] ?? null),
            'sections' => $this->normalizeArray($payload['sections'] ?? null),
            'faq' => $this->normalizeArray($payload['faq'] ?? null),
            // Marks entries written by the auto-generation pipeline (migration /
            // `seo:refresh`) so it can safely re-generate its own output later without
            // touching text an admin typed by hand via `/admin/seo-content` — manual
            // saves never set this, so its mere absence means "hands off".
            'auto_generated' => array_key_exists('auto_generated', $payload) ? (bool) $payload['auto_generated'] : null,
        ], fn ($value) => $value !== null && $value !== []);

        if ($normalized === []) {
            unset($all[$entryKey]);
        } else {
            $all[$entryKey] = $normalized;
        }
    }

    private function settingsKey(?AppBrand $brand = null): string
    {
        $brand = $brand ?? (
            config('app.client') instanceof AppBrand
                ? config('app.client')
                : AppBrand::CARBEAT
        );

        return 'seo_content_overrides_' . $brand->value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;
        return $value === '' ? null : $value;
    }

    private function normalizeArray(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $items = array_values(array_filter($value, fn ($item) => is_array($item)));

        return $items === [] ? null : $items;
    }
}
