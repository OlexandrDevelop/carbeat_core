<?php

namespace App\Http\Services\EasyWeek;

class EasyWeekSlugParser
{
    /**
     * Known public hostnames EasyWeek uses for company booking pages.
     * Confirmed from the OAuth2/Partner API docs: easyweek.io (canonical) and
     * eyw.me (short link). Custom domains are not handled here.
     */
    private const KNOWN_HOSTS = ['easyweek.io', 'eyw.me', 'widget.easyweek.io'];

    /**
     * Extract the company slug from a pasted EasyWeek booking-page URL, or
     * accept a bare slug directly.
     */
    public function parse(string $input): ?string
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        if (! str_contains($input, '/') && ! str_contains($input, '.')) {
            return $this->normalizeSlug($input);
        }

        $url = str_contains($input, '://') ? $input : "https://{$input}";
        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $host = strtolower(preg_replace('/^www\./', '', $parts['host']));
        if (! in_array($host, self::KNOWN_HOSTS, true)) {
            return null;
        }

        $path = trim((string) ($parts['path'] ?? ''), '/');
        if ($path === '') {
            return null;
        }

        $slug = explode('/', $path)[0];

        return $this->normalizeSlug($slug);
    }

    private function normalizeSlug(string $slug): ?string
    {
        $slug = trim($slug);

        return preg_match('/^[a-zA-Z0-9_-]+$/', $slug) === 1 ? $slug : null;
    }
}
