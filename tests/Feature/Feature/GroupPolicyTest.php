<?php

use App\Models\Group;
use App\Models\User;
use App\Policies\GroupPolicy;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->groupPolicy = new GroupPolicy;
    $this->groupService = new GroupService;
    $this->user = User::factory()->create(['email' => 'grouppolicy_user@example.com']);
    $this->actingAs($this->user);
});

describe('GroupPolicy', function () {
    it('allows anyone to view any group', function () {
        expect($this->groupPolicy->viewAny($this->user))->toBeTrue();
        expect($this->groupPolicy->viewAny(null))->toBeTrue(); // Guest user
    });

    it('allows anyone to view a public group', function () {
        $group = Group::factory()->create(['privacy' => 'public']);
        expect($this->groupPolicy->view($this->user, $group))->toBeTrue();
        expect($this->groupPolicy->view(null, $group))->toBeTrue(); // Guest user
    });

    it('allows members to view a private group', function () {
        $group = Group::factory()->create(['privacy' => 'private']);
        $this->groupService->addMember($group, $this->user);
        expect($this->groupPolicy->view($this->user, $group))->toBeTrue();
    });

    it('denies non-members from viewing a private group', function () {
        $group = Group::factory()->create(['privacy' => 'private']);
        $nonMember = User::factory()->create(['email' => 'nonmember_view_private_group@example.com']);
        expect($this->groupPolicy->view($nonMember, $group))->toBeFalse();
        expect($this->groupPolicy->view(null, $group))->toBeFalse(); // Guest user
    });

    it('allows authenticated users to create a group', function () {
        expect($this->groupPolicy->create($this->user))->toBeTrue();
    });

    it('denies guests from creating a group', function () {
        expect($this->groupPolicy->create(null))->toBeFalse();
    });

    it('allows group admins to update a group', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'admin');
        expect($this->groupPolicy->update($this->user, $group))->toBeTrue();
    });

    it('denies non-admins from updating a group', function () {
        $group = Group::factory()->create();
        $member = User::factory()->create(['email' => 'member_update_group@example.com']);
        $this->groupService->addMember($group, $member, 'member');
        expect($this->groupPolicy->update($member, $group))->toBeFalse();
    });

    it('allows group admins to delete a group', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'admin');
        expect($this->groupPolicy->delete($this->user, $group))->toBeTrue();
    });

    it('denies non-admins from deleting a group', function () {
        $group = Group::factory()->create();
        $member = User::factory()->create(['email' => 'member_delete_group@example.com']);
        $this->groupService->addMember($group, $member, 'member');
        expect($this->groupPolicy->delete($member, $group))->toBeFalse();
    });

    it('allows non-members to join a group', function () {
        $group = Group::factory()->create();
        expect($this->groupPolicy->join($this->user, $group))->toBeTrue();
    });

    it('denies members from joining a group they are already in', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user);
        expect($this->groupPolicy->join($this->user, $group))->toBeFalse();
    });

    it('allows members to leave a group', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user);
        expect($this->groupPolicy->leave($this->user, $group))->toBeTrue();
    });

    it('denies non-members from leaving a group', function () {
        $group = Group::factory()->create();
        $nonMember = User::factory()->create();
        expect($this->groupPolicy->leave($nonMember, $group))->toBeFalse();
    });

    it('denies the last admin from leaving a group', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'admin');
        expect($this->groupPolicy->leave($this->user, $group))->toBeFalse();
    });

    it('allows group members to invite others', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user);
        expect($this->groupPolicy->invite($this->user, $group))->toBeTrue();
    });

    it('denies non-members from inviting others', function () {
        $group = Group::factory()->create();
        $nonMember = User::factory()->create(['email' => 'nonmember_invite_others@example.com']);
        expect($this->groupPolicy->invite($nonMember, $group))->toBeFalse();
    });

    it('allows group admins to remove members', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'admin');
        expect($this->groupPolicy->removeMember($this->user, $group))->toBeTrue();
    });

    it('denies non-admins from removing members', function () {
        $group = Group::factory()->create();
        $member = User::factory()->create(['email' => 'member_remove_members@example.com']);
        $this->groupService->addMember($group, $member, 'member');
        expect($this->groupPolicy->removeMember($member, $group))->toBeFalse();
    });

    it('allows group admins to approve join requests', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'admin');
        expect($this->groupPolicy->approveRequest($this->user, $group))->toBeTrue();
    });

    it('denies non-admins from approving join requests', function () {
        $group = Group::factory()->create();
        $member = User::factory()->create(['email' => 'member_approve_requests@example.com']);
        $this->groupService->addMember($group, $member, 'member');
        expect($this->groupPolicy->approveRequest($member, $group))->toBeFalse();
    });
});
