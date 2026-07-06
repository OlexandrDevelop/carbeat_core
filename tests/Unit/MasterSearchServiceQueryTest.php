<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Services\Master\MasterSearchService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MasterSearchServiceQueryTest extends TestCase
{
    public function test_get_masters_on_distance_returns_array(): void
    {
        // mock DB::select to avoid raw sql execution. The service now issues two
        // queries per call: the paginated data query and a matching COUNT(*) query
        // (needed so meta.last_page reflects every match, not just the current page).
        DB::shouldReceive('select')
            ->twice()
            ->andReturnUsing(function (string $query, array $params) {
                $this->assertNotEmpty($params);

                if (str_contains($query, 'COUNT(*)')) {
                    return [(object) ['total' => 0]];
                }

                $this->assertStringContainsString('masters.status', $query);
                $this->assertStringContainsString('masters.status_expires_at', $query);

                return [];
            });

        $service = new MasterSearchService;

        $result = $service->getMastersOnDistance(50.0, 30.0, 5.0, [], 10000, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEmpty($result['data']);
        $this->assertSame(0, $result['total']);
    }
}
