<?php

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Policies\PostPolicy;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->postPolicy = new PostPolicy;
    $this->groupService = new GroupService;
    $this->user = User::factory()->create(['email' => 'postpolicy_user@example.com']);
    $this->actingAs($this->user);
});

describe('PostPolicy', function () {
    it('allows anyone to view any post', function () {
        $post = Post::factory()->create();
        expect($this->postPolicy->viewAny($this->user))->toBeTrue();
        expect($this->postPolicy->viewAny(null))->toBeTrue();
    });

    it('allows anyone to view a non-group post', function () {
        $post = Post::factory()->create();
        expect($this->postPolicy->view($this->user, $post))->toBeTrue();
        expect($this->postPolicy->view(null, $post))->toBeTrue();
    });

    it('allows group members to view a private group post', function () {
        $group = Group::factory()->create(['privacy' => 'private']);
        $this->groupService->addMember($group, $this->user);
        $post = Post::factory()->create(['group_id' => $group->id]);
        expect($this->postPolicy->view($this->user, $post))->toBeTrue();
    });

    it('denies non-members from viewing a private group post', function () {
        $group = Group::factory()->create(['privacy' => 'private']);
        $post = Post::factory()->create(['group_id' => $group->id]);
        $nonMember = User::factory()->create(['email' => 'nonmember_view_private_group_post@example.com']);
        expect($this->postPolicy->view($nonMember, $post))->toBeFalse();
    });

    it('allows authenticated users to create a post', function () {
        expect($this->postPolicy->create($this->user))->toBeTrue();
    });

    it('denies guests from creating a post', function () {
        expect($this->postPolicy->create(null))->toBeFalse();
    });

    it('allows group members to create a post in a group', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user);
        expect($this->postPolicy->createInGroup($this->user, $group))->toBeTrue();
    });

    it('denies non-members from creating a post in a group', function () {
        $group = Group::factory()->create();
        $nonMember = User::factory()->create(['email' => 'nonmember_create_group_post@example.com']);
        expect($this->postPolicy->createInGroup($nonMember, $group))->toBeFalse();
    });

    it('allows post creator to update their post', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        expect($this->postPolicy->update($this->user, $post))->toBeTrue();
    });

    it('denies other users from updating a non-group post', function () {
        $post = Post::factory()->create();
        $otherUser = User::factory()->create(['email' => 'otheruser_update_non_group_post@example.com']);
        expect($this->postPolicy->update($otherUser, $post))->toBeFalse();
    });

    it('allows group post creator to update their group post', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'admin');
        $post = Post::factory()->create(['user_id' => $this->user->id, 'group_id' => $group->id]);
        expect($this->postPolicy->update($this->user, $post))->toBeTrue();
    });

    it('allows group admin to update a group post', function () {
        $group = Group::factory()->create();
        $admin = User::factory()->create(['email' => 'admin_update_group_post@example.com']);
        $this->groupService->addMember($group, $admin, 'admin');
        $postCreator = User::factory()->create(['email' => 'postcreator_update_group_post@example.com']);
        $post = Post::factory()->create(['user_id' => $postCreator->id, 'group_id' => $group->id]);
        expect($this->postPolicy->update($admin, $post))->toBeTrue();
    });

    it('denies non-admin, non-creator from updating a group post', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'member');
        $postCreator = User::factory()->create(['email' => 'postcreator_deny_update_group_post@example.com']);
        $post = Post::factory()->create(['user_id' => $postCreator->id, 'group_id' => $group->id]);
        expect($this->postPolicy->update($this->user, $post))->toBeFalse();
    });

    it('allows post creator to delete their post', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        expect($this->postPolicy->delete($this->user, $post))->toBeTrue();
    });

    it('denies other users from deleting a non-group post', function () {
        $post = Post::factory()->create();
        $otherUser = User::factory()->create(['email' => 'otheruser_delete_non_group_post@example.com']);
        expect($this->postPolicy->delete($otherUser, $post))->toBeFalse();
    });

    it('allows group post creator to delete their group post', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'admin');
        $post = Post::factory()->create(['user_id' => $this->user->id, 'group_id' => $group->id]);
        expect($this->postPolicy->delete($this->user, $post))->toBeTrue();
    });

    it('allows group admin to delete a group post', function () {
        $group = Group::factory()->create();
        $admin = User::factory()->create(['email' => 'admin_delete_group_post@example.com']);
        $this->groupService->addMember($group, $admin, 'admin');
        $postCreator = User::factory()->create(['email' => 'postcreator_delete_group_post@example.com']);
        $post = Post::factory()->create(['user_id' => $postCreator->id, 'group_id' => $group->id]);
        expect($this->postPolicy->delete($admin, $post))->toBeTrue();
    });

    it('denies non-admin, non-creator from deleting a group post', function () {
        $group = Group::factory()->create();
        $this->groupService->addMember($group, $this->user, 'member');
        $postCreator = User::factory()->create(['email' => 'postcreator_deny_delete_group_post@example.com']);
        $post = Post::factory()->create(['user_id' => $postCreator->id, 'group_id' => $group->id]);
        expect($this->postPolicy->delete($this->user, $post))->toBeFalse();
    });
});
