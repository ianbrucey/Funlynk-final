<?php

namespace Tests\Feature;

use App\Livewire\Groups\CreateGroup;
use App\Livewire\Groups\CreateGroupPost;
use App\Livewire\Groups\GroupMembers;
use App\Livewire\Groups\GroupSettings;
use App\Livewire\Groups\GroupShow;
use App\Livewire\Groups\GroupsIndex;
use App\Livewire\Groups\JoinRequestsList;
use App\Models\Group;
use App\Models\User;
use App\Services\GroupService;
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
            ->set('location_name', 'New York, NY, USA')
            ->set('latitude', 40.7128)
            ->set('longitude', -74.0060)
            ->call('createGroup')
            ->assertRedirect();

        $this->assertDatabaseHas('groups', [
            'name' => 'Test Group',
            'created_by' => $user->id,
            'location_name' => 'New York, NY, USA',
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

        // Join - flash message is "Successfully joined the group!"
        Livewire::actingAs($user)
            ->test(GroupsIndex::class)
            ->call('joinGroup', $group->id)
            ->assertSee('Successfully joined the group!');

        $this->assertTrue($group->members->contains($user));

        // Leave - flash message is "Successfully left the group!"
        Livewire::actingAs($user)
            ->test(GroupsIndex::class)
            ->call('leaveGroup', $group->id)
            ->assertSee('Successfully left the group!');

        $this->assertFalse($group->fresh()->members->contains($user));
    }

    public function test_admin_can_remove_member()
    {
        $admin = User::factory()->create(['onboarding_completed_at' => now()]);
        $member = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['created_by' => $admin->id]);

        // Add admin first (creator needs to be admin to remove members)
        app(GroupService::class)->addMember($group, $admin, 'admin');
        // Add member using service
        app(GroupService::class)->addMember($group, $member);

        // Get the membership ID (not user ID) - use memberships() not members()
        $membership = $group->memberships()->where('user_id', $member->id)->first();

        Livewire::actingAs($admin)
            ->test(GroupMembers::class, ['group' => $group])
            ->call('removeMember', $membership->id)
            ->assertSee('Member removed successfully.');

        $this->assertFalse($group->fresh()->members->contains($member));
    }

    public function test_private_group_join_creates_request()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['privacy' => 'private']);

        Livewire::actingAs($user)
            ->test(GroupsIndex::class)
            ->call('joinGroup', $group->id)
            ->assertSee('Join request sent!');

        $this->assertDatabaseHas('group_join_requests', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_join_request()
    {
        $admin = User::factory()->create(['onboarding_completed_at' => now()]);
        $requester = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['created_by' => $admin->id, 'privacy' => 'private']);

        app(GroupService::class)->addMember($group, $admin, 'admin');
        $request = app(GroupService::class)->createJoinRequest($group, $requester);

        Livewire::actingAs($admin)
            ->test(JoinRequestsList::class, ['group' => $group])
            ->call('approveRequest', $request->id)
            ->assertSee('Join request approved');

        $this->assertTrue($group->fresh()->members->contains($requester));
        $this->assertDatabaseHas('group_join_requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_deny_join_request()
    {
        $admin = User::factory()->create(['onboarding_completed_at' => now()]);
        $requester = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['created_by' => $admin->id, 'privacy' => 'private']);

        app(GroupService::class)->addMember($group, $admin, 'admin');
        $request = app(GroupService::class)->createJoinRequest($group, $requester);

        Livewire::actingAs($admin)
            ->test(JoinRequestsList::class, ['group' => $group])
            ->call('denyRequest', $request->id)
            ->assertSee('Join request denied');

        $this->assertFalse($group->fresh()->members->contains($requester));
        $this->assertDatabaseHas('group_join_requests', [
            'id' => $request->id,
            'status' => 'denied',
        ]);
    }

    public function test_non_admin_cannot_access_join_requests()
    {
        $admin = User::factory()->create(['onboarding_completed_at' => now()]);
        $member = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['created_by' => $admin->id, 'privacy' => 'private']);

        app(GroupService::class)->addMember($group, $admin, 'admin');
        app(GroupService::class)->addMember($group, $member);

        // Non-admin accessing join requests should get 403
        $response = $this->actingAs($member)->get(route('groups.requests', $group));
        $response->assertStatus(403);
    }

    public function test_admin_can_update_group_settings()
    {
        $admin = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['created_by' => $admin->id, 'name' => 'Original Name']);

        app(GroupService::class)->addMember($group, $admin, 'admin');

        Livewire::actingAs($admin)
            ->test(GroupSettings::class, ['group' => $group])
            ->set('name', 'Updated Name')
            ->set('description', 'Updated description')
            ->call('updateGroup')
            ->assertSee('Group settings updated');

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_non_admin_cannot_access_group_settings()
    {
        $admin = User::factory()->create(['onboarding_completed_at' => now()]);
        $member = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['created_by' => $admin->id]);

        app(GroupService::class)->addMember($group, $admin, 'admin');
        app(GroupService::class)->addMember($group, $member);

        // Non-admin accessing settings should get 403
        $response = $this->actingAs($member)->get(route('groups.settings', $group));
        $response->assertStatus(403);
    }

    public function test_member_can_create_group_post()
    {
        $admin = User::factory()->create(['onboarding_completed_at' => now()]);
        $member = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['created_by' => $admin->id]);

        app(GroupService::class)->addMember($group, $admin, 'admin');
        app(GroupService::class)->addMember($group, $member);

        Livewire::actingAs($member)
            ->test(CreateGroupPost::class, ['group' => $group])
            ->call('openModal')
            ->set('title', 'Test Post Title')
            ->set('description', 'Test post description')
            ->set('expiresAt', now()->addDays(2)->format('Y-m-d H:i:s'))
            ->call('createPost')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post Title',
            'group_id' => $group->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_group_show_displays_pending_request_status()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $admin = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['created_by' => $admin->id, 'privacy' => 'private']);

        app(GroupService::class)->addMember($group, $admin, 'admin');
        app(GroupService::class)->createJoinRequest($group, $user);

        Livewire::actingAs($user)
            ->test(GroupShow::class, ['group' => $group])
            ->assertSee('Request Pending');
    }

    public function test_user_can_cancel_join_request()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $admin = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['created_by' => $admin->id, 'privacy' => 'private']);

        app(GroupService::class)->addMember($group, $admin, 'admin');
        app(GroupService::class)->createJoinRequest($group, $user);

        Livewire::actingAs($user)
            ->test(GroupShow::class, ['group' => $group])
            ->call('cancelJoinRequest')
            ->assertSee('Join request cancelled');

        $this->assertDatabaseMissing('group_join_requests', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }
}
