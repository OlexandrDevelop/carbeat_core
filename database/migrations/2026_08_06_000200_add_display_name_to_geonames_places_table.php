<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GeoNames' `name` column for Ukrainian settlements is the Latin
 * transliteration (e.g. "Ivano-Frankivsk"), not Cyrillic — so a driver
 * typing in Ukrainian (see RepairRequestController::citySuggestions())
 * never matched anything against it. The actual Cyrillic forms are buried
 * inside `alternate_names`, mixed in with Russian and other-language
 * variants with no language tag to tell them apart in this export format.
 *
 * `display_name` picks the best Ukrainian-looking Cyrillic candidate from
 * that list: Ukrainian has letters (і/ї/є/ґ) Russian never uses, and typical
 * Ukrainian adjectival endings (-ський/-ська/-ське) differ from the Russian
 * equivalent (-ский/-ская/-ское) even when no unique letter is present
 * (e.g. Краснопартизанська vs Краснопартизанская). Falls back to `name`
 * (Latin) when no Cyrillic candidate exists at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('geonames_places', function (Blueprint $table) {
            $table->string('display_name', 200)->nullable()->after('name');
        });

        $this->backfillDisplayNames();

        Schema::table('geonames_places', function (Blueprint $table) {
            $table->dropIndex('geonames_places_search_index');
            $table->index(['country_code', 'feature_class', 'display_name'], 'geonames_places_search_index');
        });
    }

    public function down(): void
    {
        Schema::table('geonames_places', function (Blueprint $table) {
            $table->dropIndex('geonames_places_search_index');
            $table->index(['country_code', 'feature_class', 'name'], 'geonames_places_search_index');
            $table->dropColumn('display_name');
        });
    }

    private function backfillDisplayNames(): void
    {
        DB::table('geonames_places')
            ->orderBy('geoname_id')
            ->select(['geoname_id', 'name', 'alternate_names'])
            ->chunkById(2000, function ($rows) {
                // A real UPDATE, not upsert(): MySQL validates an
                // insert-with-on-duplicate-key statement's VALUES against
                // every NOT NULL column with no default (name, latitude,
                // longitude...) even though only geoname_id/display_name
                // are given and the UPDATE branch is always the one taken —
                // a single CASE-based UPDATE avoids that entirely, in one
                // round-trip per chunk.
                $whenClauses = [];
                $bindings = [];
                $ids = [];

                foreach ($rows as $row) {
                    /** @var \stdClass $row */
                    $whenClauses[] = 'WHEN ? THEN ?';
                    $bindings[] = $row->geoname_id;
                    $bindings[] = $this->pickUkrainianName($row->name, $row->alternate_names);
                    $ids[] = $row->geoname_id;
                }

                $sql = 'update `geonames_places` set `display_name` = case `geoname_id` '
                    .implode(' ', $whenClauses)
                    .' end where `geoname_id` in ('.implode(',', array_fill(0, count($ids), '?')).')';

                DB::update($sql, [...$bindings, ...$ids]);
            }, 'geoname_id');
    }

    private function pickUkrainianName(string $primaryName, ?string $alternateNames): string
    {
        if (empty($alternateNames)) {
            return $primaryName;
        }

        $best = null;
        $bestScore = 0; // only switch to an alternate if it scores strictly better than "no signal"

        foreach (explode(',', $alternateNames) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '' || ! preg_match('/\p{Cyrillic}/u', $candidate)) {
                continue; // skip Latin transliterations and other scripts
            }

            $score = 2 * (int) preg_match_all('/[іїєґІЇЄҐ]/u', $candidate);
            $score -= 2 * (int) preg_match_all('/[ыэъёЫЭЪЁ]/u', $candidate);
            $score += preg_match('/(ська|ський|ське|цька|цький|цьке)$/u', $candidate) ? 1 : 0;
            $score -= preg_match('/(ская|ский|ское|цкая|цкий|цкое)$/u', $candidate) ? 1 : 0;

            if ($best === null || $score > $bestScore) {
                $best = $candidate;
                $bestScore = $score;
            }
        }

        return $best ?? $primaryName;
    }
};
