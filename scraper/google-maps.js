'use strict';

/**
 * Google Maps scraper — no API key required.
 *
 * PHOTOS work without authentication.
 * REVIEWS require a Google session cookie (see README in this directory).
 *
 * Usage: node google-maps.js <place_id> <name> <address> [maxReviews]
 * Output: JSON to stdout { success, reviews, photos, authenticated, error? }
 *
 * For authenticated review scraping, place your Google session cookies in:
 *   scraper/cookies.json  (see scraper/README.md for instructions)
 */

const { chromium } = require('playwright-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const path = require('path');
const fs = require('fs');
chromium.use(StealthPlugin());

const placeId    = process.argv[2] || '';
const name       = process.argv[3] || '';
const address    = process.argv[4] || '';
const maxReviews = parseInt(process.argv[5] || '30', 10);

// Use system Chrome if available, otherwise Playwright's bundled Chromium
const CHROME_PATH  = fs.existsSync('/usr/bin/google-chrome') ? '/usr/bin/google-chrome' : undefined;
const COOKIES_FILE = path.join(__dirname, 'cookies.json');

main().catch(err => {
    process.stdout.write(JSON.stringify({ success: false, error: err.message, reviews: [], photos: [], authenticated: false }));
});

function out(data) {
    process.stdout.write(JSON.stringify(data));
}

async function main() {
    const cookies = loadCookies();

    const launchOpts = {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-gpu'],
    };
    if (CHROME_PATH) launchOpts.executablePath = CHROME_PATH;

    const browser = await chromium.launch(launchOpts);

    try {
        const context = await browser.newContext({
            userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            locale: 'uk-UA',
            viewport: { width: 1280, height: 900 },
            extraHTTPHeaders: { 'Accept-Language': 'uk-UA,uk;q=0.9,en;q=0.5' },
        });

        // Load auth cookies if available
        if (cookies.length > 0) {
            await context.addCookies(cookies);
        }

        const page = await context.newPage();
        page.on('console', () => {});
        page.on('pageerror', () => {});

        // Navigate to business page
        await navigateToBusinessPage(page, placeId, name, address);

        // Check if authenticated (reviews visible)
        const authenticated = await isAuthenticated(page);

        const reviews = authenticated ? await scrapeReviews(page, maxReviews) : [];
        const photos  = await scrapePhotos(page);

        out({ success: true, reviews, photos, authenticated });
    } finally {
        await browser.close();
    }
}

// ─── Authentication ──────────────────────────────────────────────────────────

function loadCookies() {
    if (!fs.existsSync(COOKIES_FILE)) return [];
    try {
        const raw = fs.readFileSync(COOKIES_FILE, 'utf8');
        const data = JSON.parse(raw);
        return Array.isArray(data) ? data : [];
    } catch {
        return [];
    }
}

async function isAuthenticated(page) {
    // If "limited access" banner is gone and reviews section is present, we're in
    return await page.evaluate(() => {
        const text = document.body.innerText || '';
        return !text.includes('обмеженого доступу') && !text.includes('limited access');
    });
}

// ─── Navigation ─────────────────────────────────────────────────────────────

async function navigateToBusinessPage(page, placeId, name, address) {
    const url = buildSearchUrl(placeId, name, address);
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 40000 });
    await dismissCookies(page);
    await page.waitForTimeout(2500);

    // If still on a search results feed, get the first result URL and navigate directly
    const firstHref = await page.locator('a[href*="/maps/place"]').first().getAttribute('href', { timeout: 2000 }).catch(() => null);
    if (firstHref && page.url().includes('/search/')) {
        const dest = firstHref.startsWith('http') ? firstHref : 'https://www.google.com' + firstHref;
        await page.goto(dest + (dest.includes('?') ? '&' : '?') + 'hl=uk', { waitUntil: 'domcontentloaded', timeout: 35000 });
        await page.waitForTimeout(3000);
    }
}

function buildSearchUrl(placeId, name, address) {
    if (placeId && /^ChIJ/i.test(placeId)) {
        return `https://www.google.com/maps/place/?q=place_id:${encodeURIComponent(placeId)}&hl=uk`;
    }
    const q = [name, address].filter(Boolean).join(' ');
    return `https://www.google.com/maps/search/${encodeURIComponent(q)}?hl=uk`;
}

async function dismissCookies(page) {
    const selectors = ['button[aria-label="Прийняти все"]', 'button[aria-label="Accept all"]', 'form:last-of-type button:last-of-type'];
    for (const sel of selectors) {
        try {
            const btn = page.locator(sel).first();
            if (await btn.isVisible({ timeout: 1500 })) { await btn.click(); await page.waitForTimeout(1000); return; }
        } catch { /* try next */ }
    }
}

// ─── Scrolling ───────────────────────────────────────────────────────────────

async function scrollPanel(page, times = 8) {
    for (let i = 0; i < times; i++) {
        await page.evaluate(() => {
            [...document.querySelectorAll('*')].forEach(el => {
                try {
                    const s = getComputedStyle(el);
                    if ((s.overflowY === 'auto' || s.overflowY === 'scroll') && el.scrollHeight > el.clientHeight + 100) {
                        el.scrollTop += 1500;
                    }
                } catch { /* ignore */ }
            });
        });
        await page.waitForTimeout(600);
    }
    await page.waitForTimeout(800);
}

// ─── Reviews (requires auth) ─────────────────────────────────────────────────

