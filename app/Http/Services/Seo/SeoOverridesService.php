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

        $normalized = array_filter([
            'title' => $this->nullableString($payload['title'] ?? null),
            'description' => $this->nullableString($payload['description'] ?? null),
            'intro' => $this->nullableString($payload['intro'] ?? null),
            'sections' => $this->normalizeArray($payload['sections'] ?? null),
            'faq' => $this->normalizeArray($payload['faq'] ?? null),
        ], fn ($value) => $value !== null && $value !== []);

        if ($normalized === []) {
            unset($all[$entryKey]);
        } else {
            $all[$entryKey] = $normalized;
        }

        AppSetting::updateOrCreate(
            ['key' => $this->settingsKey($brand)],
            ['value' => $all],
        );

        return $all[$entryKey] ?? [];
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
