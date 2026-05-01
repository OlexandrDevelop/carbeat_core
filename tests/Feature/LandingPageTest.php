<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\City;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_guest_map_is_public(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Guest Map');
    }

    public function test_marketing_landing_is_still_public(): void
    {
        $this->get('/landing')
            ->assertOk()
            ->assertSee('Landing');
    }

    public function test_public_sto_route_renders_map_page_with_master_seo(): void
    {
        $master = Master::factory()->make([
            'slug' => 'seo-sto-slug',
            'name' => 'SEO STO',
            'latitude' => 50.45,
            'longitude' => 30.52,
            'description' => 'Fast diagnostics and engine repair.',
            'address' => 'Kyiv, Main Street 10',
            'photo' => 'data:image/png;base64,'.base64_encode('x'),
        ]);
        $master->save();

        $this->get('/sto/seo-sto-slug')
            ->assertOk()
            ->assertSee('SEO STO')
            ->assertSee('/sto/seo-sto-slug');
    }

    public function test_legacy_public_master_route_redirects_to_canonical_sto_route(): void
    {
        $master = Master::factory()->make([
            'slug' => 'legacy-sto-slug',
            'latitude' => 50.45,
            'longitude' => 30.52,
            'description' => 'Legacy redirect target.',
            'photo' => 'data:image/png;base64,'.base64_encode('x'),
        ]);
        $master->save();

        $this->get('/m/legacy-sto-slug')
            ->assertRedirect('/sto/legacy-sto-slug');
    }

    public function test_city_route_renders_seo_map_page(): void
    {
        $city = City::create(['name' => 'Berlin', 'latitude' => 52.52, 'longitude' => 13.405]);
        $service = Service::create(['name' => 'Oil Change']);

        $master = Master::create([
            'name' => 'Berlin Garage',
            'contact_phone' => '+49111111111',
            'service_id' => $service->id,
            'city_id' => $city->id,
            'latitude' => 52.52,
            'longitude' => 13.405,
            'description' => 'Berlin city test garage.',
            'address' => 'Alexanderplatz 1',
            'photo' => 'defaults/avatar.png',
            'slug' => 'berlin-garage',
        ]);
        $master->services()->attach($service->id);

        $this->get('/city/berlin')
            ->assertOk()
            ->assertSee('Berlin')
            ->assertSee('/sto/berlin-garage');
    }

    public function test_city_service_route_renders_filtered_seo_map_page(): void
    {
        $city = City::create(['name' => 'Munich', 'latitude' => 48.137, 'longitude' => 11.575]);
        $service = Service::create(['name' => 'Tire Service']);

        $master = Master::create([
            'name' => 'Munich Tire Point',
            'contact_phone' => '+49222222222',
            'service_id' => $service->id,
            'city_id' => $city->id,
            'latitude' => 48.137,
            'longitude' => 11.575,
            'description' => 'Munich tire service test garage.',
            'address' => 'Marienplatz 1',
            'photo' => 'defaults/avatar.png',
            'slug' => 'munich-tire-point',
        ]);
        $master->services()->attach($service->id);

        $this->get('/city/munich/tire-service')
            ->assertOk()
            ->assertSee('Munich')
            ->assertSee('/sto/munich-tire-point');
    }

    public function test_admin_requires_auth_for_guests(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login');
    }
}
