<?php

use App\Events\PostConvertedToEvent;
use App\Models\Activity;
use App\Models\Post;
use App\Models\PostConversion;
use App\Models\PostReaction;
use App\Models\Tag;
use App\Models\User;
use App\Services\ActivityConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ActivityConversionService;
    $this->user = User::factory()->create();
});

test('successfully converts post to event', function () {
    Event::fake();

    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 5,
        'title' => 'Test Post',
        'description' => 'Test Description',
    ]);

    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 20,
        'price' => 0,
    ];

    $activity = $this->service->createFromPost($post, $eventData, $this->user);

    expect($activity)->toBeInstanceOf(Activity::class);
    expect($activity->title)->toBe('Test Post');
    expect($activity->description)->toBe('Test Description');
    expect($activity->user_id)->toBe($this->user->id);
    expect($activity->originated_from_post_id)->toBe($post->id);
    expect($activity->status)->toBe('published');

    Event::assertDispatched(PostConvertedToEvent::class);
});

test('creates post conversion record', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 7,
    ]);

    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 15,
    ];

    $activity = $this->service->createFromPost($post, $eventData, $this->user);

    $conversion = PostConversion::where('post_id', $post->id)->first();

    expect($conversion)->not->toBeNull();
    expect($conversion->event_id)->toBe($activity->id);
    expect($conversion->converted_by)->toBe($this->user->id);
    expect($conversion->reactions_at_conversion)->toBe(7);
    expect($conversion->trigger_type)->toBe('manual');
});

test('updates post status to converted', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 5,
    ]);

    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 10,
    ];

    $this->service->createFromPost($post, $eventData, $this->user);

    $post->refresh();
    expect($post->status)->toBe('converted');
});

test('pre-fills event data from post', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 5,
        'title' => 'Original Title',
        'description' => 'Original Description',
        'location_name' => 'Original Location',
    ]);

    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 10,
    ];

    $activity = $this->service->createFromPost($post, $eventData, $this->user);

    expect($activity->title)->toBe('Original Title');
    expect($activity->description)->toBe('Original Description');
    expect($activity->location_name)->toBe('Original Location');
});

test('allows overriding post data with event data', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 5,
        'title' => 'Original Title',
    ]);

    $eventData = [
        'title' => 'New Event Title',
        'description' => 'New Event Description',
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 10,
    ];

    $activity = $this->service->createFromPost($post, $eventData, $this->user);

    expect($activity->title)->toBe('New Event Title');
    expect($activity->description)->toBe('New Event Description');
});

test('syncs tags from post to event', function () {
    $tag1 = Tag::factory()->create(['name' => 'hiking']);
    $tag2 = Tag::factory()->create(['name' => 'outdoors']);

    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 5,
        'tags' => ['hiking', 'outdoors'],
    ]);

    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 10,
    ];

    $activity = $this->service->createFromPost($post, $eventData, $this->user);

    expect($activity->tags()->count())->toBe(2);
    expect($activity->tags->pluck('name')->toArray())->toContain('hiking', 'outdoors');
});

test('throws exception if post is not eligible', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'expired',
        'reaction_count' => 5,
    ]);

    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 10,
    ];

    expect(fn () => $this->service->createFromPost($post, $eventData, $this->user))
        ->toThrow(\Exception::class, 'Post is not eligible for conversion');
});

test('transaction rollback on failure', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 5,
    ]);

    // Invalid event data will cause failure
    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 10,
        'price' => 'invalid', // This will cause a type error
    ];

    try {
        $this->service->createFromPost($post, $eventData, $this->user);
    } catch (\Exception $e) {
        // Expected to fail
    }

    // Verify post status wasn't changed
    $post->refresh();
    expect($post->status)->toBe('active');

    // Verify no conversion record was created
    expect(PostConversion::where('post_id', $post->id)->exists())->toBeFalse();
});

test('previewConversion returns correct data', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Test Post',
    ]);

    // Create some reactions
    PostReaction::factory()->count(5)->create([
        'post_id' => $post->id,
        'reaction_type' => 'im_down',
    ]);

    $preview = $this->service->previewConversion($post);

    expect($preview)->toHaveKeys([
        'interested_users_count',
        'invited_users_count',
        'total_potential_attendees',
        'suggested_capacity',
        'event_preview',
    ]);

    expect($preview['interested_users_count'])->toBe(5);
    expect($preview['suggested_capacity'])->toBe(10); // ceil(5 * 1.5) = 8, but min is 10
});

test('previewConversion calculates suggested capacity correctly', function () {
    $post = Post::factory()->create(['user_id' => $this->user->id]);

    // Create 20 reactions
    PostReaction::factory()->count(20)->create([
        'post_id' => $post->id,
        'reaction_type' => 'im_down',
    ]);

    $preview = $this->service->previewConversion($post);

    expect($preview['interested_users_count'])->toBe(20);
    expect($preview['suggested_capacity'])->toBe(30); // ceil(20 * 1.5) = 30
});

test('previewConversion includes event preview data', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Test Post',
        'description' => 'Test Description',
        'location_name' => 'Test Location',
    ]);

    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'price' => 15,
    ];

    $preview = $this->service->previewConversion($post, $eventData);

    expect($preview['event_preview']['title'])->toBe('Test Post');
    expect($preview['event_preview']['description'])->toBe('Test Description');
    expect($preview['event_preview']['location'])->toBe('Test Location');
    expect($preview['event_preview']['start_time'])->toBe($eventData['start_time']);
    expect($preview['event_preview']['price'])->toBe(15);
});
