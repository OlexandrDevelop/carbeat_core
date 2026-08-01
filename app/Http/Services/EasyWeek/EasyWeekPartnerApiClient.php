<?php

namespace App\Http\Services\EasyWeek;

use App\Exceptions\EasyWeekApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the EasyWeek Partner API
 * (spec: https://github.com/easyweek/api-documentation, EasyWeek-Partner-API.v1.json).
 *
 * Authorized with a single partner-level bearer token (issued manually by
 * octopus@easyweek.io) — no per-master OAuth needed for these read-only calls.
 */
class EasyWeekPartnerApiClient
{
    /**
     * Read-only company configuration: branches, products, employees, working hours.
     */
    public function getCompanyConfig(string $companySlug): array
    {
        $response = $this->client()->get("/bookings/widgets/company/{$companySlug}/type/external");

        return $this->unwrap($response, "getCompanyConfig({$companySlug})");
    }

    /**
     * Available time slots within a date range for a given service/branch.
     * Used to infer whether a master is busy right now: if "now" isn't
     * covered by any free spot, EasyWeek's calendar has them booked.
     */
    public function getCalendarSlots(
        string $companySlug,
        string $rangeStart,
        string $rangeEnd,
        int $productId,
        ?int $branchId = null
    ): array {
        $response = $this->client()->get("/bookings/company/{$companySlug}/calendars", array_filter([
            'range_start' => $rangeStart,
            'range_end' => $rangeEnd,
            'service' => $productId,
            'branch' => $branchId,
        ], static fn ($v) => $v !== null));

        return $this->unwrap($response, "getCalendarSlots({$companySlug})");
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.easyweek.widget_base_url'), '/'))
            ->withToken((string) config('services.easyweek.partner_token'))
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 300, throw: false);
    }

    /**
     * @throws EasyWeekApiException
     */
    private function unwrap(Response $response, string $context): array
    {
        if ($response->failed()) {
            throw new EasyWeekApiException(
                "EasyWeek API request failed [{$context}]: HTTP {$response->status()} — ".$response->body(),
                $response->status()
            );
        }

        return $response->json() ?? [];
    }
}
