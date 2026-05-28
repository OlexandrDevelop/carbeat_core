# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Carbeat Core is a **multi-brand, dual-interface platform** for managing and browsing a catalog of service providers (auto mechanics, beauty salons, etc.). It runs two brands from a single codebase: **Carbeat** and **Floxcity**, selected at runtime via the `X-App` HTTP header, `APP_CLIENT` env var, or hostname.

The app has two surfaces:
1. **Web (Inertia/Vue)** — public-facing SEO landing, admin dashboard
2. **Mobile API** — JSON REST API consumed by Flutter mobile apps (identified by `Dart/` user-agent)

## Tech Stack

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Vue 3 + TypeScript + Inertia.js + Tailwind CSS
- **Auth**: JWT (`tymon/jwt-auth`) for mobile API; Laravel session for web/admin
- **Queue/Cache**: Redis via Predis
- **Realtime**: Separate Node.js Socket.IO server in `realtime/` that subscribes to Redis pub/sub
- **SMS**: TurboSMS (`daaner/turbosms`) — OTP-based phone auth
- **Push**: Firebase FCM (per-brand credential files in `keys/`)
- **Monitoring**: Laravel Telescope (dev), Horizon (queue dashboard), Pulse, Sentry

## Commands

### Development

```bash
# Full dev stack (server + queue + logs + vite)
composer run dev

# Or individually:
php artisan serve
npm run dev
php artisan queue:listen --tries=1
php artisan pail --timeout=0    # log streaming
```

### Testing

```bash
# All tests
php artisan test

# Single test file
php artisan test tests/Unit/MasterServiceTest.php

# Single test method
php artisan test --filter testMethodName

# PHPUnit directly
./vendor/bin/phpunit tests/Unit/MasterServiceTest.php
```

Tests use `.env.testing` which configures SQLite in-memory. The `DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:` are set there — do **not** run feature tests against a real MySQL database.

### Static Analysis

```bash
./vendor/bin/phpstan analyse          # level 5, covers app/
```

### Code Style

```bash
./vendor/bin/pint                     # Laravel Pint (PHP)
npm run lint                          # ESLint + Prettier (JS/TS/Vue)
```

### Frontend Build

```bash
npm run build                         # vue-tsc check + Vite SSR + client builds
```

### Docker

```bash
docker-compose up -d                  # Start all services
docker-compose exec app php artisan migrate
```

Services: `app` (PHP-FPM), `nginx` (port 100), `db` (MySQL 8, port 3311), `redis` (port 6380), `vite` (port 5173), `queue`, `scheduler`, `socketio`.

### Artisan

```bash
php artisan sitemap:generate
php artisan sitemap:generate-clean
php artisan subscriptions:sync
php artisan smart-random-statuses:sync
php artisan masters:generate-thumbnails
php artisan masters:generate-slugs
```

## Architecture

### Multi-Brand System

`AppBrand` enum (`app/Enums/AppBrand.php`) has two values: `carbeat` and `floxcity`. The `DetectApp` middleware resolves the brand on every request and stores it in `config('app.client')`.

**`AppScoped` trait** (`app/Models/Traits/AppScoped.php`) is used on models that are brand-partitioned (e.g., `Master`, `Review`, `Subscription`, `RefreshToken`). It automatically adds a global `WHERE app = ?` scope and sets `app` on `creating`. Always be aware of this scope when writing raw queries or tests.

Brand-specific Vue pages live under `resources/js/Pages/Carbeat/` and `resources/js/Pages/Floxcity/`. Admin pages under `resources/js/Pages/Admin/` are shared.

### Authentication Flow

Mobile API uses a **JWT access token + opaque refresh token** pattern:
1. `POST /api/v1/auth/request-otp` → sends OTP via TurboSMS
2. `POST /api/v1/auth/verify-otp` → verifies OTP, returns `{ access_token, refresh_token }`
3. `POST /api/v1/auth/refresh` → exchanges refresh token for new access token
4. JWT guard is `auth:api`; `TokenService` manages both token types

Web/admin uses Laravel session auth. Admin login via OTP at `/admin-auth/request-otp` + `/admin-auth/verify-otp`.

### Route Structure

- `routes/api_v1.php` — mobile/Flutter REST API (prefix: `/api/v1/`)
- `routes/web.php` — public Inertia pages + includes `admin.php`
- `routes/admin.php` — two groups: `/admin` (Inertia pages) and `/admin-api` (JSON API), both guarded by `auth` + `admin.access` + `admin.brand`

### Service Layer

