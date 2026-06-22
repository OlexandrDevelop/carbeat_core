# Google Maps Scraper

Scrapes **photos** (no auth) and **reviews** (requires Google session) for each master.

## Setup

```bash
cd scraper
npm install
npx playwright install chromium
```

## Usage

```bash
# Photos only (no auth required)
php artisan masters:scrape-google --photos --limit=100

# Single master by ID
php artisan masters:scrape-google --id=14 --photos

# With reviews (requires cookies.json — see below)
php artisan masters:scrape-google --photos --limit=50

# Force re-scrape masters that already have reviews
php artisan masters:scrape-google --photos --force --limit=20
```

## Getting reviews (Google auth cookies)

Google Maps requires sign-in to show reviews. To scrape reviews:

1. Open **Google Chrome** and go to [maps.google.com](https://maps.google.com)
2. Sign into your Google account
3. Install browser extension **"Cookie-Editor"** (Chrome Web Store)
4. On maps.google.com, open Cookie-Editor and click **Export → Export as JSON**
5. Save the exported JSON to `scraper/cookies.json`

> `cookies.json` is gitignored — never commit it.

Without `cookies.json`, the scraper still works for **photos**.

## Output format

```json
{
  "success": true,
  "authenticated": true,
  "reviews": [
    { "author": "Іван К.", "rating": 5, "text": "Відмінний сервіс!", "date": "3 місяці тому" }
  ],
  "photos": [
    "https://lh3.googleusercontent.com/...=w1600"
  ]
}
```
