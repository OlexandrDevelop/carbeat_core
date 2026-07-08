<?php

declare(strict_types=1);

use App\Http\Services\Seo\SeoOverrideBackfillService;
use Illuminate\Database\Migrations\Migration;

/**
 * One-time backfill: generates natural Ukrainian SEO copy (title/description/intro/
 * sections/faq) for every existing city and service, for both brands, so the admin
 * doesn't have to fill in `/admin/seo-content` by hand. Skips any entry that already
 * has content (respects manual edits an admin may already have made).
 *
 * The actual generation logic lives in `SeoOverrideBackfillService`, shared with
 * `php artisan seo:refresh` (run later when data changes — e.g. merging two services
 * in the admin — make previously generated counts/text stale).
 *
 * City/service combinations are intentionally NOT seeded here — that's a much larger
 * (near-combinatorial) set than what was asked for; the live default template
 * (see PublicGuestMapController / SeoContentAdminService) already renders those in
 * natural Ukrainian without needing a stored override.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(SeoOverrideBackfillService::class)->syncAll(overwriteAuto: false);
    }

    public function down(): void
    {
        // Intentionally a no-op: this migration only backfills generated marketing
        // copy (SEO overrides), not schema — there is nothing meaningful to revert.
    }
};
