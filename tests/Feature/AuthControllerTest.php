<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Services\SmsService;
use App\Http\Services\TokenService;
use App\Models\Master;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_otp_sends_sms_and_returns_200(): void
    {
        // Mock SmsService generateAndSendCode
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('generateAndSendCode')->once()->with('+380501234567', 4, null);
        });

        $response = $this->postJson('/api/auth/request-otp', [
            'phone' => '+380501234567',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'needs_registration',
            ]);
    }

    public function test_verify_otp_returns_400_on_wrong_code(): void
    {
        // SmsService verifyCode returns false
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')->once()->andReturn(false);
        });

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => '+380501234567',
            'sms_code' => '123456',
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Wrong code']);
    }

    public function test_verify_otp_returns_tokens_on_success(): void
    {
        // Mock SmsService verifyCode true
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')->once()->andReturn(true);
        });

        // Mock TokenService to skip JWT generation
        $this->mock(TokenService::class, function ($mock) {
            $mock->shouldReceive('createAccessToken')->once()->andReturn('access');
            $mock->shouldReceive('createRefreshToken')->once()->andReturnUsing(function ($user) {
                $model = new \App\Models\RefreshToken([
                    'token' => 'hashed',
                    'expires_at' => now()->addDays(30),
                ]);
                $model->plain_token = 'refresh';

                return $model;
            });
            $mock->shouldReceive('revoke')->andReturnNull();
        });

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => '+380501234567',
            'sms_code' => '111111',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'access_token',
                'refresh_token',
                'expires_in',
            ]);
    }

    public function test_request_otp_does_not_require_registration_when_floxcity_master_exists_without_user(): void
    {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('generateAndSendCode')->once()->with('+380501234567', 4, null);
        });

        Master::forceCreate([
            'app' => 'floxcity',
            'name' => 'Existing Flox Master',
            'contact_phone' => '+380501234567',
            'service_id' => 1,
            'longitude' => 30,
            'latitude' => 50,
            'description' => 'desc',
            'photo' => 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==',
        ]);

        $response = $this
            ->withHeader('X-App', 'floxcity')
            ->postJson('/api/auth/request-otp', [
                'phone' => '+380501234567',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'needs_registration' => false,
            ]);
    }

    public function test_verify_otp_links_existing_floxcity_master_without_user(): void
    {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')->once()->andReturn(true);
        });

        $this->mock(TokenService::class, function ($mock) {
            $mock->shouldReceive('createAccessToken')->once()->andReturn('access');
            $mock->shouldReceive('createRefreshToken')->once()->andReturnUsing(function (User $user) {
                $model = new \App\Models\RefreshToken([
                    'user_id' => $user->id,
                    'token' => 'hashed',
                    'expires_at' => now()->addDays(30),
                ]);
                $model->plain_token = 'refresh';

                return $model;
            });
        });

        $master = Master::forceCreate([
            'app' => 'floxcity',
            'name' => 'Existing Flox Master',
            'contact_phone' => '+380501234567',
            'service_id' => 1,
            'longitude' => 30,
            'latitude' => 50,
            'description' => 'desc',
            'photo' => 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==',
        ]);

        $response = $this
            ->withHeader('X-App', 'floxcity')
            ->postJson('/api/auth/verify-otp', [
                'phone' => '+380501234567',
                'sms_code' => '1111',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'refresh_token' => 'refresh',
            ]);

        $user = User::withoutGlobalScope('app')
            ->where('app', 'floxcity')
            ->where('phone', '+380501234567')
            ->first();

        $this->assertNotNull($user);
        $this->assertSame($user->id, $master->refresh()->user_id);
    }
}
