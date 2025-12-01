<?php

namespace Tests\Feature;

use App\Livewire\Groups\CreateGroup;
use App\Livewire\Groups\GroupMembers;
use App\Livewire\Groups\GroupsIndex;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GroupsLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_group()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);

        Livewire::actingAs($user)
            ->test(CreateGroup::class)
            ->set('name', 'Test Group')
            ->set('description', 'This is a test group description.')
            ->set('privacy', 'public')
            ->call('createGroup')
            ->assertRedirect();

        $this->assertDatabaseHas('groups', [
            'name' => 'Test Group',
            'created_by' => $user->id,
        ]);
    }

    public function test_can_search_groups()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        Group::factory()->create(['name' => 'Hiking Club']);
        Group::factory()->create(['name' => 'Book Club']);

        Livewire::actingAs($user)
            ->test(GroupsIndex::class)
            ->set('search', 'Hiking')
            ->assertSee('Hiking Club')
            ->assertDontSee('Book Club');
    }

    public function test_can_join_and_leave_group()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['privacy' => 'public']);

        // Join
        Livewire::actingAs($user)
            ->test(GroupsIndex::class)
            ->call('joinGroup', $group->id)
            ->assertSee('You have joined the group!');

        $this->assertTrue($group->members->contains($user));

        // Leave
        Livewire::actingAs($user)
            ->test(GroupsIndex::class)
            ->call('leaveGroup', $group->id)
            ->assertSee('You have left the group.');

        $this->assertFalse($group->fresh()->members->contains($user));
    }

    public function test_admin_can_remove_member()
    {
        $admin = User::factory()->create(['onboarding_completed_at' => now()]);
        $member = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['created_by' => $admin->id]);
        
        // Add member using service or factory if possible, but here we manually attach
        // Assuming GroupService logic is tested elsewhere or we use the service
        app(\App\Services\GroupService::class)->addMember($group, $member);

        Livewire::actingAs($admin)
            ->test(GroupMembers::class, ['group' => $group])
            ->call('removeMember', $group->members()->where('user_id', $member->id)->first()->id)
            ->assertSee('Member removed successfully.');

        $this->assertFalse($group->fresh()->members->contains($member));
    }
}
