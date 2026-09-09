<?php

namespace App\Http\Services\Visit;

/**
 * User-Agent based filter for Admin/Visits: keeps out search/SEO crawlers,
 * link-preview unfurlers (Facebook, Slack, Telegram, ...), uptime monitors,
 * and headless/automation tools, so the dashboard reflects real visitors.
 *
 * This is necessarily a substring blocklist, not a proof of humanity — it
 * only has to catch the traffic that would otherwise show up as noise in
 * the dashboard, not withstand an adversary.
 */
class BotDetector
{
    private const PATTERNS = [
        // Generic
        'bot', 'crawl', 'spider', 'slurp', 'archiver', 'scraper',

        // Search engines
        'googlebot', 'bingbot', 'yandex', 'baiduspider', 'duckduckbot',
        'applebot', 'sogou', 'exabot', 'seznambot', 'mail.ru_bot',

        // Link-preview / chat unfurlers
        'facebookexternalhit', 'facebot', 'twitterbot', 'slackbot',
        'telegrambot', 'discordbot', 'whatsapp', 'linkedinbot', 'redditbot',
        'skypeuripreview', 'vkshare', 'viber', 'line/', 'pinterest',

        // SEO / security scanners
        'ahrefsbot', 'semrushbot', 'mj12bot', 'dotbot', 'petalbot',
        'blexbot', 'nessus', 'nikto', 'sqlmap', 'nmap',

        // Uptime / monitoring
        'uptimerobot', 'pingdom', 'statuscake', 'site24x7', 'newrelic',

        // Headless browsers / automation
        'headlesschrome', 'phantomjs', 'puppeteer', 'playwright',
        'selenium', 'webdriver',

        // Non-browser HTTP clients (never a real visitor's browser UA)
        'curl/', 'wget/', 'python-requests', 'python-urllib',
        'go-http-client', 'okhttp', 'node-fetch', 'axios/',
        'postmanruntime', 'java/', 'libwww-perl', 'php/',
    ];

    /**
     * A real browser always sends a User-Agent header, so a missing one is
     * itself a bot signal, not something to pass through.
     */
    public static function isBot(?string $userAgent): bool
    {
        if (! $userAgent) {
            return true;
        }

        $userAgent = strtolower($userAgent);

        foreach (self::PATTERNS as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
