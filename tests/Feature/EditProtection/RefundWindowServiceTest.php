<?php

use App\Models\Activity;
use App\Models\ActivityEditLog;
use App\Models\ActivityRefundWindow;
use App\Models\Rsvp;
use App\Models\RsvpChangeResponse;
use App\Models\User;
use App\Services\RefundWindowService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = app(RefundWindowService::class);
    $this->host = User::factory()->create();
    $this->activity = Activity::factory()->create([
        'host_id' => $this->host->id,
        'is_paid' => true,
        'price_cents' => 5000,
    ]);
    $this->activity->lockEditing();
});

test('creates refund window with correct expiration', function () {
    $log = ActivityEditLog::factory()->create([
        'activity_id' => $this->activity->id,
        'editor_id' => $this->host->id,
    ]);

    $window = $this->service->create($this->activity, $log, [
        ['field' => 'title', 'old_display' => 'Old', 'new_display' => 'New'],
    ]);

    expect($window)->toBeInstanceOf(ActivityRefundWindow::class);
    expect($window->status)->toBe(ActivityRefundWindow::STATUS_ACTIVE);
    // expires_at should be ~72 hours in the future
    expect(now()->diffInHours($window->expires_at))->toBeGreaterThanOrEqual(71);
    expect(now()->diffInHours($window->expires_at))->toBeLessThanOrEqual(72);
});

test('creates pending responses for all paid RSVPs', function () {
    // Create paid RSVPs
    $attendees = User::factory()->count(3)->create();
    foreach ($attendees as $attendee) {
        Rsvp::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $attendee->id,
            'is_paid' => true,
            'status' => 'attending',
        ]);
    }

    $log = ActivityEditLog::factory()->create([
        'activity_id' => $this->activity->id,
        'editor_id' => $this->host->id,
    ]);

    $window = $this->service->create($this->activity, $log, []);

    expect(RsvpChangeResponse::where('refund_window_id', $window->id)->count())->toBe(3);
});

test('cancels existing active window when creating new one', function () {
    $log1 = ActivityEditLog::factory()->create([
        'activity_id' => $this->activity->id,
        'editor_id' => $this->host->id,
    ]);

    $window1 = $this->service->create($this->activity, $log1, []);

    $log2 = ActivityEditLog::factory()->create([
        'activity_id' => $this->activity->id,
        'editor_id' => $this->host->id,
    ]);

    $window2 = $this->service->create($this->activity, $log2, []);

    expect($window1->fresh()->status)->toBe(ActivityRefundWindow::STATUS_CANCELLED);
    expect($window2->status)->toBe(ActivityRefundWindow::STATUS_ACTIVE);
});

test('processes accept response correctly', function () {
    $attendee = User::factory()->create();
    $rsvp = Rsvp::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => $attendee->id,
        'is_paid' => true,
        'status' => 'attending',
    ]);

    $log = ActivityEditLog::factory()->create([
        'activity_id' => $this->activity->id,
        'editor_id' => $this->host->id,
    ]);

    $window = $this->service->create($this->activity, $log, []);

    $response = $this->service->processResponse(
        $rsvp,
        $window,
        RsvpChangeResponse::RESPONSE_ACCEPTED
    );

    expect($response->response)->toBe(RsvpChangeResponse::RESPONSE_ACCEPTED);
    expect($response->responded_at)->not->toBeNull();
});

test('expires windows correctly', function () {
    $log = ActivityEditLog::factory()->create([
        'activity_id' => $this->activity->id,
        'editor_id' => $this->host->id,
    ]);

    $window = $this->service->create($this->activity, $log, []);

    // Manually set expiration to past
    $window->update(['expires_at' => now()->subHour()]);

    $expiredCount = $this->service->expireWindows();

    expect($expiredCount)->toBe(1);
    expect($window->fresh()->status)->toBe(ActivityRefundWindow::STATUS_EXPIRED);
});

test('throws exception when responding to expired window', function () {
    $attendee = User::factory()->create();
    $rsvp = Rsvp::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => $attendee->id,
        'is_paid' => true,
        'status' => 'attending',
    ]);

    $log = ActivityEditLog::factory()->create([
        'activity_id' => $this->activity->id,
        'editor_id' => $this->host->id,
    ]);

    $window = $this->service->create($this->activity, $log, []);
    $window->update(['status' => ActivityRefundWindow::STATUS_EXPIRED]);

    $this->service->processResponse($rsvp, $window, RsvpChangeResponse::RESPONSE_ACCEPTED);
})->throws(Exception::class, 'Refund window has expired.');

