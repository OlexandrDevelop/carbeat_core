<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Master;
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

    public function test_admin_requires_auth_for_guests(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login');
    }
}
