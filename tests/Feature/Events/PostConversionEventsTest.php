<?php

use App\Events\PostConversionPrompted;
use App\Events\PostConvertedToEvent;
use App\Events\PostInvitationMigrated;
use App\Listeners\MigratePostInvitations;
use App\Listeners\SendConversionPromptNotification;
use App\Models\Activity;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostConversion;
use App\Models\PostInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('PostConversionPrompted event dispatches correctly', function () {
    Event::fake();

    $post = Post::factory()->create(['reaction_count' => 5]);

    event(new PostConversionPrompted($post, 'soft'));

    Event::assertDispatched(PostConversionPrompted::class, function ($event) use ($post) {
        return $event->post->id === $post->id && $event->threshold === 'soft';
    });
});

test('SendConversionPromptNotification creates notification for post owner', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'title' => 'Test Post',
        'reaction_count' => 5,
    ]);

    $event = new PostConversionPrompted($post, 'soft');
    $listener = new SendConversionPromptNotification;

    $listener->handle($event);

    $notification = Notification::where('user_id', $user->id)
        ->where('type', 'post_conversion_prompt')
        ->first();

    expect($notification)->not->toBeNull();
    expect($notification->data['post_id'])->toBe($post->id);
    expect($notification->data['post_title'])->toBe('Test Post');
    expect($notification->data['reaction_count'])->toBe(5);
    expect($notification->data['threshold'])->toBe('soft');
    expect($notification->data['message'])->toContain('5 people are interested');
});

test('SendConversionPromptNotification uses strong message for 10+ reactions', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'reaction_count' => 10,
    ]);

    $event = new PostConversionPrompted($post, 'strong');
    $listener = new SendConversionPromptNotification;

    $listener->handle($event);

    $notification = Notification::where('user_id', $user->id)->first();

    expect($notification->data['message'])->toContain('10+ people want to join');
    expect($notification->data['message'])->toContain('🔥');
});

test('PostConvertedToEvent event dispatches correctly', function () {
    Event::fake();

    $post = Post::factory()->create();
    $activity = Activity::factory()->create();
    $conversion = PostConversion::factory()->create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
    ]);

    event(new PostConvertedToEvent($post, $activity, $conversion));

    Event::assertDispatched(PostConvertedToEvent::class, function ($event) use ($post, $activity) {
        return $event->post->id === $post->id && $event->activity->id === $activity->id;
    });
});

test('MigratePostInvitations migrates pending invitations', function () {
    $inviter = User::factory()->create();
    $invitee1 = User::factory()->create();
    $invitee2 = User::factory()->create();

    $post = Post::factory()->create();
    $activity = Activity::factory()->create();
    $conversion = PostConversion::factory()->create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
    ]);

    // Create pending invitations
    $invitation1 = PostInvitation::factory()->create([
        'post_id' => $post->id,
        'inviter_id' => $inviter->id,
        'invitee_id' => $invitee1->id,
        'status' => 'pending',
    ]);

    $invitation2 = PostInvitation::factory()->create([
        'post_id' => $post->id,
        'inviter_id' => $inviter->id,
        'invitee_id' => $invitee2->id,
        'status' => 'pending',
    ]);

    $event = new PostConvertedToEvent($post, $activity, $conversion);
    $listener = new MigratePostInvitations;

    $listener->handle($event);

    // Check invitations were migrated
    $invitation1->refresh();
    $invitation2->refresh();

    expect($invitation1->status)->toBe('migrated');
    expect($invitation2->status)->toBe('migrated');
});

test('MigratePostInvitations creates notifications for invited users', function () {
    $inviter = User::factory()->create(['display_name' => 'John Doe']);
    $invitee = User::factory()->create();

    $post = Post::factory()->create(['title' => 'Original Post']);
    $activity = Activity::factory()->create([
        'title' => 'Converted Event',
        'location_name' => 'Test Location',
        'price' => 15,
    ]);
    $conversion = PostConversion::factory()->create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
    ]);

    PostInvitation::factory()->create([
        'post_id' => $post->id,
        'inviter_id' => $inviter->id,
        'invitee_id' => $invitee->id,
        'status' => 'pending',
    ]);

    $event = new PostConvertedToEvent($post, $activity, $conversion);
    $listener = new MigratePostInvitations;

    $listener->handle($event);

    $notification = Notification::where('user_id', $invitee->id)
        ->where('type', 'post_invitation_converted')
        ->first();

    expect($notification)->not->toBeNull();
    expect($notification->data['activity_title'])->toBe('Converted Event');
    expect($notification->data['inviter_name'])->toBe('John Doe');
    expect($notification->data['location'])->toBe('Test Location');
    expect($notification->data['price'])->toBe(15);
    expect($notification->data['is_free'])->toBeFalse();
});

test('MigratePostInvitations updates conversion record with invited count', function () {
    $inviter = User::factory()->create();
    $post = Post::factory()->create();
    $activity = Activity::factory()->create();
    $conversion = PostConversion::factory()->create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
        'invited_users_notified' => 0,
    ]);

    // Create 3 pending invitations
    PostInvitation::factory()->count(3)->create([
        'post_id' => $post->id,
        'inviter_id' => $inviter->id,
        'status' => 'pending',
    ]);

    $event = new PostConvertedToEvent($post, $activity, $conversion);
    $listener = new MigratePostInvitations;

    $listener->handle($event);

    $conversion->refresh();
    expect($conversion->invited_users_notified)->toBe(3);
});

test('MigratePostInvitations dispatches PostInvitationMigrated events', function () {
    Event::fake([PostInvitationMigrated::class]);

    $inviter = User::factory()->create();
    $post = Post::factory()->create();
    $activity = Activity::factory()->create();
    $conversion = PostConversion::factory()->create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
    ]);

    PostInvitation::factory()->count(2)->create([
        'post_id' => $post->id,
        'inviter_id' => $inviter->id,
        'status' => 'pending',
    ]);

    $event = new PostConvertedToEvent($post, $activity, $conversion);
    $listener = new MigratePostInvitations;

    $listener->handle($event);

    Event::assertDispatchedTimes(PostInvitationMigrated::class, 2);
});

test('MigratePostInvitations only migrates pending invitations', function () {
    $inviter = User::factory()->create();
    $post = Post::factory()->create();
    $activity = Activity::factory()->create();
    $conversion = PostConversion::factory()->create([
        'post_id' => $post->id,
        'event_id' => $activity->id,
    ]);

    // Create one pending and one accepted invitation
    $pendingInvitation = PostInvitation::factory()->create([
        'post_id' => $post->id,
        'inviter_id' => $inviter->id,
        'status' => 'pending',
    ]);

    $acceptedInvitation = PostInvitation::factory()->create([
        'post_id' => $post->id,
        'inviter_id' => $inviter->id,
        'status' => 'accepted',
    ]);

    $event = new PostConvertedToEvent($post, $activity, $conversion);
    $listener = new MigratePostInvitations;

    $listener->handle($event);

    $pendingInvitation->refresh();
    $acceptedInvitation->refresh();

    expect($pendingInvitation->status)->toBe('migrated');
    expect($acceptedInvitation->status)->toBe('accepted'); // Unchanged
});
