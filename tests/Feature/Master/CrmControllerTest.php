<?php

declare(strict_types=1);

namespace Tests\Feature\Master;

use App\Models\CrmGarageClient;
use App\Models\Master;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createMasterWithUser(string $app = 'carbeat', string $phone = '+380501112233'): array
    {
        $user = User::factory()->create(['phone' => $phone]);

        $master = Master::forceCreate([
            'app' => $app,
            'name' => 'Test Garage',
            'contact_phone' => $phone,
            'service_id' => 1,
            'longitude' => 30,
            'latitude' => 50,
            'description' => 'desc',
            'photo' => 'defaults/avatar.png',
            'user_id' => $user->id,
        ]);

        return [$master, $user];
    }

    public function test_guest_is_redirected_to_master_login(): void
    {
        $this->withHeader('X-App', 'carbeat')
            ->get('/master/schedule')
            ->assertRedirect('/master-login');
    }

    public function test_authenticated_non_master_user_gets_403(): void
    {
        $user = User::factory()->create();

        $this->withHeader('X-App', 'carbeat')
            ->actingAs($user)
            ->getJson('/master-api/crm/snapshot')
            ->assertForbidden();
    }

    public function test_master_from_a_different_brand_cannot_access_this_brand(): void
    {
        [$master, $user] = $this->createMasterWithUser('floxcity');

        // The user's Master row only exists under `floxcity`; hitting the API
        // as `carbeat` must not resolve it (AppScoped + EnsureIsMaster).
        $this->withHeader('X-App', 'carbeat')
            ->actingAs($user)
            ->getJson('/master-api/crm/snapshot')
            ->assertForbidden();
    }

    public function test_snapshot_returns_only_current_brand_data(): void
    {
        [$master, $user] = $this->createMasterWithUser('carbeat');

        CrmGarageClient::withoutGlobalScopes()->create([
            'uuid' => Str::uuid()->toString(),
            'master_id' => $master->id,
            'name' => 'Carbeat Client',
            'phone' => '+380500000001',
            'app' => 'carbeat',
        ]);

        // Same master_id but tagged as a different brand — must never leak
        // into a carbeat-scoped snapshot even though master_id matches.
        CrmGarageClient::withoutGlobalScopes()->create([
            'uuid' => Str::uuid()->toString(),
            'master_id' => $master->id,
            'name' => 'Floxcity Client',
            'phone' => '+380500000002',
            'app' => 'floxcity',
        ]);

        $response = $this->withHeader('X-App', 'carbeat')
            ->actingAs($user)
            ->getJson('/master-api/crm/snapshot');

        $response->assertOk();
        $names = collect($response->json('clients'))->pluck('name')->all();

        $this->assertContains('Carbeat Client', $names);
        $this->assertNotContains('Floxcity Client', $names);
    }

    public function test_sync_creates_updates_and_cancels_an_appointment(): void
    {
        [$master, $user] = $this->createMasterWithUser('carbeat');

        // A garage needs at least one bay before booking into it; the mobile
        // app provisions this via MasterCrmService::ensureDefaultBay(), so
        // mirror that here rather than reaching into the table directly.
        app(\App\Http\Services\MasterCrmService::class)->ensureDefaultBay($master->fresh());
        $bay = \App\Models\MasterBay::withoutGlobalScopes()->where('master_id', $master->id)->first();

        $appointmentId = Str::uuid()->toString();
        $businessDay = now()->toDateString();

        $createResponse = $this->withHeader('X-App', 'carbeat')
            ->actingAs($user)
            ->postJson('/master-api/crm/sync', [
                'businessDay' => $businessDay,
                'changes' => [[
                    'type' => 'create_appointment',
                    'payload' => [
                        'id' => $appointmentId,
                        'bayId' => $bay->uuid,
                        'kind' => 'work',
                        'startsAt' => now()->setTime(10, 0)->toIso8601String(),
                        'endsAt' => now()->setTime(11, 0)->toIso8601String(),
                        'customerName' => 'John Doe',
                        'priceUah' => 1000,
                        'paidAmountUah' => 0,
                        'paymentStatus' => 'pending',
                    ],
                ]],
            ]);

        $createResponse->assertOk();
        $this->assertDatabaseHas('bookings', [
            'crm_uuid' => $appointmentId,
            'customer_name' => 'John Doe',
            'status' => 'confirmed',
        ]);

        $updateResponse = $this->withHeader('X-App', 'carbeat')
            ->actingAs($user)
            ->postJson('/master-api/crm/sync', [
                'businessDay' => $businessDay,
                'changes' => [[
                    'type' => 'update_appointment',
                    'payload' => [
                        'id' => $appointmentId,
                        'customerName' => 'Jane Doe',
                        'priceUah' => 1500,
                    ],
                ]],
            ]);

        $updateResponse->assertOk();
        $this->assertDatabaseHas('bookings', [
            'crm_uuid' => $appointmentId,
            'customer_name' => 'Jane Doe',
            'total_amount' => 1500,
        ]);

        $cancelResponse = $this->withHeader('X-App', 'carbeat')
            ->actingAs($user)
            ->postJson('/master-api/crm/sync', [
                'businessDay' => $businessDay,
                'changes' => [[
                    'type' => 'cancel_appointment',
                    'payload' => ['id' => $appointmentId],
                ]],
            ]);

        $cancelResponse->assertOk();
        $this->assertDatabaseHas('bookings', [
            'crm_uuid' => $appointmentId,
            'status' => 'cancelled',
        ]);

        // Cancelled appointments drop out of the day's snapshot.
        $snapshot = $this->withHeader('X-App', 'carbeat')
            ->actingAs($user)
            ->getJson('/master-api/crm/snapshot?date='.$businessDay);

        $allAppointmentIds = collect($snapshot->json('bays'))
            ->flatMap(fn ($b) => collect($b['appointments'])->pluck('id'))
            ->all();

        $this->assertNotContains($appointmentId, $allAppointmentIds);
    }

    public function test_finance_endpoint_aggregates_bookings_in_range(): void
    {
        [$master, $user] = $this->createMasterWithUser('carbeat');
        app(\App\Http\Services\MasterCrmService::class)->ensureDefaultBay($master->fresh());
        $bay = \App\Models\MasterBay::withoutGlobalScopes()->where('master_id', $master->id)->first();

        \App\Models\Booking::withoutGlobalScopes()->create([
            'app' => 'carbeat',
            'master_id' => $master->id,
            'bay_id' => $bay->id,
            'crm_uuid' => Str::uuid()->toString(),
            'crm_kind' => 'work',
            'start_time' => now()->subDays(1),
            'end_time' => now()->subDays(1)->addHour(),
            'status' => 'confirmed',
            'financial_status' => 'paid',
            'crm_payment_method' => 'cash',
            'total_amount' => 500,
            'paid_amount' => 500,
        ]);

        $response = $this->withHeader('X-App', 'carbeat')
            ->actingAs($user)
            ->getJson('/master-api/crm/finance?from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString());

        $response->assertOk();
        $response->assertJsonPath('cash.cashRevenue', 500);
        $response->assertJsonPath('profitability.totalRevenue', 500);
        $response->assertJsonPath('kpi.completedOrders', 1);
    }
}
