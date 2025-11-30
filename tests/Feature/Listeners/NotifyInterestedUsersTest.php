<?php

use App\Events\PostConvertedToEvent;
use App\Listeners\NotifyInterestedUsers;
use App\Models\Activity;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('notifies all interested users when post is converted to event', function () {
    // Create post owner
    $owner = User::factory()->create(['username' => 'owner', 'display_name' => 'Post Owner']);

    // Create interested users
    $user1 = User::factory()->create(['username' => 'user1']);
    $user2 = User::factory()->create(['username' => 'user2']);
    $user3 = User::factory()->create(['username' => 'user3']);

    // Create post
    $post = Post::factory()->create(['user_id' => $owner->id]);

    // Add reactions from interested users
    $post->reactions()->create(['user_id' => $user1->id, 'reaction_type' => 'im_down']);
    $post->reactions()->create(['user_id' => $user2->id, 'reaction_type' => 'im_down']);
    $post->reactions()->create(['user_id' => $user3->id, 'reaction_type' => 'im_down']);

    // Create activity (converted event)
    $activity = Activity::factory()->create([
        'host_id' => $owner->id,
        'originated_from_post_id' => $post->id,
        'is_paid' => false,
        'price_cents' => null,
    ]);

    // Create conversion record
    $conversion = PostConversion::create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
        'reactions_at_conversion' => 3,
        'trigger_type' => 'manual',
    ]);

    // Dispatch event
    event(new PostConvertedToEvent($post, $activity, $conversion));

    // Verify notifications were created for interested users (not owner)
    expect(Notification::where('user_id', $user1->id)->count())->toBe(1);
    expect(Notification::where('user_id', $user2->id)->count())->toBe(1);
    expect(Notification::where('user_id', $user3->id)->count())->toBe(1);
    expect(Notification::where('user_id', $owner->id)->count())->toBe(0); // Owner not notified

    // Verify notification content
    $notification = Notification::where('user_id', $user1->id)->first();
    expect($notification->type)->toBe('post_converted_to_event');
    expect($notification->title)->toBe('🎉 Post Became an Event!');
    expect($notification->data['activity_id'])->toBe($activity->id);
    expect($notification->data['host_name'])->toBe('Post Owner');
    expect($notification->data['is_free'])->toBe(true);
});

test('does not notify post owner when post is converted', function () {
    $owner = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    // Owner reacts to their own post
    $post->reactions()->create(['user_id' => $owner->id, 'reaction_type' => 'im_down']);

    $activity = Activity::factory()->create(['host_id' => $owner->id]);
    $conversion = PostConversion::create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
        'reactions_at_conversion' => 1,
        'trigger_type' => 'manual',
    ]);

    event(new PostConvertedToEvent($post, $activity, $conversion));

    // Owner should not receive notification
    expect(Notification::where('user_id', $owner->id)->count())->toBe(0);
});

