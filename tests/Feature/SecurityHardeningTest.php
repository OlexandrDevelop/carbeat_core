<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.allow_all_in_local' => false,
            'admin.allowed_user_ids' => [],
            'admin.allowed_emails' => [],
            'admin.allowed_phones' => [],
        ]);
    }

    public function test_public_sto_page_does_not_expose_internal_routes_or_view_paths(): void
    {
        $service = Service::create(['name' => 'Diagnostics']);

        $master = Master::create([
            'name' => 'Secure STO',
            'slug' => 'secure-sto',
            'contact_phone' => '+380501112233',
            'phone' => '+380501112233',
            'service_id' => $service->id,
            'latitude' => 50.45,
            'longitude' => 30.52,
            'description' => 'Security test page.',
            'address' => 'Kyiv, Main Street 10',
            'photo' => 'defaults/avatar.png',
        ]);
        $master->services()->attach($service->id);

        $response = $this->get('/sto/secure-sto');

        $response->assertOk()
            ->assertSee('Secure STO')
            ->assertSee('"seo"')
            ->assertDontSee('admin.api.')
            ->assertDontSee('admin-auth')
            ->assertDontSee('horizon.')
            ->assertDontSee('telescope')
            ->assertDontSee('"pulse"')
            ->assertDontSee('scribe')
            ->assertDontSee('/app/resources/views')
            ->assertDontSee('claim.redirect')
            ->assertDontSee('status-request.respond')
            ->assertDontSee('storage.local');
    }

    public function test_guest_public_page_keeps_only_public_ziggy_routes(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('public.guest-map')
            ->assertSee('landing')
            ->assertSee('robots')
            ->assertDontSee('admin.masters.index')
            ->assertDontSee('logout')
            ->assertDontSee('livewire.update')
            ->assertDontSee('sanctum.csrf-cookie');
    }

    public function test_guest_cannot_access_admin_pages_or_admin_api(): void
    {
        $this->get('/admin/masters')->assertRedirect('/login');

        $apiResponse = $this->getJson('/admin-api/masters');
        self::assertContains($apiResponse->getStatusCode(), [401, 403]);

        $destructiveResponse = $this->postJson('/admin-api/maintenance/truncate');
        self::assertContains($destructiveResponse->getStatusCode(), [401, 403, 419]);
    }

    public function test_non_admin_user_cannot_access_admin_pages_or_admin_api(): void
    {
        $user = User::factory()->create([
            'phone' => '+380500000001',
        ]);

        $this->actingAs($user)
            ->get('/admin/masters')
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson('/admin-api/masters')
            ->assertForbidden();
    }

    public function test_allowed_admin_can_access_admin_pages_and_admin_ziggy_stays_trimmed(): void
    {
        $admin = User::factory()->create([
            'phone' => '+380500000099',
        ]);

        config(['admin.allowed_phones' => [$admin->phone]]);

        $response = $this->actingAs($admin)->get('/admin/masters');

        $response->assertOk()
            ->assertSee('admin.masters.index')
            ->assertSee('logout')
            ->assertDontSee('admin.api.masters.list')
            ->assertDontSee('admin.auth.verify_otp')
            ->assertDontSee('horizon.')
            ->assertDontSee('telescope')
            ->assertDontSee('scribe');
    }

    public function test_admin_tool_gates_allow_only_configured_admins(): void
    {
        $admin = User::factory()->create([
            'phone' => '+380500000010',
        ]);
        $user = User::factory()->create([
            'phone' => '+380500000011',
        ]);

        config(['admin.allowed_phones' => [$admin->phone]]);

        self::assertTrue(Gate::forUser($admin)->allows('viewPulse'));
        self::assertTrue(Gate::forUser($admin)->allows('viewHorizon'));
        self::assertTrue(Gate::forUser($admin)->allows('viewTelescope'));

        self::assertFalse(Gate::forUser($user)->allows('viewPulse'));
        self::assertFalse(Gate::forUser($user)->allows('viewHorizon'));
        self::assertFalse(Gate::forUser($user)->allows('viewTelescope'));
    }

    public function test_admin_tool_routes_are_not_public_when_registered(): void
    {
        foreach (['telescope', 'horizon.index', 'pulse', 'scribe', 'scribe.openapi'] as $routeName) {
            if (! Route::has($routeName)) {
                continue;
            }

            $response = $this->get(route($routeName, [], false));
            self::assertContains($response->getStatusCode(), [302, 403, 404]);
        }
    }
}
