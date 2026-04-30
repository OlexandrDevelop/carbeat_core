<?php

declare(strict_types=1);

namespace Tests\Feature;

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

    public function test_admin_requires_auth_for_guests(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login');
    }
}
