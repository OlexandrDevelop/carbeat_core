<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Master;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Regression coverage for the mobile claim JSON API after ClaimService::verify()
 * was split into ClaimService::verifyAndClaim() (see Tests\Feature\Master\ClaimTest
 * for the browser-facing counterpart) — confirms JWT issuance still works.
 */
class ClaimControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_issues_jwt_tokens_and_claims_master(): void
    {
        $master = Master::forceCreate([
            'app' => 'carbeat',
            'name' => 'Mobile Garage',
            'contact_phone' => '+380501112233',
            'service_id' => 1,
            'longitude' => 30,
            'latitude' => 50,
            'description' => 'desc',
            'photo' => 'defaults/avatar.png',
            'is_claimed' => false,
            'claim_token' => 'mobile-token',
            'user_id' => null,
        ]);

        Redis::shouldReceive('get')->once()->andReturn(json_encode([
            'code' => '123456',
            'phone' => '+380501112233',
        ]));
        Redis::shouldReceive('del')->once();

        $response = $this->withHeader('X-App', 'carbeat')->postJson('/api/auth/claim/verify', [
            'master_id' => $master->id,
            'phone' => '+380501112233',
            'code' => '123456',
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'verified'])
            ->assertJsonStructure(['access_token', 'refresh_token', 'expires_in', 'user']);

        $master->refresh();
        $this->assertTrue($master->is_claimed);
    }
}
