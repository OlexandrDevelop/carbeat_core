<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_landing_is_public(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Landing');
    }

    public function test_root_redirects_to_admin_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect('/admin/masters');
    }

    public function test_admin_requires_auth_for_guests(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login');
    }
}