async function scrapeReviews(page, maxReviews) {
    try {
        await openReviewsList(page);
        await scrollPanel(page, 12);
        await expandMoreButtons(page);
        return await extractReviews(page, maxReviews);
    } catch {
        return [];
    }
}

async function openReviewsList(page) {
    const selectors = [
        'button[aria-label*="відгук"]', 'button[aria-label*="review"]', 'button[aria-label*="зірок"]',
        '[role="tab"]:has-text("відгук")', '[role="tab"]:has-text("Відгук")',
        'a:has-text("Переглянути всі відгуки")', 'a:has-text("See all reviews")',
    ];
    for (const sel of selectors) {
        try {
            const btn = page.locator(sel).first();
            if (await btn.isVisible({ timeout: 1000 })) {
                await btn.click();
                await page.waitForTimeout(2500);
                const count = await page.evaluate(() => document.querySelectorAll('[data-review-id], [class*="jJc9Ad"]').length);
                if (count > 0) return;
            }
        } catch { /* try next */ }
    }
}

async function expandMoreButtons(page) {
    try {
        const btns = page.locator('button[jsaction*="expand"], button:has-text("Ще"), button:has-text("More")');
        const n = await btns.count();
        for (let i = 0; i < Math.min(n, 60); i++) {
            try { await btns.nth(i).click({ timeout: 300 }); } catch { /* skip */ }
        }
    } catch { /* ignore */ }
}

async function extractReviews(page, maxReviews) {
    return page.evaluate((max) => {
        const results = [];

        function parseEl(el) {
            let author = 'Anonymous';
            for (const sel of ['.d4r55', '.kvMYJc', '[class*="d4r55"]', '[tabindex="0"] span']) {
                const t = el.querySelector(sel)?.textContent?.trim();
                if (t && t.length > 0 && t.length < 120) { author = t; break; }
            }

            const ratingEl = el.querySelector('[aria-label*="з 5"], [aria-label*="out of 5"], [aria-label*="зірк"], [aria-label*="star"]');
            const ratingMatch = ratingEl?.getAttribute('aria-label')?.match(/(\d+)/);
            const rating = ratingMatch ? Math.min(5, parseInt(ratingMatch[1], 10)) : 0;

            let text = '';
            for (const sel of ['.wiI7pd', '[class*="wiI7pd"]', '.MyEned', '[class*="MyEned"]']) {
                const t = el.querySelector(sel)?.textContent?.trim();
                if (t && t.length > 2) { text = t; break; }
            }

            let date = '';
            for (const sel of ['.rsqaWe', '[class*="rsqaWe"]', '.dehysf']) {
                const t = el.querySelector(sel)?.textContent?.trim();
                if (t) { date = t; break; }
            }

            if (rating === 0 && text.length === 0) return null;
            return { author, rating, text, date };
        }

        const containers = [
            ...document.querySelectorAll('[data-review-id]'),
            ...document.querySelectorAll('[class*="jJc9Ad"]'),
        ];

        const seen = new Set();
        for (const el of containers) {
            if (results.length >= max) break;
            const key = el.getAttribute('data-review-id') || el.textContent?.substring(0, 50);
            if (key && seen.has(key)) continue;
            if (key) seen.add(key);
            const r = parseEl(el);
            if (r) results.push(r);
        }

        return results;
    }, maxReviews);
}

// ─── Photos ──────────────────────────────────────────────────────────────────

async function scrapePhotos(page) {
    // Try clicking the Photos tab
    const photoSelectors = ['[role="tab"]:has-text("Фото")', '[role="tab"]:has-text("Photo")', 'button[aria-label*="Фото"]', 'button[aria-label*="Photo"]'];
    for (const sel of photoSelectors) {
        try {
            const btn = page.locator(sel).first();
            if (await btn.isVisible({ timeout: 1000 })) {
                await btn.click();
                await page.waitForTimeout(2000);
                await scrollPanel(page, 4);
                break;
            }
        } catch { /* try next */ }
    }

    return page.evaluate(() => {
        const seen = new Set();
        const urls = [];

        function normBase(src) {
            if (!src) return null;
            // Skip tiny avatars
            if (/[=\/]s(16|20|24|32|40|48|56|64|72|80|96)\b/.test(src)) return null;
            if (/\/a\/[A-Za-z0-9_\-]+=s\d/.test(src)) return null;

            let b = src.split('?')[0];
            // Strip any Google CDN size/transform params: =w123, =s560, =w123-h456-k-no, etc.
            b = b.replace(/=[swh]\d+([^/]*)?$/, '');
            // Strip /w203-h152-n-k-no/ path segment style
            b = b.replace(/\/[whs]\d+-[whs]\d+[^/]*$/, '');
            return b && b.length > 40 ? b : null;
        }

        document.querySelectorAll('img[src*="googleusercontent.com"]').forEach(img => {
            const base = normBase(img.src || '');
            if (!base || seen.has(base)) return;
            seen.add(base);
            urls.push(base + '=w1600');
        });

        // Background images
        document.querySelectorAll('[style*="googleusercontent"]').forEach(el => {
            const m = (el.getAttribute('style') || '').match(/url\("?(https:\/\/lh[^"')]+)/);
            if (!m) return;
            const base = normBase(m[1]);
            if (base && !seen.has(base)) { seen.add(base); urls.push(base + '=w1600'); }
        });

        return urls.slice(0, 30);
    });
}
