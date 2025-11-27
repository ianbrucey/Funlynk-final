<?php

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\CommentService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->post = Post::factory()->create(['user_id' => $this->user->id]);
    $this->activity = Activity::factory()->create(['host_id' => $this->user->id]);
    $this->commentService = app(CommentService::class);
});

test('can create comment on post', function () {
    $comment = $this->commentService->createComment(
        $this->post,
        $this->user,
        'Test comment on post'
    );

    expect($comment)
        ->toBeInstanceOf(Comment::class)
        ->and($comment->commentable_type)->toBe(Post::class)
        ->and($comment->commentable_id)->toBe($this->post->id)
        ->and($comment->user_id)->toBe($this->user->id)
        ->and($comment->content)->toBe('Test comment on post')
        ->and($comment->depth)->toBe(0);
});

test('can create comment on activity', function () {
    $comment = $this->commentService->createComment(
        $this->activity,
        $this->user,
        'Test comment on activity'
    );

    expect($comment)
        ->toBeInstanceOf(Comment::class)
        ->and($comment->commentable_type)->toBe(Activity::class)
        ->and($comment->commentable_id)->toBe($this->activity->id)
        ->and($comment->depth)->toBe(0);
});

test('can create nested reply to comment', function () {
    $parentComment = $this->commentService->createComment(
        $this->post,
        $this->user,
        'Parent comment'
    );

    $reply = $this->commentService->createComment(
        $this->post,
        $this->user,
        'Reply to parent',
        $parentComment
    );

    expect($reply)
        ->toBeInstanceOf(Comment::class)
        ->and($reply->parent_comment_id)->toBe($parentComment->id)
        ->and($reply->depth)->toBe(1);
});

test('calculates correct depth for nested replies', function () {
    $level0 = $this->commentService->createComment($this->post, $this->user, 'Level 0');
    $level1 = $this->commentService->createComment($this->post, $this->user, 'Level 1', $level0);
    $level2 = $this->commentService->createComment($this->post, $this->user, 'Level 2', $level1);
    $level3 = $this->commentService->createComment($this->post, $this->user, 'Level 3', $level2);

    expect($level0->depth)->toBe(0)
        ->and($level1->depth)->toBe(1)
        ->and($level2->depth)->toBe(2)
        ->and($level3->depth)->toBe(3);
});

test('enforces maximum depth of 10', function () {
    $comment = $this->commentService->createComment($this->post, $this->user, 'Level 0');
    
    // Create comments up to depth 10
    for ($i = 1; $i <= 10; $i++) {
        $comment = $this->commentService->createComment(
            $this->post,
            $this->user,
            "Level {$i}",
            $comment
        );
    }

    expect($comment->depth)->toBe(10);

    // Attempting to create one more level should throw exception
    $this->commentService->createComment(
        $this->post,
        $this->user,
        'Level 11',
        $comment
    );
})->throws(Exception::class, 'Maximum reply depth reached');

test('validates content is not empty', function () {
    $this->commentService->createComment(
        $this->post,
        $this->user,
        ''
    );
})->throws(Exception::class, 'Comment content cannot be empty');

test('validates content does not exceed 500 characters', function () {
    $longContent = str_repeat('a', 501);
    
    $this->commentService->createComment(
        $this->post,
        $this->user,
        $longContent
    );
})->throws(Exception::class, 'Comment content cannot exceed 500 characters');

test('can update comment', function () {
    $comment = $this->commentService->createComment(
        $this->post,
        $this->user,
        'Original content'
    );

    $updatedComment = $this->commentService->updateComment($comment, 'Updated content');

    expect($updatedComment->content)->toBe('Updated content')
        ->and($updatedComment->is_edited)->toBeTrue();
});

test('can delete comment', function () {
    $comment = $this->commentService->createComment(
        $this->post,
        $this->user,
        'To be deleted'
    );

    $deleted = $this->commentService->deleteComment($comment);

    expect($deleted)->toBeTrue()
        ->and(Comment::find($comment->id))->toBeNull()
        ->and(Comment::withTrashed()->find($comment->id))->not->toBeNull();
});

test('can get comments for entity', function () {
    Comment::factory()->count(5)->forPost($this->post)->create();

    $comments = $this->commentService->getCommentsForEntity($this->post, 20);

    expect($comments)->toHaveCount(5);
});

test('parses mentions in comment content', function () {
    $mentionedUser = User::factory()->create(['username' => 'john_doe']);
    
    $comment = $this->commentService->createComment(
        $this->post,
        $this->user,
        'Hey @john_doe, check this out!'
    );

    expect($comment->content)->toContain('@john_doe');
});
