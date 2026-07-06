<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Services\Master\MasterSearchService;
use App\Http\Services\Master\MasterService;
use App\Http\Services\PaginatorService;
use App\Models\Master;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class MasterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaginatorService $paginator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paginator = new PaginatorService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(?MasterSearchService $searchMock = null): MasterService
    {
        $search = $searchMock ?? Mockery::mock(MasterSearchService::class);

        return new MasterService(new Master, $this->paginator, $search);
    }

    public function test_get_masters_on_distance_returns_paginator(): void
    {
        $items = [
            (object) ['id' => 1, 'name' => 'A'],
            (object) ['id' => 2, 'name' => 'B'],
        ];

        $searchMock = Mockery::mock(MasterSearchService::class);
        $searchMock->shouldReceive('getMastersOnDistance')
            ->once()
            ->andReturn(['data' => $items, 'total' => count($items)]);

        $service = $this->makeService($searchMock);
        $paginator = $service->getMastersOnDistance(1, 50.0, 30.0, 5.0, []);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertCount(2, $paginator->items());
    }

    public function test_create_or_update_creates_master_and_maps_phone(): void
    {
        $srv = Service::forceCreate(['name' => 'Barber']);

        $data = [
            'phone' => '+380601234567',
            'name' => 'Tester',
            'service_id' => $srv->id,
            'latitude' => 50.45,
            'longitude' => 30.52,
            'description' => 'Test desc',
            'photo' => 'data:image/png;base64,'.base64_encode('fake'),
        ];

        Storage::fake('public');

        $service = $this->makeService();
        $master = $service->createOrUpdate($data);

        $this->assertDatabaseHas('masters', [
            'id' => $master->id,
            'contact_phone' => '+380601234567',
        ]);
    }

    public function test_create_or_update_with_invalid_photo_throws_exception(): void
    {
        $srv = Service::forceCreate(['name' => 'Barber']);

        $data = [
            'phone' => '+380601999999',
            'name' => 'BadPhoto',
            'service_id' => $srv->id,
            'latitude' => 50.45,
            'longitude' => 30.52,
            'description' => 'Desc',
            'photo' => 'not_base64',
        ];

        $service = $this->makeService();

        $this->expectException(\Exception::class);
        $service->createOrUpdate($data);
    }

    public function test_generate_slug_returns_expected_slug(): void
    {
        $srv = Service::forceCreate(['name' => 'Plumber']);
        $master = Master::factory()->create([
            'name' => 'John Doe',
            'service_id' => $srv->id,
        ]);

        $slug = MasterService::generateSlug($master);
        $this->assertStringContainsString('john-doe', $slug);
        $this->assertStringContainsString('plumber', $slug);
    }
}
