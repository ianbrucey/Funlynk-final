<?php

use App\Events\PostConvertedToEvent;
use App\Mail\PostConvertedToEventMail;
use App\Models\Activity;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sends notifications to interested users when post is converted', function () {
    Mail::fake();

    $owner = User::factory()->create(['username' => 'owner', 'display_name' => 'Post Owner']);
    $user1 = User::factory()->create(['username' => 'user1']);
    $user2 = User::factory()->create(['username' => 'user2']);

    $post = Post::factory()->create(['user_id' => $owner->id]);
    $post->reactions()->create(['user_id' => $user1->id, 'reaction_type' => 'im_down']);
    $post->reactions()->create(['user_id' => $user2->id, 'reaction_type' => 'im_down']);

    $activity = Activity::factory()->create(['host_id' => $owner->id, 'originated_from_post_id' => $post->id]);
    $conversion = PostConversion::create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
        'reactions_at_conversion' => 2,
        'trigger_type' => 'manual',
    ]);

    event(new PostConvertedToEvent($post, $activity, $conversion));

    // Verify in-app notifications were created for both interested users
    expect(Notification::where('type', 'post_converted_to_event')->count())->toBe(2);
    expect(Notification::where('user_id', $user1->id)->count())->toBe(1);
    expect(Notification::where('user_id', $user2->id)->count())->toBe(1);
});

test('respects user notification preference - all', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $user = User::factory()->create(['notification_preference' => 'all', 'email_on_post_converted' => true]);

    $post = Post::factory()->create(['user_id' => $owner->id]);
    $post->reactions()->create(['user_id' => $user->id, 'reaction_type' => 'im_down']);

    $activity = Activity::factory()->create(['host_id' => $owner->id]);
    $conversion = PostConversion::create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
        'reactions_at_conversion' => 1,
        'trigger_type' => 'manual',
    ]);

    event(new PostConvertedToEvent($post, $activity, $conversion));

    Mail::assertQueued(PostConvertedToEventMail::class);
});

test('respects user notification preference - in_app_only', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $user = User::factory()->create(['notification_preference' => 'in_app_only']);

    $post = Post::factory()->create(['user_id' => $owner->id]);
    $post->reactions()->create(['user_id' => $user->id, 'reaction_type' => 'im_down']);

    $activity = Activity::factory()->create(['host_id' => $owner->id]);
    $conversion = PostConversion::create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
        'reactions_at_conversion' => 1,
        'trigger_type' => 'manual',
    ]);

    event(new PostConvertedToEvent($post, $activity, $conversion));

    Mail::assertNotQueued(PostConvertedToEventMail::class);
    expect(Notification::where('user_id', $user->id)->count())->toBe(1);
});

test('respects user notification preference - none', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $user = User::factory()->create(['notification_preference' => 'none']);

    $post = Post::factory()->create(['user_id' => $owner->id]);
    $post->reactions()->create(['user_id' => $user->id, 'reaction_type' => 'im_down']);

    $activity = Activity::factory()->create(['host_id' => $owner->id]);
    $conversion = PostConversion::create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
        'reactions_at_conversion' => 1,
        'trigger_type' => 'manual',
    ]);

    event(new PostConvertedToEvent($post, $activity, $conversion));

    Mail::assertNotQueued(PostConvertedToEventMail::class);
    expect(Notification::where('user_id', $user->id)->count())->toBe(0);
});

test('respects email_on_post_converted preference', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $user = User::factory()->create(['notification_preference' => 'all', 'email_on_post_converted' => false]);

    $post = Post::factory()->create(['user_id' => $owner->id]);
    $post->reactions()->create(['user_id' => $user->id, 'reaction_type' => 'im_down']);

    $activity = Activity::factory()->create(['host_id' => $owner->id]);
    $conversion = PostConversion::create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
        'reactions_at_conversion' => 1,
        'trigger_type' => 'manual',
    ]);

    event(new PostConvertedToEvent($post, $activity, $conversion));

    Mail::assertNotQueued(PostConvertedToEventMail::class);
    expect(Notification::where('user_id', $user->id)->count())->toBe(1);
});

