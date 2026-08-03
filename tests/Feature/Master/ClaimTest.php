<?php

declare(strict_types=1);

namespace Tests\Feature\Master;

use App\Models\Master;
use Daaner\TurboSMS\Facades\TurboSMS;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ClaimTest extends TestCase
{
    use RefreshDatabase;

    private function createUnclaimedMaster(string $token, ?string $phone = '+380501112233'): Master
    {
        return Master::forceCreate([
            'app' => 'carbeat',
            'name' => 'Test Garage',
            'contact_phone' => $phone,
            'service_id' => 1,
            'longitude' => 30,
            'latitude' => 50,
            'description' => 'desc',
            'photo' => 'defaults/avatar.png',
            'is_claimed' => false,
            'claim_token' => $token,
            'user_id' => null,
        ]);
    }

    public function test_show_renders_claim_page_for_valid_token(): void
    {
        $this->createUnclaimedMaster('valid-token');

        $response = $this->withHeader('X-App', 'carbeat')->get('/claim/valid-token');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Master/Auth/Claim')
            ->where('notFound', false)
            ->where('master.name', 'Test Garage')
        );
    }

    public function test_show_returns_404_for_invalid_token(): void
    {
        $response = $this->withHeader('X-App', 'carbeat')->get('/claim/does-not-exist');

        $response->assertStatus(404);
        $response->assertInertia(fn ($page) => $page
            ->component('Master/Auth/Claim')
            ->where('notFound', true)
        );
    }

    public function test_send_code_happy_path_stores_code_and_sends_sms(): void
    {
        $this->createUnclaimedMaster('tok-send-ok');

        Redis::shouldReceive('setex')->once();
        TurboSMS::shouldReceive('sendMessages')->once()->andReturn(['success' => true]);

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/claim/tok-send-ok/send-code');

        $response->assertOk()->assertJson(['status' => 'sent']);
    }

    public function test_send_code_on_already_claimed_master_returns_409(): void
    {
        $master = $this->createUnclaimedMaster('tok-claimed');
        $master->update(['is_claimed' => true]);

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/claim/tok-claimed/send-code');

        $response->assertStatus(409)->assertJson(['error' => 'already_claimed']);
    }

    public function test_send_code_without_phone_returns_422(): void
    {
        $this->createUnclaimedMaster('tok-no-phone', null);

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/claim/tok-no-phone/send-code');

        $response->assertStatus(422)->assertJson(['error' => 'no_phone']);
    }

    public function test_send_code_for_invalid_token_returns_404(): void
    {
        $response = $this->withHeader('X-App', 'carbeat')->postJson('/claim/does-not-exist/send-code');

        $response->assertStatus(404);
    }

    public function test_verify_happy_path_logs_in_and_claims_master(): void
    {
        $master = $this->createUnclaimedMaster('tok-verify-ok');

        Redis::shouldReceive('get')->once()->andReturn(json_encode([
            'code' => '123456',
            'phone' => '+380501112233',
        ]));
        Redis::shouldReceive('del')->once();

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/claim/tok-verify-ok/verify', [
            'code' => '123456',
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);
        $this->assertTrue(Auth::guard('web')->check());

        $master->refresh();
        $this->assertTrue($master->is_claimed);
        $this->assertNull($master->claim_token);
        $this->assertNotNull($master->phone_verified_at);
        $this->assertNotNull($master->user_id);
        $this->assertSame($master->user_id, Auth::guard('web')->id());

        $this->assertDatabaseHas('master_bays', ['master_id' => $master->id]);
    }

    public function test_verify_wrong_code_returns_422(): void
    {
        $this->createUnclaimedMaster('tok-verify-wrong');

        Redis::shouldReceive('get')->once()->andReturn(json_encode([
            'code' => '999999',
            'phone' => '+380501112233',
        ]));

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/claim/tok-verify-wrong/verify', [
            'code' => '123456',
        ]);

        $response->assertStatus(422)->assertJson(['error' => 'invalid_code']);
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_verify_expired_code_returns_410(): void
    {
        $this->createUnclaimedMaster('tok-verify-expired');

        Redis::shouldReceive('get')->once()->andReturn(null);

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/claim/tok-verify-expired/verify', [
            'code' => '123456',
        ]);

        $response->assertStatus(410)->assertJson(['error' => 'code_expired']);
    }

    public function test_verify_for_invalid_token_returns_404(): void
    {
        $response = $this->withHeader('X-App', 'carbeat')->postJson('/claim/does-not-exist/verify', [
            'code' => '123456',
        ]);

        $response->assertStatus(404);
    }
}
