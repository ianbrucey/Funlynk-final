<?php

use App\Jobs\NotifyAttendeesOfChanges;
use App\Models\Activity;
use App\Models\ActivityEditLog;
use App\Models\ActivityRefundWindow;
use App\Models\Rsvp;
use App\Models\User;
use App\Services\ActivityEditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->service = app(ActivityEditService::class);
    $this->host = User::factory()->create();
    $this->activity = Activity::factory()->create([
        'host_id' => $this->host->id,
        'title' => 'Original Title',
        'description' => 'Original description',
        'start_time' => Carbon::now()->addDays(7),
        'end_time' => Carbon::now()->addDays(7)->addHours(2),
        'location_name' => 'Original Location',
        'price_cents' => 5000,
        'is_paid' => true,
    ]);
});

test('allows cosmetic changes without restrictions', function () {
    $result = $this->service->processEdit($this->activity, [
        'description' => 'Updated description',
    ], $this->host);

    expect($result['success'])->toBeTrue();
    expect($result['blocked_changes'])->toBeEmpty();
    expect($result['significant_changes'])->toBeEmpty();
    expect($result['window'])->toBeNull();
});

test('logs all changes', function () {
    $this->service->processEdit($this->activity, [
        'title' => 'New Title',
        'description' => 'New description',
    ], $this->host);

    expect(ActivityEditLog::where('activity_id', $this->activity->id)->count())->toBe(2);
});

test('blocks price increase when activity has paid attendees', function () {
    // Lock the activity (simulate first paid RSVP)
    $this->activity->lockEditing();

    $result = $this->service->processEdit($this->activity, [
        'price_cents' => 7500,
    ], $this->host);

    expect($result['success'])->toBeFalse();
    expect($result['blocked_changes'])->toHaveCount(1);
    expect($result['blocked_changes'][0]['field'])->toBe('price_cents');
});

test('allows price increase when activity has no paid attendees', function () {
    // Activity is not locked
    $result = $this->service->processEdit($this->activity, [
        'price_cents' => 7500,
    ], $this->host);

    expect($result['success'])->toBeTrue();
    expect($result['blocked_changes'])->toBeEmpty();
});

test('creates refund window for significant changes when locked', function () {
    // Lock the activity
    $this->activity->lockEditing();

    // Create a paid RSVP
    $attendee = User::factory()->create();
    Rsvp::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => $attendee->id,
        'is_paid' => true,
        'status' => 'attending',
    ]);

    $result = $this->service->processEdit($this->activity, [
        'title' => 'Completely Different Event',
    ], $this->host);

    expect($result['success'])->toBeTrue();
    expect($result['significant_changes'])->toHaveCount(1);
    expect($result['window'])->not->toBeNull();
    expect($result['window'])->toBeInstanceOf(ActivityRefundWindow::class);
});

test('does not create refund window when not locked', function () {
    $result = $this->service->processEdit($this->activity, [
        'title' => 'Completely Different Event',
    ], $this->host);

    expect($result['success'])->toBeTrue();
    expect($result['window'])->toBeNull();
});

test('returns no changes message when nothing changed', function () {
    $result = $this->service->processEdit($this->activity, [
        'title' => 'Original Title',
    ], $this->host);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('No changes detected.');
});

test('records paid attendee count in edit log', function () {
    $this->activity->lockEditing();

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

    $this->service->processEdit($this->activity, [
        'description' => 'Updated description',
    ], $this->host);

    $log = ActivityEditLog::where('activity_id', $this->activity->id)->first();
    expect($log->paid_attendee_count)->toBe(3);
});

