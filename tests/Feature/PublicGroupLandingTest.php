<?php

namespace Tests\Feature;

use App\Livewire\Groups\PublicGroupLanding;
use App\Models\Group;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicGroupLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_public_group_landing_as_guest()
    {
        $group = Group::factory()->create(['privacy' => 'public']);

        $this->get(route('groups.public', $group))
            ->assertStatus(200)
            ->assertSee($group->name);
    }

    public function test_can_render_public_group_landing_as_member()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['privacy' => 'public']);

        app(GroupService::class)->addMember($group, $user);

        $this->actingAs($user)
            ->get(route('groups.public', $group))
            ->assertStatus(200)
            ->assertSee('Welcome Back!');
    }

    public function test_can_render_public_group_landing_as_non_member_authenticated()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['privacy' => 'public']);

        $this->actingAs($user)
            ->get(route('groups.public', $group))
            ->assertStatus(200)
            ->assertSee('Join Group');
    }
}
