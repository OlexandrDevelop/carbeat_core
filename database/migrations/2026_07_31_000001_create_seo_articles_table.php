<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Standalone informational guide pages (e.g. "what is chemical peeling", "how often to
 * change oil") — not tied to any single city or master, unlike the rest of the public SEO
 * surface. These exist to capture broad top-of-funnel search queries and cross-link into
 * the city/service pages, mirroring the informational-article layer competitors run
 * alongside their location-based listings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_articles', function (Blueprint $table) {
            $table->id();
            $table->string('app');
            $table->string('slug');
            $table->string('title');
            $table->text('intro')->nullable();
            $table->json('sections')->nullable();
            $table->json('faq')->nullable();
            $table->string('related_service_name')->nullable();
            $table->timestamps();

            $table->unique(['app', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_articles');
    }
};
