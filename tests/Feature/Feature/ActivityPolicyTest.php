<?php

use App\Models\Activity;
use App\Models\Group;
use App\Models\User;
use App\Policies\ActivityPolicy;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disable event listeners to avoid transaction issues in tests
    Event::fake();

    $this->activityPolicy = new ActivityPolicy;
    $this->groupService = new GroupService;
    $this->user = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($this->user);
});

describe('ActivityPolicy', function () {
    it('allows anyone to view any activity', function () {
        expect($this->activityPolicy->viewAny($this->user))->toBeTrue();
        expect($this->activityPolicy->viewAny(null))->toBeTrue();
    });

    it('allows anyone to view a public activity', function () {
        $activity = Activity::factory()->create(['is_public' => true]);
        expect($this->activityPolicy->view($this->user, $activity))->toBeTrue();
        expect($this->activityPolicy->view(null, $activity))->toBeTrue();
    });

    it('allows host to view a private activity', function () {
        $activity = Activity::factory()->create(['is_public' => false, 'host_id' => $this->user->id]);
        expect($this->activityPolicy->view($this->user, $activity))->toBeTrue();
    });

    it('denies non-host from viewing a private activity', function () {
        $activity = Activity::factory()->create(['is_public' => false]);
        $otherUser = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
        expect($this->activityPolicy->view($otherUser, $activity))->toBeFalse();
    });

    it('allows group members to view a private group activity', function () {
        $group = Group::factory()->create(['privacy' => 'private']);
        $this->groupService->addMember($group, $this->user);
        $activity = Activity::factory()->create(['group_id' => $group->id, 'is_public' => false]);
        expect($this->activityPolicy->view($this->user, $activity))->toBeTrue();
    });

    it('denies non-members from viewing a private group activity', function () {
        $group = Group::factory()->create(['privacy' => 'private']);
        $activity = Activity::factory()->create(['group_id' => $group->id, 'is_public' => false]);
        $nonMember = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
        expect($this->activityPolicy->view($nonMember, $activity))->toBeFalse();
    });

    it('allows authenticated users to create an activity', function () {
        expect($this->activityPolicy->create($this->user))->toBeTrue();
    });

    it('denies guests from creating an activity', function () {
        expect($this->activityPolicy->create(null))->toBeFalse();
    });

    it('allows group members to create an event in a group', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user);
        expect($this->activityPolicy->createInGroup($this->user, $group))->toBeTrue();
    });

    it('denies non-members from creating an event in a group', function () {
        $group = Group::factory()->create();
        $nonMember = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
        expect($this->activityPolicy->createInGroup($nonMember, $group))->toBeFalse();
    });

    it('allows activity host to update their activity', function () {
        $activity = Activity::factory()->create(['host_id' => $this->user->id]);
        expect($this->activityPolicy->update($this->user, $activity))->toBeTrue();
    });

    it('denies other users from updating an activity', function () {
        $activity = Activity::factory()->create();
        $otherUser = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
        expect($this->activityPolicy->update($otherUser, $activity))->toBeFalse();
    });

    it('allows group event creator to update their group event', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'admin');
        $activity = Activity::factory()->create(['host_id' => $this->user->id, 'group_id' => $group->id]);
        expect($this->activityPolicy->update($this->user, $activity))->toBeTrue();
    });

    it('allows group admin to update a group event', function () {
        $group = Group::factory()->create();
        $admin = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
        $this->groupService->addMember($group, $admin, 'admin');
        $eventCreator = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
        $activity = Activity::factory()->create(['host_id' => $eventCreator->id, 'group_id' => $group->id]);
        expect($this->activityPolicy->update($admin, $activity))->toBeTrue();
    });

    it('denies non-admin, non-creator from updating a group event', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'member');
        $eventCreator = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
        $activity = Activity::factory()->create(['host_id' => $eventCreator->id, 'group_id' => $group->id]);
        expect($this->activityPolicy->update($this->user, $activity))->toBeFalse();
    });

    it('allows activity host to delete their activity', function () {
        $activity = Activity::factory()->create(['host_id' => $this->user->id, 'current_attendees' => 0, 'status' => 'draft']);
        expect($this->activityPolicy->delete($this->user, $activity))->toBeTrue();
    });

    it('denies deleting an activity with attendees', function () {
        $activity = Activity::factory()->create(['host_id' => $this->user->id, 'current_attendees' => 1, 'status' => 'draft']);
        expect($this->activityPolicy->delete($this->user, $activity))->toBeFalse();
    });

    it('denies deleting a completed activity', function () {
        $activity = Activity::factory()->create(['host_id' => $this->user->id, 'current_attendees' => 0, 'status' => 'completed']);
        expect($this->activityPolicy->delete($this->user, $activity))->toBeFalse();
    });

    it('denies other users from deleting an activity', function () {
        $activity = Activity::factory()->create(['current_attendees' => 0, 'status' => 'draft']);
        $otherUser = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
        expect($this->activityPolicy->delete($otherUser, $activity))->toBeFalse();
    });

    it('allows group event creator to delete their group event', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'admin');
        $activity = Activity::factory()->create(['host_id' => $this->user->id, 'group_id' => $group->id, 'current_attendees' => 0, 'status' => 'draft']);
        expect($this->activityPolicy->delete($this->user, $activity))->toBeTrue();
    });

    it('allows group admin to delete a group event', function () {
        $group = Group::factory()->create();
        $admin = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
        $this->groupService->addMember($group, $admin, 'admin');
        $eventCreator = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
        $activity = Activity::factory()->create(['host_id' => $eventCreator->id, 'group_id' => $group->id, 'current_attendees' => 0, 'status' => 'draft']);
        expect($this->activityPolicy->delete($admin, $activity))->toBeTrue();
    });

    it('denies non-admin, non-creator from deleting a group event', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'member');
        $eventCreator = User::factory()->create(['email' => fake()->unique()->safeEmail()]);
        $activity = Activity::factory()->create(['host_id' => $eventCreator->id, 'group_id' => $group->id, 'current_attendees' => 0, 'status' => 'draft']);
        expect($this->activityPolicy->delete($this->user, $activity))->toBeFalse();
    });
});
