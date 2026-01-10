<?php

use App\Models\Activity;
use App\Models\EventInterest;
use App\Models\GuestBookmark;
use App\Models\User;
use App\Services\GuestEngagementService;

beforeEach(function () {
    $this->service = app(GuestEngagementService::class);
    $this->user = User::factory()->create();
    $this->activity = Activity::factory()->create([
        'host_id' => $this->user->id,
    ]);
});

test('can record guest interest in event', function () {
    $email = 'guest@example.com';
    $metadata = [
        'source' => 'instagram',
        'utm_campaign' => 'summer_events',
        'utm_source' => 'instagram',
        'utm_medium' => 'social',
    ];

    $interest = $this->service->recordInterest($this->activity, $email, $metadata);

    expect($interest)->toBeInstanceOf(EventInterest::class)
        ->and($interest->activity_id)->toBe($this->activity->id)
        ->and($interest->email)->toBe($email)
        ->and($interest->source)->toBe('instagram')
        ->and($interest->utm_campaign)->toBe('summer_events');
});

test('can create guest bookmark', function () {
    $result = $this->service->createBookmark($this->activity, null, 'instagram');

    expect($result)->toHaveKeys(['bookmark', 'token'])
        ->and($result['bookmark'])->toBeInstanceOf(GuestBookmark::class)
        ->and($result['bookmark']->activity_id)->toBe($this->activity->id)
        ->and($result['token'])->toBeString()
        ->and(strlen($result['token']))->toBe(64);
});

test('can retrieve guest bookmarks by token', function () {
    $result = $this->service->createBookmark($this->activity, null, 'instagram');
    $token = $result['token'];

    $bookmarks = $this->service->getGuestBookmarks($token);

    expect($bookmarks)->toHaveCount(1)
        ->and($bookmarks->first()->activity_id)->toBe($this->activity->id);
});

test('can migrate guest data to user account', function () {
    $email = 'newuser@example.com';
    $guestToken = 'test-guest-token-123';

    // Create guest interest
    EventInterest::create([
        'activity_id' => $this->activity->id,
        'email' => $email,
        'source' => 'instagram',
    ]);

    // Create guest bookmark
    GuestBookmark::create([
        'activity_id' => $this->activity->id,
        'guest_token' => $guestToken,
        'source' => 'instagram',
    ]);

    $newUser = User::factory()->create(['email' => $email]);

    $result = $this->service->migrateGuestData($newUser, $email, $guestToken);

    expect($result['interests'])->toBe(1)
        ->and($result['bookmarks'])->toBe(1);

    $interest = EventInterest::where('email', $email)->first();
    $bookmark = GuestBookmark::where('guest_token', $guestToken)->first();

    expect($interest->user_id)->toBe($newUser->id)
        ->and($bookmark->user_id)->toBe($newUser->id);
});

test('can check if email has expressed interest', function () {
    $email = 'interested@example.com';

    expect($this->service->hasExpressedInterest($this->activity, $email))->toBeFalse();

    $this->service->recordInterest($this->activity, $email);

    expect($this->service->hasExpressedInterest($this->activity, $email))->toBeTrue();
});
