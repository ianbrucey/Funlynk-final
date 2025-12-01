<?php

namespace Tests\Feature\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['onboarding_completed_at' => now()]);
    }
    use RefreshDatabase;

    public function test_groups_index_is_accessible()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->user)
            ->get(route('groups.index'))
            ->assertStatus(200);
    }

    public function test_create_group_requires_authentication()
    {
        $this->get(route('groups.create'))
            ->assertRedirect(route('login'));
    }

    public function test_create_group_page_is_accessible_for_authenticated_users()
    {
        $this->actingAs($this->user)
            ->get(route('groups.create'))
            ->assertStatus(200);
    }

    public function test_group_show_page_is_accessible()
    {
        $group = Group::factory()->create([
            'privacy' => 'public',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('groups.show', $group))
            ->assertStatus(200);
    }

    public function test_group_members_page_is_accessible()
    {
        $this->withoutExceptionHandling();
        $group = Group::factory()->create(['created_by' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('groups.members', $group))
            ->assertStatus(200);
    }

    public function test_group_timeline_page_is_accessible()
    {
        $group = Group::factory()->create(['created_by' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('groups.timeline', $group))
            ->assertStatus(200);
    }
}
