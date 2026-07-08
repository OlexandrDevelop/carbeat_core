<?php

declare(strict_types=1);

namespace Tests\Feature\Master;

use App\Http\Services\SmsService;
use App\Models\Master;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createMaster(string $phone, string $app = 'carbeat', ?int $userId = null): Master
    {
        return Master::forceCreate([
            'app' => $app,
            'name' => 'Test Garage',
            'contact_phone' => $phone,
            'service_id' => 1,
            'longitude' => 30,
            'latitude' => 50,
            'description' => 'desc',
            'photo' => 'defaults/avatar.png',
            'user_id' => $userId,
        ]);
    }

    public function test_request_otp_sends_sms_when_phone_belongs_to_a_master(): void
    {
        $this->createMaster('+380501112233');

        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('generateAndSendCode')->once();
        });

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-auth/request-otp', [
            'phone' => '+380501112233',
        ]);

        $response->assertOk()->assertJson(['message' => 'OTP sent']);
    }

    public function test_request_otp_does_not_send_sms_for_a_non_master_phone(): void
    {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldNotReceive('generateAndSendCode');
        });

        // Always the same generic response, so this endpoint can't be used to
        // enumerate which phone numbers belong to masters.
        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-auth/request-otp', [
            'phone' => '+380509998877',
        ]);

        $response->assertOk()->assertJson(['message' => 'OTP sent']);
    }

    public function test_verify_otp_returns_400_on_wrong_code(): void
    {
        $this->createMaster('+380501112233');

        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')->once()->andReturn(false);
        });

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-auth/verify-otp', [
            'phone' => '+380501112233',
            'sms_code' => '0000',
        ]);

        $response->assertStatus(400)->assertJson(['error' => 'Wrong code']);
    }

    public function test_verify_otp_logs_in_a_registered_master(): void
    {
        $this->createMaster('+380501112233');

        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')->once()->andReturn(true);
        });

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-auth/verify-otp', [
            'phone' => '+380501112233',
            'sms_code' => '1234',
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);
        $this->assertTrue(Auth::guard('web')->check());

        $user = User::where('phone', '+380501112233')->first();
        $this->assertNotNull($user);
        $this->assertSame($user->id, Auth::guard('web')->id());
    }

    public function test_verify_otp_rejects_a_phone_that_is_not_a_registered_master(): void
    {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')->once()->andReturn(true);
        });

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/master-auth/verify-otp', [
            'phone' => '+380509998877',
            'sms_code' => '1234',
        ]);

        $response->assertStatus(403);
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_verify_otp_does_not_auto_create_a_master_record(): void
    {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')->once()->andReturn(true);
        });

        $this->withHeader('X-App', 'carbeat')->postJson('/master-auth/verify-otp', [
            'phone' => '+380509998877',
            'sms_code' => '1234',
        ]);

        $this->assertSame(0, Master::count());
    }
}