Business logic lives in `app/Http/Services/`. Key services:

| Service | Responsibility |
|---|---|
| `Master/MasterSearchService` | Raw SQL geo-distance query with filters |
| `Master/MasterService` | CRUD, photo upload, gallery management |
| `Master/MasterAvailabilityService` | Reads/writes Redis availability flags |
| `Appointment/AppointmentRedisService` | Redis key management for availability (`{brand}:master:{id}:available`) |
| `SmartRandomStatusService` | Simulates "online" status for unclaimed masters via scheduled rotation |
| `SubscriptionService` | Verifies in-app purchases (iOS/Android), syncs `is_premium` to Masters |
| `TokenService` | JWT access + hashed refresh token lifecycle |
| `SmsService` | OTP generation/verification via TurboSMS |
| `MasterCrmService` | CRM snapshot: bays, bookings, garage clients, vehicles, chat threads |
| `MobileActivityService` | Redis-backed Dart client activity tracking |

### Subscription & Plan Gates

Two middleware aliases guard premium API routes:
- `active.subscription` → `EnsureActiveSubscription` (user has a non-expired subscription)
- `plan.feature:booking_management` → `EnsurePlanAllowsFeature` (plan includes the feature)

Trial mode is configurable via `SUBSCRIPTION_TRIAL_ENABLED` / `SUBSCRIPTION_TRIAL_DAYS`.

### Real-time Availability

Masters can set themselves available/unavailable via the API. This writes a Redis key `{brand}:master:{id}:available` with a TTL (`AVAILABILITY_TTL_SECONDS`, default 3600). The `realtime/server.js` Node.js process subscribes to Redis pub/sub and broadcasts events to mobile clients via Socket.IO. The `SmartRandomStatusService` runs every 15 minutes via the scheduler to simulate availability for unclaimed masters.

### CRM Module

The CRM (accessed via `/api/v1/crm/`) is a master-facing garage management system with:
- **MasterBay** — physical service bays/lifts in the garage
- **CrmGarageClient** — garage's own client records
- **CrmGarageVehicle** — vehicles linked to garage clients
- **MasterServiceCatalogItem** — master's own service price list
- **CrmChatThread / CrmMessage** — internal communication threads

CRM is synced offline-first: the mobile app does a full `GET /crm/snapshot` on login and `POST /crm/sync` to push local changes.

### Frontend Structure

```
resources/js/
├── Pages/
│   ├── Admin/         # Shared admin dashboard pages
│   ├── Carbeat/       # Carbeat brand public pages
│   └── Floxcity/      # Floxcity brand public pages
├── Layouts/           # Inertia layout wrappers
├── composables/       # Vue composables
├── lib/               # Utility libraries
├── shared/            # Shared display-label helpers
├── types/             # TypeScript types
├── i18n.ts            # vue-i18n setup (en/uk)
└── app.ts / ssr.ts    # Inertia app entry points
```

Translations are in `resources/lang/en.json` and `resources/lang/uk.json`. The `SetLocale` middleware resolves locale from `Accept-Language`.

### Scheduled Jobs

| Schedule | Command |
|---|---|
| Daily 00:00 | `sitemap:generate` (brand-aware, writes to `storage/app/public/sitemap-{brand}.xml`) |
| Daily 00:10 | `sitemap:generate-clean` |
| Twice daily | `subscriptions:sync` |
| Daily 00:00 | `telescope:prune --hours=48` |
| Every 15 min | `masters:generate-thumbnails` |
| Every 15 min | `smart-random-statuses:sync` |

## Key Conventions

### AppScoped Models

When writing migrations, seeds, factories, or raw queries for models that use `AppScoped`, explicitly set or filter the `app` column. In tests, either set `config(['app.client' => AppBrand::CARBEAT])` before creating records, or use `withoutGlobalScope('app')` when querying all brands.

### API Error Responses

The `bootstrap/app.php` exception handler normalizes all API errors to JSON. Never return Blade/HTML from `api/*` routes. Use `abort()` with HTTP status codes — the handler converts them to `{ message: "..." }` or `{ error: "..." }`.

### JWT Guard vs Sanctum

Mobile API uses `auth:api` (JWT). The web admin uses `auth` (session). Sanctum (`auth:sanctum`) exists in the codebase but is not actively used for main flows. Do not mix guards.

### Firebase Credentials

Per-brand FCM credential JSON files are referenced by `FCM_TOKEN_PATH_CARBEAT` and `FCM_TOKEN_PATH_FLOXCITY` env vars, stored in `keys/` (gitignored).

