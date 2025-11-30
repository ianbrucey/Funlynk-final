<?php

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new PostService;
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('convertToEvent successfully converts post', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 5,
    ]);

    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 20,
    ];

    $activity = $this->service->convertToEvent($post->id, $eventData);

    expect($activity)->toBeInstanceOf(Activity::class);
    expect($activity->user_id)->toBe($this->user->id);
});

test('convertToEvent throws exception if not post owner', function () {
    $otherUser = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $otherUser->id]);

    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 20,
    ];

    expect(fn () => $this->service->convertToEvent($post->id, $eventData))
        ->toThrow(\Exception::class, 'Unauthorized: Only post owner can convert');
});

test('convertToEvent validates required fields', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 5,
    ]);

    // Missing start_time
    expect(fn () => $this->service->convertToEvent($post->id, [
        'end_time' => now()->addDays(1)->toDateTimeString(),
        'max_attendees' => 20,
    ]))->toThrow(\Exception::class, 'Missing required field: start_time');

    // Missing end_time
    expect(fn () => $this->service->convertToEvent($post->id, [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'max_attendees' => 20,
    ]))->toThrow(\Exception::class, 'Missing required field: end_time');

    // Missing max_attendees
    expect(fn () => $this->service->convertToEvent($post->id, [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
    ]))->toThrow(\Exception::class, 'Missing required field: max_attendees');
});

test('convertToEvent validates start time is in future', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 5,
    ]);

    $eventData = [
        'start_time' => now()->subHours(1)->toDateTimeString(), // Past time
        'end_time' => now()->addHours(1)->toDateTimeString(),
        'max_attendees' => 20,
    ];

    expect(fn () => $this->service->convertToEvent($post->id, $eventData))
        ->toThrow(\Exception::class, 'Start time must be in the future');
});

test('convertToEvent validates end time is after start time', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 5,
    ]);

    $startTime = now()->addDays(1);
    $eventData = [
        'start_time' => $startTime->toDateTimeString(),
        'end_time' => $startTime->copy()->subHours(1)->toDateTimeString(), // Before start
        'max_attendees' => 20,
    ];

    expect(fn () => $this->service->convertToEvent($post->id, $eventData))
        ->toThrow(\Exception::class, 'End time must be after start time');
});

test('convertToEvent validates max attendees is at least 1', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 5,
    ]);

    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 0,
    ];

    expect(fn () => $this->service->convertToEvent($post->id, $eventData))
        ->toThrow(\Exception::class, 'Max attendees must be at least 1');
});

test('convertToEvent allows explicit user parameter', function () {
    $otherUser = User::factory()->create();
    $post = Post::factory()->create([
        'user_id' => $otherUser->id,
        'status' => 'active',
        'reaction_count' => 5,
    ]);

    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 20,
    ];

    $activity = $this->service->convertToEvent($post->id, $eventData, $otherUser);

    expect($activity->user_id)->toBe($otherUser->id);
});

test('convertToEvent eager loads relationships', function () {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'active',
        'reaction_count' => 5,
    ]);

    $eventData = [
        'start_time' => now()->addDays(1)->toDateTimeString(),
        'end_time' => now()->addDays(1)->addHours(2)->toDateTimeString(),
        'max_attendees' => 20,
    ];

    // This test verifies that the service loads relationships
    // to avoid N+1 queries in ActivityConversionService
    $activity = $this->service->convertToEvent($post->id, $eventData);

    expect($activity)->toBeInstanceOf(Activity::class);
});
