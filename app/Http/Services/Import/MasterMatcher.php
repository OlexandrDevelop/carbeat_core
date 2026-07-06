<?php

namespace App\Http\Services\Import;

use App\Models\Master;

class MasterMatcher
{
    /**
     * Words that carry no brand identity in Ukrainian/Russian auto-service listings
     * (generic business-type labels), stripped before comparing names.
     */
    private const NOISE_WORDS = [
        'сто', 'сервис', 'сервіс', 'автосервис', 'автосервіс', 'автоцентр',
        'центр', 'ооо', 'тов', 'пп', 'фоп', 'llc',
    ];

    /**
     * Find an existing master near the given coordinates with a similar name.
     * Used to avoid creating duplicate masters when a phone match isn't found.
     */
    public function findMatch(float $lat, float $lng, string $name, float $radiusKm = 0.15, float $minSimilarity = 65.0): ?Master
    {
        $candidates = Master::query()
            ->select(['id', 'name', 'latitude', 'longitude', 'is_claimed'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw(
                '6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                    + sin(radians(?)) * sin(radians(latitude))
                ) <= ?',
                [$lat, $lng, $lat, $radiusKm]
            )
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $normalizedName = $this->normalize($name);
        $nameTokens = $this->tokens($normalizedName);
        $best = null;
        $bestTokenScore = 0.0;
        $bestCharScore = 0.0;

        foreach ($candidates as $candidate) {
            $candidateNormalized = $this->normalize((string) $candidate->name);
            $candidateTokens = $this->tokens($candidateNormalized);

            $tokenScore = $this->tokenOverlapScore($nameTokens, $candidateTokens);
            similar_text($normalizedName, $candidateNormalized, $charPercent);

            // Rank candidates by whichever metric is more confident for them
            if (max($tokenScore, $charPercent) > max($bestTokenScore, $bestCharScore)) {
                $bestTokenScore = $tokenScore;
                $bestCharScore = $charPercent;
                $best = $candidate;
            }
        }

        // Token overlap (word-set based) catches reordered brand names; char similarity
        // catches near-identical strings. Either signal being confident enough is a match.
        if ($best && ($bestTokenScore >= 50.0 || $bestCharScore >= $minSimilarity)) {
            return $best;
        }

        return null;
    }

    /**
     * Percentage of overlapping tokens relative to the smaller token set —
     * robust to reordered words ("Автосервис Elcars" vs "Elcars СТО").
     *
     * @param  array<int,string>  $a
     * @param  array<int,string>  $b
     */
    private function tokenOverlapScore(array $a, array $b): float
    {
        if (empty($a) || empty($b)) {
            return 0.0;
        }

        $intersection = array_intersect($a, $b);
        $smaller = min(count($a), count($b));

        return (count($intersection) / $smaller) * 100;
    }

    /**
     * @return array<int,string>
     */
    private function tokens(string $normalized): array
    {
        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_values(array_diff($words, self::NOISE_WORDS));

        return array_unique($words);
    }

    private function normalize(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/["\'«»]/u', '', $name) ?? $name;
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $name) ?? $name;

        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }
}
