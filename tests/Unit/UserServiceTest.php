<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Services\UserService;
use App\Models\Master;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_or_update_from_master_creates_user_and_associates(): void
    {
        /** @var Master $master */
        $master = Master::factory()->make();
        $master->user_id = null;
        $master->save();

        $user = $this->service->createOrUpdateFromMaster($master);

        $this->assertInstanceOf(User::class, $user);
        $master->refresh();
        $this->assertEquals($user->id, $master->user_id);
        $this->assertEquals($master->name, $user->name);
        $this->assertEquals($master->contact_phone, $user->phone);
    }

    public function test_create_or_update_for_client_updates_existing_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['phone' => '+380501234567', 'name' => 'Old Name']);

        $data = [
            'phone' => '+380501234567',
            'name' => 'New Name',
        ];

        $result = $this->service->createOrUpdateForClient($data);

        $this->assertEquals($user->id, $result->id);
        $this->assertEquals('New Name', $result->name);
    }

    public function test_create_or_update_for_client_creates_new_user_if_not_exists(): void
    {
        $data = [
            'phone' => '+380501000000',
            'name' => 'Client Name',
        ];

        $result = $this->service->createOrUpdateForClient($data);

        $this->assertDatabaseHas('users', [
            'phone' => '+380501000000',
            'name' => 'Client Name',
        ]);
        $this->assertEquals($data['phone'], $result->phone);
    }

    public function test_find_user_by_phone_returns_user(): void
    {
        $user = User::factory()->create(['phone' => '+380507777777']);

        $found = $this->service->findUserByPhone('+380507777777');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_attach_user_to_master_by_phone_updates_master(): void
    {
        $user = User::factory()->create(['phone' => '+380508888888']);
        $master = Master::factory()->make([
            'contact_phone' => '+380508888888',
            'user_id' => null,
        ]);
        $master->save();

        $this->service->attachUserToMasterByPhone('+380508888888', $user);

        $master->refresh();
        $this->assertEquals($user->id, $master->user_id);
    }

    public function test_attach_user_to_master_by_phone_does_not_crash_on_duplicate_contact_phone(): void
    {
        // Reproduces the masters_user_id_unique crash: two master rows share
        // a contact_phone (an unclaimed import placeholder with the legacy
        // user_id=1 sentinel, plus a genuinely unclaimed row). Both used to
        // match the lookup and get bulk-updated to the same user_id at once.
        // Bump past id 1 so $owner->id can't collide with the sentinel below.
        User::factory()->create();
        $owner = User::factory()->create(['phone' => '+380509999999']);

        $placeholderImport = Master::factory()->make([
            'contact_phone' => '+380509999999',
            'user_id' => 1,
        ]);
        $placeholderImport->save();

        $unclaimed = Master::factory()->make([
            'contact_phone' => '+380509999999',
            'user_id' => null,
        ]);
        $unclaimed->save();

        $this->service->attachUserToMasterByPhone('+380509999999', $owner);

        $placeholderImport->refresh();
        $unclaimed->refresh();

        $this->assertEquals(1, $placeholderImport->user_id);
        $this->assertEquals($owner->id, $unclaimed->user_id);
    }

    public function test_create_token_for_user_returns_token(): void
    {
        $user = User::factory()->create();

        JWTAuth::shouldReceive('claims')
            ->once()
            ->with(['phone' => $user->phone])
            ->andReturnSelf();

        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->with($user)
            ->andReturn('token123');

        $token = $this->service->createTokenForUser($user);

        $this->assertEquals('token123', $token);
    }
}
