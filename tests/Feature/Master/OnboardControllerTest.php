<?php

declare(strict_types=1);

namespace Tests\Feature\Master;

use App\Http\Services\SmsService;
use App\Models\Master;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class OnboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createMaster(string $phone, bool $claimed): Master
    {
        return Master::forceCreate([
            'app' => 'carbeat',
            'name' => 'Existing Garage',
            'contact_phone' => $phone,
            'service_id' => 1,
            'longitude' => 30,
            'latitude' => 50,
            'description' => 'desc',
            'photo' => 'defaults/avatar.png',
            'is_claimed' => $claimed,
            'claim_token' => $claimed ? null : 'onboard-token',
            'user_id' => null,
        ]);
    }

    public function test_send_code_reports_register_mode_for_unknown_phone(): void
    {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('generateAndSendCode')->once();
        });

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-onboard/send-code', [
            'phone' => '+380501112233',
        ]);

        $response->assertOk()->assertJson(['status' => 'sent', 'mode' => 'register', 'preview' => null]);
    }

    public function test_send_code_reports_claim_mode_for_unclaimed_master(): void
    {
        $this->createMaster('+380501112233', claimed: false);

        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('generateAndSendCode')->once();
        });

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-onboard/send-code', [
            'phone' => '+380501112233',
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'sent', 'mode' => 'claim'])
            ->assertJsonPath('preview.name', 'Existing Garage');
    }

    public function test_send_code_reports_login_mode_for_claimed_master(): void
    {
        $this->createMaster('+380501112233', claimed: true);

        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('generateAndSendCode')->once();
        });

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-onboard/send-code', [
            'phone' => '+380501112233',
        ]);

        $response->assertOk()->assertJson(['status' => 'sent', 'mode' => 'login']);
    }

    public function test_verify_returns_400_on_wrong_code(): void
    {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')->once()->andReturn(false);
        });

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-onboard/verify', [
            'phone' => '+380501112233',
            'code' => '1234',
        ]);

        $response->assertStatus(400)->assertJson(['error' => 'invalid_code']);
    }

    public function test_verify_registers_a_new_master(): void
    {
        Service::create(['name' => 'Diagnostics']);

        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')->once()->andReturn(true);
        });

        Redis::shouldReceive('exists')->andReturn(false);
        Redis::shouldReceive('publish')->once();

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-onboard/verify', [
            'phone' => '+380501112233',
            'code' => '1234',
            'name' => 'New Garage',
            'service_id' => 1,
            'latitude' => 50.1,
            'longitude' => 30.1,
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);
        $this->assertTrue(Auth::guard('web')->check());

        $master = Master::where('contact_phone', '+380501112233')->first();
        $this->assertNotNull($master);
        $this->assertSame('New Garage', $master->name);
        $this->assertNotNull($master->user_id);
    }

    public function test_verify_rejects_registration_without_required_fields(): void
    {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')->once()->andReturn(true);
        });

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-onboard/verify', [
            'phone' => '+380501112233',
            'code' => '1234',
        ]);

        $response->assertStatus(422)->assertJson(['error' => 'registration_incomplete']);
        $this->assertSame(0, Master::count());
    }

    public function test_verify_claims_an_unclaimed_master(): void
    {
        $master = $this->createMaster('+380501112233', claimed: false);

        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')->once()->andReturn(true);
        });

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-onboard/verify', [
            'phone' => '+380501112233',
            'code' => '1234',
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);
        $this->assertTrue(Auth::guard('web')->check());

        $master->refresh();
        $this->assertTrue($master->is_claimed);
        $this->assertNotNull($master->user_id);
    }

    public function test_verify_logs_in_an_already_claimed_master(): void
    {
        $this->createMaster('+380501112233', claimed: true);

        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')->once()->andReturn(true);
        });

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-onboard/verify', [
            'phone' => '+380501112233',
            'code' => '1234',
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);
        $this->assertTrue(Auth::guard('web')->check());
    }
}
