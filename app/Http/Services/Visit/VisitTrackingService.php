<?php

namespace App\Http\Services\Visit;

use App\Models\VisitEvent;
use App\Models\VisitSession;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class VisitTrackingService
{
    /**
     * Record one pageview/click event, creating the visit session on its
     * first event and bumping its counters on every subsequent one.
     */
    public function record(Request $request, array $data): VisitSession
    {
        try {
            return $this->store($request, $data);
        } catch (QueryException $e) {
            // Two beacons for a brand-new session (e.g. pageview + an
            // immediate click) can race to insert the same session_token.
            // The loser just re-reads the row the winner created.
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            return $this->store($request, $data);
        }
    }

    private function store(Request $request, array $data): VisitSession
    {
        $session = VisitSession::firstOrNew(['session_token' => $data['session_token']]);

        if (! $session->exists) {
            $referrer = $data['referrer'] ?? null;

            $session->fill([
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
                'device_type' => $this->detectDeviceType($request->userAgent()),
                'referrer' => $referrer,
                'referrer_host' => $this->extractHost($referrer),
                'landing_path' => $data['path'] ?? null,
                'started_at' => now(),
                'pageviews_count' => 0,
                'clicks_count' => 0,
            ]);
        }

        if ($user = $request->user()) {
            $session->user_id = $user->id;
        }
        $session->last_seen_at = now();

        if ($data['type'] === VisitEvent::TYPE_CLICK) {
            $session->clicks_count++;
        } else {
            $session->pageviews_count++;
        }

        $session->save();

        $element = $data['element'] ?? [];

        $session->events()->create([
            'type' => $data['type'],
            'path' => $data['path'] ?? null,
            'full_url' => $data['url'] ?? null,
            'referrer' => $data['referrer'] ?? null,
            'element_tag' => $element['tag'] ?? null,
            'element_text' => isset($element['text']) ? mb_substr((string) $element['text'], 0, 255) : null,
            'element_id' => $element['id'] ?? null,
            'element_classes' => isset($element['class']) ? mb_substr((string) $element['class'], 0, 255) : null,
            'element_href' => $element['href'] ?? null,
            'meta' => $data['meta'] ?? null,
            'created_at' => now(),
        ]);

        return $session;
    }

    private function detectDeviceType(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        return match (true) {
            (bool) preg_match('/iPad|Tablet/i', $userAgent) => 'tablet',
            (bool) preg_match('/Mobile|iPhone|Android/i', $userAgent) => 'mobile',
            default => 'desktop',
        };
    }

    private function extractHost(?string $referrer): ?string
    {
        if (! $referrer) {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return $host ? strtolower($host) : null;
    }
}
