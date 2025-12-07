<?php

use App\Models\Activity;
use App\Models\Rsvp;
use App\Models\User;
use App\Services\CheckInService;

beforeEach(function () {
    $this->checkInService = app(CheckInService::class);
    $this->host = User::factory()->create();
    $this->attendee = User::factory()->create();
    $this->activity = Activity::factory()->create([
        'host_id' => $this->host->id,
        'status' => 'published',
    ]);
});

describe('generateCheckInCredentials', function () {
    test('generates unique check-in code and QR token', function () {
        $rsvp = Rsvp::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->attendee->id,
            'status' => 'attending',
        ]);

        expect($rsvp->check_in_code)->toBeNull();
        expect($rsvp->qr_token)->toBeNull();

        $this->checkInService->generateCheckInCredentials($rsvp);
        $rsvp->refresh();

        expect($rsvp->check_in_code)->toBeString()->toHaveLength(6);
        expect($rsvp->qr_token)->toBeString()->toHaveLength(36); // UUID length
    });

    test('generates unique codes within an activity', function () {
        $rsvps = Rsvp::factory()->count(5)->create([
            'activity_id' => $this->activity->id,
            'status' => 'attending',
        ]);

        foreach ($rsvps as $rsvp) {
            $this->checkInService->generateCheckInCredentials($rsvp);
        }

        $codes = $rsvps->fresh()->pluck('check_in_code')->toArray();
        expect(array_unique($codes))->toHaveCount(5);
    });
});

describe('validateQrToken', function () {
    test('returns RSVP when QR token is valid', function () {
        $validToken = (string) \Illuminate\Support\Str::uuid();
        $rsvp = Rsvp::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->attendee->id,
            'qr_token' => $validToken,
        ]);

        $result = $this->checkInService->validateQrToken($this->activity->id, $validToken);

        expect($result)->not->toBeNull();
        expect($result->id)->toBe($rsvp->id);
        expect($result->user)->not->toBeNull();
    });

    test('returns null when QR token is invalid', function () {
        $invalidToken = (string) \Illuminate\Support\Str::uuid();
        $result = $this->checkInService->validateQrToken($this->activity->id, $invalidToken);

        expect($result)->toBeNull();
    });

    test('returns null when QR token belongs to different activity', function () {
        $otherToken = (string) \Illuminate\Support\Str::uuid();
        $otherActivity = Activity::factory()->create(['host_id' => $this->host->id]);
        Rsvp::factory()->create([
            'activity_id' => $otherActivity->id,
            'qr_token' => $otherToken,
        ]);

        $result = $this->checkInService->validateQrToken($this->activity->id, $otherToken);

        expect($result)->toBeNull();
    });
});

describe('validateCheckInCode', function () {
    test('returns RSVP when code is valid (case-insensitive)', function () {
        $rsvp = Rsvp::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->attendee->id,
            'check_in_code' => 'ABC123',
        ]);

        $result = $this->checkInService->validateCheckInCode($this->activity->id, 'abc123');

        expect($result)->not->toBeNull();
        expect($result->id)->toBe($rsvp->id);
    });

    test('returns null when code is invalid', function () {
        $result = $this->checkInService->validateCheckInCode($this->activity->id, 'INVALID');

        expect($result)->toBeNull();
    });
});

describe('performCheckIn', function () {
    test('successfully checks in an attendee', function () {
        $rsvp = Rsvp::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->attendee->id,
            'status' => 'attending',
            'checked_in_at' => null,
        ]);

        $result = $this->checkInService->performCheckIn($rsvp, 'qr_scan', $this->host);

        expect($result->checked_in_at)->not->toBeNull();
        expect($result->check_in_method)->toBe('qr_scan');
        expect($result->attended)->toBeTrue();
        expect($result->checked_in_by)->toBe($this->host->id);
    });

    test('throws exception when already checked in', function () {
        $rsvp = Rsvp::factory()->create([
            'activity_id' => $this->activity->id,
            'checked_in_at' => now(),
        ]);

        expect(fn () => $this->checkInService->performCheckIn($rsvp, 'qr_scan'))
            ->toThrow(Exception::class, 'already checked in');
    });
});

describe('getActivityCheckInStats', function () {
    test('returns accurate statistics', function () {
        // Create 5 RSVPs, 2 checked in
        Rsvp::factory()->count(3)->create([
            'activity_id' => $this->activity->id,
            'status' => 'attending',
            'attended' => false,
            'checked_in_at' => null,
        ]);
        Rsvp::factory()->count(2)->create([
            'activity_id' => $this->activity->id,
            'status' => 'attending',
            'attended' => true,
            'checked_in_at' => now(),
        ]);

        $stats = $this->checkInService->getActivityCheckInStats($this->activity);

        expect($stats['total_rsvps'])->toBe(5);
        expect($stats['checked_in_count'])->toBe(2);
        expect($stats['pending_count'])->toBe(3);
        expect($stats['check_in_percentage'])->toBe(40.0);
    });
});

