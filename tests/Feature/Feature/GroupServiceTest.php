<?php

use App\Events\GroupCreated;
use App\Events\GroupJoinRequestApproved;
use App\Events\GroupJoinRequestReceived;
use App\Events\GroupMemberJoined;
use App\Events\GroupMemberRemoved;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\GroupMember;
use App\Models\Tag;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disable event listeners to avoid transaction issues in tests
    Event::fake();

    $this->groupService = new GroupService;
    $this->user = User::factory()->create(['email' => 'test@example.com']);
    $this->actingAs($this->user);
});

describe('GroupService', function () {
    it('can create a group', function () {
        Event::fake();

        $data = [
            'name' => 'Test Group',
            'description' => 'This is a test group.',
            'privacy' => 'public',
            'tags' => [Tag::factory()->create()->id],
        ];

        $group = $this->groupService->createGroup($this->user, $data);

        expect($group)->toBeInstanceOf(Group::class);
        expect($group->name)->toBe('Test Group');
        expect($group->created_by)->toBe($this->user->id);
        expect($group->members()->count())->toBe(1);
        expect($group->memberships()->first()->user_id)->toBe($this->user->id);
        expect($group->memberships()->first()->role)->toBe('admin');
        expect($group->tags()->count())->toBe(1);

        Event::assertDispatched(GroupCreated::class);
    });

    it('can update a group', function () {
        $group = Group::factory()->create(['created_by' => $this->user->id]);
        $this->groupService->addMember($group, $this->user, 'admin');

        $newData = [
            'name' => 'Updated Group Name',
            'description' => 'Updated description.',
            'privacy' => 'private',
        ];

        $updatedGroup = $this->groupService->updateGroup($group, $newData);

        expect($updatedGroup->name)->toBe('Updated Group Name');
        expect($updatedGroup->description)->toBe('Updated description.');
        expect($updatedGroup->privacy)->toBe('private');
    });

    it('can delete a group', function () {
        $group = Group::factory()->create(['created_by' => $this->user->id]);
        $this->groupService->addMember($group, $this->user, 'admin');

        $this->groupService->deleteGroup($group);

        expect(Group::find($group->id))->toBeNull();
        expect(GroupMember::where('group_id', $group->id)->count())->toBe(0);
    });

    it('can add a member to a group', function () {
        Event::fake();

        $group = Group::factory()->create();
        $newUser = User::factory()->create(['email' => 'newuser@example.com']);

        $member = $this->groupService->addMember($group, $newUser);

        expect($member)->toBeInstanceOf(GroupMember::class);
        expect($group->members()->count())->toBe(1);
        // Note: member_count is updated by event listener which is faked
        // So we only check the relationship count here

        Event::assertDispatched(GroupMemberJoined::class);
    });

    it('can remove a member from a group', function () {
        Event::fake();

        $group = Group::factory()->create();
        $adminUser = User::factory()->create(['email' => 'adminuser_remove@example.com']);
        $memberUser = User::factory()->create(['email' => 'memberuser_remove@example.com']);

        $this->groupService->addMember($group, $adminUser, 'admin');
        $this->groupService->addMember($group, $memberUser, 'member');

        $this->groupService->removeMember($group, $memberUser);

        expect($group->members()->count())->toBe(1);
        // Note: member_count is updated by event listener which is faked
        // So we only check the relationship count here

        Event::assertDispatched(GroupMemberRemoved::class);
    });

    it('cannot remove the last admin from a group', function () {
        $group = Group::factory()->create();
        $adminUser = User::factory()->create(['email' => 'adminuser@example.com']);
        $this->groupService->addMember($group, $adminUser, 'admin');

        $this->groupService->removeMember($group, $adminUser);
    })->throws(\Exception::class, 'Cannot remove the last admin from the group.');

    it('can update a member role', function () {
        $group = Group::factory()->create();
        $memberUser = User::factory()->create(['email' => 'memberuser_update_role@example.com']);
        $this->groupService->addMember($group, $memberUser, 'member');

        $updatedMember = $this->groupService->updateMemberRole($group, $memberUser, 'admin');

        expect($updatedMember->role)->toBe('admin');
    });

    it('can search public groups', function () {
        $tag = Tag::factory()->create();
        $group1 = Group::factory()->create(['name' => 'Public Group A', 'privacy' => 'public']);
        $group1->tags()->attach($tag);
        $group2 = Group::factory()->create(['name' => 'Public Group B', 'privacy' => 'public']);
        $group3 = Group::factory()->create(['name' => 'Private Group C', 'privacy' => 'private']);

        $results = $this->groupService->searchPublicGroups('Public');
        expect($results->count())->toBe(2);

        $resultsWithTag = $this->groupService->searchPublicGroups('Public', [$tag->id]);
        expect($resultsWithTag->count())->toBe(1);
    });

    it('can get groups by tag', function () {
        $tag = Tag::factory()->create();
        $group1 = Group::factory()->create(['privacy' => 'public']);
        $group2 = Group::factory()->create(['privacy' => 'public']);
        $group3 = Group::factory()->create(['privacy' => 'private']);

        $group1->tags()->attach($tag);
        $group2->tags()->attach($tag);

        $results = $this->groupService->getGroupsByTag($tag);
        expect($results->count())->toBe(2);
    });

    it('can get user groups', function () {
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();
        $this->groupService->addMember($group1, $this->user);
        $this->groupService->addMember($group2, $this->user);

        $userGroups = $this->groupService->getUserGroups($this->user);
        expect($userGroups->count())->toBe(2);
    });

    it('can get group members', function () {
        $group = Group::factory()->create();
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $this->groupService->addMember($group, $user1);
        $this->groupService->addMember($group, $user2);

        $members = $this->groupService->getGroupMembers($group);
        expect($members->count())->toBe(2);
    });

    it('can sync group tags', function () {
        $group = Group::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $this->groupService->syncGroupTags($group, [$tag1->id]);
        expect($group->tags()->count())->toBe(1);
        expect($group->tags()->first()->id)->toBe($tag1->id);

        $this->groupService->syncGroupTags($group, [$tag2->id]);
        expect($group->tags()->count())->toBe(1);
        expect($group->tags()->first()->id)->toBe($tag2->id);
    });

    it('can create a join request', function () {
        Event::fake();

        $group = Group::factory()->create(['privacy' => 'private']);
        $requester = User::factory()->create(['email' => 'requester_create_join@example.com']);

        $joinRequest = $this->groupService->createJoinRequest($group, $requester);

        expect($joinRequest)->toBeInstanceOf(GroupJoinRequest::class);
        expect($joinRequest->group_id)->toBe($group->id);
        expect($joinRequest->user_id)->toBe($requester->id);
        expect($joinRequest->status)->toBe('pending');

        Event::assertDispatched(GroupJoinRequestReceived::class);
    });

    it('can approve a join request', function () {
        Event::fake();

        $group = Group::factory()->create(['privacy' => 'private']);
        $admin = User::factory()->create(['email' => 'admin_approve_join@example.com']);
        $this->groupService->addMember($group, $admin, 'admin');
        $requester = User::factory()->create(['email' => 'requester_approve_join@example.com']);
        $joinRequest = $this->groupService->createJoinRequest($group, $requester);

        $this->groupService->approveJoinRequest($joinRequest, $admin);

        $joinRequest->refresh();
        expect($joinRequest->status)->toBe('approved');
        expect($group->members()->where('user_id', $requester->id)->exists())->toBeTrue();

        Event::assertDispatched(GroupJoinRequestApproved::class);
    });

    it('can deny a join request', function () {
        $group = Group::factory()->create(['privacy' => 'private']);
        $requester = User::factory()->create(['email' => 'requester_deny_join@example.com']);
        $joinRequest = $this->groupService->createJoinRequest($group, $requester);
        $admin = User::factory()->create(['email' => 'admin_deny_join@example.com']);

        $this->groupService->denyJoinRequest($joinRequest, $admin);

        $joinRequest->refresh();
        expect($joinRequest->status)->toBe('denied');
    });

    it('can check if user is a group member', function () {
        $group = Group::factory()->create();
        $memberUser = User::factory()->create(['email' => 'memberuser_check@example.com']);
        $nonMemberUser = User::factory()->create(['email' => 'nonmemberuser_check@example.com']);

        $this->groupService->addMember($group, $memberUser);

        expect($this->groupService->isGroupMember($group, $memberUser))->toBeTrue();
        expect($this->groupService->isGroupMember($group, $nonMemberUser))->toBeFalse();
    });

    it('can check if user is a group admin', function () {
        $group = Group::factory()->create();
        $adminUser = User::factory()->create(['email' => 'adminuser_check_admin@example.com']);
        $memberUser = User::factory()->create(['email' => 'memberuser_check_admin@example.com']);

        $this->groupService->addMember($group, $adminUser, 'admin');
        $this->groupService->addMember($group, $memberUser, 'member');

        expect($this->groupService->isGroupAdmin($group, $adminUser))->toBeTrue();
        expect($this->groupService->isGroupAdmin($group, $memberUser))->toBeFalse();
    });
});
