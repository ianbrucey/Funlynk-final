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
            ->set('content', 'Test post description')
            ->set('expiresIn', '48')
            ->call('createPost')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('posts', [
            'title' => 'Test post description',
            'description' => 'Test post description',
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

    public function test_groups_with_same_name_get_unique_slugs()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $service = app(GroupService::class);

        $first = $service->createGroup($user, ['name' => 'Run Club', 'privacy' => 'public']);
        $second = $service->createGroup($user, ['name' => 'Run Club', 'privacy' => 'public']);

        $this->assertSame('run-club', $first->slug);
        $this->assertSame('run-club-2', $second->slug);
    }

    public function test_slug_is_not_reused_from_soft_deleted_group()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $service = app(GroupService::class);

        $first = $service->createGroup($user, ['name' => 'Test Group', 'privacy' => 'public']);
        $first->delete();
        $second = $service->createGroup($user, ['name' => 'Test Group', 'privacy' => 'public']);

        $this->assertNotSame($first->slug, $second->slug);
    }

    public function test_renaming_group_keeps_slug()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $service = app(GroupService::class);
        $group = $service->createGroup($user, ['name' => 'Run Club', 'privacy' => 'public']);

        $updated = $service->updateGroup($group, ['name' => 'Sprint Club']);

        $this->assertSame('run-club', $updated->slug);
    }

    public function test_cannot_demote_last_admin()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = app(GroupService::class)->createGroup($user, ['name' => 'Solo', 'privacy' => 'public']);

        $this->expectExceptionMessage('Cannot demote the last admin');
        app(GroupService::class)->updateMemberRole($group, $user, 'member');
    }

    public function test_editing_schedule_details_updates_future_occurrences()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = app(GroupService::class)->createGroup($user, ['name' => 'Runners', 'privacy' => 'public']);
        $service = app(\App\Services\RecurringScheduleService::class);

        $schedule = $service->createSchedule($group, $user, [
            'title' => 'Morning Run',
            'location_name' => 'Central Park',
            'location_coordinates' => new \MatanYadaev\EloquentSpatial\Objects\Point(40.78, -73.97),
            'frequency' => 'daily',
            'start_time' => '06:00:00',
            'duration_minutes' => 60,
        ]);
        $this->assertGreaterThan(0, $schedule->activities()->count());

        $service->updateSchedule($schedule, ['title' => 'Sunrise Run', 'start_time' => '07:30:00']);

        $future = $schedule->activities()->where('start_time', '>', now())->get();
        $this->assertGreaterThan(0, $future->count());
        foreach ($future as $activity) {
            $this->assertSame('Sunrise Run', $activity->title);
            $this->assertSame('07:30:00', $activity->start_time->format('H:i:s'));
        }
    }

    public function test_schedule_interval_skips_days()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = app(GroupService::class)->createGroup($user, ['name' => 'Walkers', 'privacy' => 'public']);

        $schedule = app(\App\Services\RecurringScheduleService::class)->createSchedule($group, $user, [
            'title' => 'Walk',
            'location_name' => 'Park',
            'location_coordinates' => new \MatanYadaev\EloquentSpatial\Objects\Point(40.78, -73.97),
            'frequency' => 'daily',
            'interval' => 3,
            'start_time' => '06:00:00',
            'generate_weeks_ahead' => 2,
        ]);

        $dates = $schedule->activities()->orderBy('recurrence_date')->pluck('recurrence_date')
            ->map(fn ($d) => \Illuminate\Support\Carbon::parse($d))->values();

        $this->assertGreaterThan(1, $dates->count());
        for ($i = 1; $i < $dates->count(); $i++) {
            $this->assertSame(3, (int) $dates[$i - 1]->diffInDays($dates[$i]));
        }
    }

    public function test_private_group_timeline_is_forbidden_for_non_members()
    {
        $owner = User::factory()->create(['onboarding_completed_at' => now()]);
        $outsider = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = app(GroupService::class)->createGroup($owner, ['name' => 'Secret', 'privacy' => 'private']);

        $this->actingAs($outsider)->get(route('groups.timeline', $group))->assertForbidden();
        $this->actingAs($owner)->get(route('groups.timeline', $group))->assertOk();
    }
}
