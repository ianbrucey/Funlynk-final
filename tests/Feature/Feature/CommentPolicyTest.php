<?php

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Policies\CommentPolicy;

beforeEach(function () {
    $this->policy = new CommentPolicy();
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->post = Post::factory()->create(['user_id' => $this->user->id]);
    $this->activity = Activity::factory()->create(['host_id' => $this->user->id]);
});

test('users can view any comments', function () {
    expect($this->policy->viewAny($this->user))->toBeTrue();
});

test('users can view individual comments', function () {
    $comment = Comment::factory()->forPost($this->post)->create();
    
    expect($this->policy->view($this->user, $comment))->toBeTrue();
});

test('authenticated users can create comments', function () {
    expect($this->policy->create($this->user))->toBeTrue();
});

test('users can update their own comments', function () {
    $comment = Comment::factory()->forPost($this->post)->create([
        'user_id' => $this->user->id,
    ]);
    
    expect($this->policy->update($this->user, $comment))->toBeTrue();
});

test('users cannot update other users comments', function () {
    $comment = Comment::factory()->forPost($this->post)->create([
        'user_id' => $this->otherUser->id,
    ]);
    
    expect($this->policy->update($this->user, $comment))->toBeFalse();
});

test('users can delete their own comments', function () {
    $comment = Comment::factory()->forPost($this->post)->create([
        'user_id' => $this->user->id,
    ]);
    
    expect($this->policy->delete($this->user, $comment))->toBeTrue();
});

test('post owners can delete comments on their posts', function () {
    $comment = Comment::factory()->forPost($this->post)->create([
        'user_id' => $this->otherUser->id,
    ]);
    
    expect($this->policy->delete($this->user, $comment))->toBeTrue();
});

test('activity hosts can delete comments on their activities', function () {
    $comment = Comment::factory()->forActivity($this->activity)->create([
        'user_id' => $this->otherUser->id,
    ]);
    
    expect($this->policy->delete($this->user, $comment))->toBeTrue();
});

test('users cannot delete other users comments on other users content', function () {
    $otherPost = Post::factory()->create(['user_id' => $this->otherUser->id]);
    $comment = Comment::factory()->forPost($otherPost)->create([
        'user_id' => $this->otherUser->id,
    ]);
    
    expect($this->policy->delete($this->user, $comment))->toBeFalse();
});

test('post owners can moderate comments on their posts', function () {
    $comment = Comment::factory()->forPost($this->post)->create([
        'user_id' => $this->otherUser->id,
    ]);
    
    expect($this->policy->moderate($this->user, $comment))->toBeTrue();
});

test('activity hosts can moderate comments on their activities', function () {
    $comment = Comment::factory()->forActivity($this->activity)->create([
        'user_id' => $this->otherUser->id,
    ]);
    
    expect($this->policy->moderate($this->user, $comment))->toBeTrue();
});

test('users cannot moderate comments on other users content', function () {
    $otherPost = Post::factory()->create(['user_id' => $this->otherUser->id]);
    $comment = Comment::factory()->forPost($otherPost)->create();
    
    expect($this->policy->moderate($this->user, $comment))->toBeFalse();
});
