<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Master;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class BookingAndAvailabilityTest extends TestCase
{
    /**
     * Master marks available, list shows available
     */
    public function test_master_marks_available_and_list_shows_available(): void
    {
        $master = Master::factory()->create([
            'latitude' => 50.4501,
            'longitude' => 30.5234,
        ]);

        $this->postJson("/api/masters/{$master->id}/availability")
            ->assertStatus(200);

        $resp = $this->getJson("/api/masters?lat={$master->latitude}&lng={$master->longitude}&available=1")
            ->assertStatus(200)
            ->json();

        $ids = collect($resp['data'] ?? [])->pluck('id')->all();
        $this->assertContains($master->id, $ids, 'Master should appear as available');
    }

    /**
     * Master marks unavailable, list shows not available
     */
    public function test_master_marks_unavailable_and_list_filters_it_out(): void
    {
        $master = Master::factory()->create([
            'latitude' => 50.4501,
            'longitude' => 30.5234,
        ]);

        // Set available then unset
        $this->postJson("/api/masters/{$master->id}/availability");
        $this->deleteJson("/api/masters/{$master->id}/availability")
            ->assertStatus(200);

        $resp = $this->getJson("/api/masters?lat={$master->latitude}&lng={$master->longitude}&available=1")
            ->assertStatus(200)
            ->json();

        $ids = collect($resp['data'] ?? [])->pluck('id')->all();
        $this->assertNotContains($master->id, $ids, 'Master should not appear as available');
    }

    /**
     * Master sets weekly schedule rule; listing day returns intervals
     */
    public function test_master_sets_schedule_and_list_day_returns_intervals(): void
    {
        $master = Master::factory()->create();
        $user = $master->user; // created by factory
        $token = JWTAuth::fromUser($user);

        // pick next Monday
        $date = Carbon::now()->startOfWeek()->addWeek()->toDateString();
        $dayOfWeek = Carbon::parse($date)->dayOfWeek; // 0..6

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/booking/masters/{$master->id}/slots/rules", [
                'day_of_week' => $dayOfWeek,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'active' => true,
            ])->assertStatus(200);

        $resp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/masters/{$master->id}/slots/day?date={$date}")
            ->assertStatus(200)
            ->json();

        $this->assertEquals($date, $resp['date']);
        $this->assertNotEmpty($resp['intervals']);

        // Coarse check that first interval aligns roughly with 09:00-18:00 of that date
        $first = $resp['intervals'][0];
        $this->assertEquals(Carbon::parse($date.' 09:00')->timestamp, $first['start']);
        $this->assertEquals(Carbon::parse($date.' 18:00')->timestamp, $first['end']);
    }

    /**
     * Client books a master, master sees the booking
     */
    public function test_client_books_and_master_sees_booking(): void
    {
        // Prepare master with premium enabled
        $master = Master::factory()->create([
            'is_premium' => true,
            'premium_until' => Carbon::now()->addMonth(),
        ]);

        $masterUser = $master->user;
        // Active subscription
        Subscription::updateOrCreate([
            'user_id' => $masterUser->id,
            'platform' => 'apple',
            'external_id' => Str::random(16),
        ], [
            'status' => 'active',
            'expires_at' => Carbon::now()->addMonth(),
        ]);

        // Add schedule rule for tomorrow 10:00-12:00 and sync
        $date = Carbon::now()->addDay()->toDateString();
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $masterToken = JWTAuth::fromUser($masterUser);

        $this->withHeader('Authorization', 'Bearer '.$masterToken)
            ->postJson("/api/masters/{$master->id}/slots/rules", [
                'day_of_week' => $dayOfWeek,
                'start_time' => '10:00',
                'end_time' => '12:00',
                'active' => true,
            ])->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$masterToken)
            ->postJson("/api/masters/{$master->id}/slots/sync-day?date={$date}")
            ->assertStatus(200);

        // Prepare client
        $clientUser = User::factory()->create();
        $client = Client::updateOrCreate(['user_id' => $clientUser->id], [
            'name' => $clientUser->name,
            'phone' => $clientUser->phone ?? '+380500000000',
            'verified_at' => now(),
            'user_id' => $clientUser->id,
        ]);
        $clientToken = JWTAuth::fromUser($clientUser);

        // Book 10:00-10:30
        $start = Carbon::parse($date.' 10:00:00')->toIso8601String();
        $end = Carbon::parse($date.' 10:30:00')->toIso8601String();

        $booking = $this->withHeader('Authorization', 'Bearer '.$clientToken)
            ->postJson("/api/booking/masters/{$master->id}", [
                'start_time' => $start,
                'end_time' => $end,
                'note' => 'Test booking',
            ])->assertStatus(200)
            ->json();

        $this->assertNotEmpty($booking['id'] ?? null);

        // Master sees booking (requires active.subscription + plan.feature)
        $list = $this->withHeader('Authorization', 'Bearer '.$masterToken)
            ->getJson("/api/booking/master?date={$date}")
            ->assertStatus(200)
            ->json();

        $ids = collect($list['data']['data'] ?? $list['data'] ?? [])->pluck('id')->all();
        $this->assertContains($booking['id'], $ids, 'Master should see the booking');
    }
}
