<?php

namespace Tests\Feature\Admin;

use App\Models\Master;
use App\Models\User;
use Daaner\TurboSMS\Facades\TurboSMS;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_invites_to_selected_masters(): void
    {
        TurboSMS::shouldReceive('sendMessages')
            ->times(2)
            ->andReturn(['success' => true]);

        $admin = User::factory()->create();

        $masters = Master::factory()->count(2)->create([
            'contact_phone' => '+380631112233',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin-api/masters/invite', [
            'master_ids' => $masters->pluck('id')->all(),
            'message' => 'Carbeat чекає на вас: :link',
        ]);

        $response->assertOk()
            ->assertJson([
                'sent' => 2,
                'requested' => 2,
            ]);
    }

    public function test_invite_skips_masters_without_phone(): void
    {
        TurboSMS::shouldReceive('sendMessages')->once()->andReturn(['success' => true]);

        $admin = User::factory()->create();

        $withPhone = Master::factory()->create(['contact_phone' => '+380501234567']);
        $noPhone = Master::factory()->create(['contact_phone' => null]);

        $response = $this->actingAs($admin)->postJson('/admin-api/masters/invite', [
            'master_ids' => [$withPhone->id, $noPhone->id],
        ]);

        $response->assertOk()
            ->assertJson([
                'sent' => 1,
                'requested' => 2,
            ]);

        $this->assertCount(1, $response->json('skipped'));
    }
}

