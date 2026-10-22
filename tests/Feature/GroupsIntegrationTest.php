<?php

namespace Tests\Feature;

use App\Livewire\Activities\RsvpButton;
use App\Models\Activity;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GroupsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_index_is_accessible()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $this->actingAs($user)
            ->get(route('groups.index'))
            ->assertStatus(200)
            ->assertSeeLivewire('groups.groups-index');
    }

    public function test_create_group_requires_authentication()
    {
        $this->get(route('groups.create'))
            ->assertRedirect(route('login'));
    }

    public function test_create_group_page_is_accessible_for_authenticated_users()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $this->actingAs($user)
            ->get(route('groups.create'))
            ->assertStatus(200)
            ->assertSeeLivewire('groups.create-group');
    }

    public function test_group_show_page_is_accessible()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create([
            'privacy' => 'public',
        ]);

        $this->actingAs($user)
            ->get(route('groups.show', $group))
            ->assertStatus(200)
            ->assertSeeLivewire('groups.group-show');
    }

    public function test_group_members_page_is_accessible()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create();

        $this->actingAs($user)
            ->get(route('groups.members', $group))
            ->assertStatus(200)
            ->assertSeeLivewire('groups.group-members');
    }

    public function test_group_timeline_page_is_accessible()
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['privacy' => 'public']);

        $this->actingAs($user)
            ->get(route('groups.timeline', $group))
            ->assertStatus(200)
            ->assertSeeLivewire('groups.group-timeline');
    }

    public function test_non_member_can_rsvp_to_public_group_event(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['privacy' => 'public']);
        $event = Activity::factory()->create(['group_id' => $group->id, 'status' => 'published', 'start_time' => now()->addDay(), 'payment_type' => 'free']);

        Livewire::actingAs($user)->test(RsvpButton::class, ['activity' => $event])->call('toggleRsvp');

        $this->assertDatabaseHas('rsvps', ['activity_id' => $event->id, 'user_id' => $user->id]);
    }

    public function test_non_member_cannot_rsvp_or_checkout_for_private_group_event(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $group = Group::factory()->create(['privacy' => 'private']);
        $event = Activity::factory()->create(['group_id' => $group->id, 'status' => 'published', 'start_time' => now()->addDay(), 'payment_type' => 'online']);

        Livewire::actingAs($user)->test(RsvpButton::class, ['activity' => $event])->call('toggleRsvp')->assertForbidden();
        $this->assertDatabaseMissing('rsvps', ['activity_id' => $event->id, 'user_id' => $user->id]);

        $this->actingAs($user)->get(route('events.checkout', $event))->assertForbidden();
    }
}
