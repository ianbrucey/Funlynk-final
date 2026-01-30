<?php

use App\Models\Activity;
use App\Models\User;
use App\Services\ChangeDetectorService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new ChangeDetectorService();
    $this->host = User::factory()->create();
    $this->activity = Activity::factory()->create([
        'host_id' => $this->host->id,
        'title' => 'Original Title',
        'description' => 'Original description',
        'start_time' => Carbon::now()->addDays(7),
        'end_time' => Carbon::now()->addDays(7)->addHours(2),
        'location_name' => 'Original Location',
        'price_cents' => 5000,
        'payment_type' => 'online',
    ]);
});

test('detects no changes when values are the same', function () {
    $changes = $this->service->detectChanges($this->activity, [
        'title' => 'Original Title',
        'description' => 'Original description',
    ]);

    expect($changes)->toBeEmpty();
});

test('classifies description change as cosmetic', function () {
    $changes = $this->service->detectChanges($this->activity, [
        'description' => 'Updated description with more details',
    ]);

    expect($changes)->toHaveCount(1);
    expect($changes[0]['field'])->toBe('description');
    expect($changes[0]['category'])->toBe('cosmetic');
});

test('classifies minor title change as minor', function () {
    $changes = $this->service->detectChanges($this->activity, [
        'title' => 'Original Title!', // Just added punctuation - minor change
    ]);

    expect($changes)->toHaveCount(1);
    expect($changes[0]['category'])->toBe('minor');
});

test('classifies major title change as significant', function () {
    $changes = $this->service->detectChanges($this->activity, [
        'title' => 'Completely Different Event Name',
    ]);

    expect($changes)->toHaveCount(1);
    expect($changes[0]['field'])->toBe('title');
    expect($changes[0]['category'])->toBe('significant');
});

test('classifies small time change as minor', function () {
    $changes = $this->service->detectChanges($this->activity, [
        'start_time' => $this->activity->start_time->addMinutes(30),
    ]);

    expect($changes)->toHaveCount(1);
    expect($changes[0]['field'])->toBe('start_time');
    expect($changes[0]['category'])->toBe('minor');
});

test('classifies large time change as significant', function () {
    $changes = $this->service->detectChanges($this->activity, [
        'start_time' => $this->activity->start_time->addHours(5),
    ]);

    expect($changes)->toHaveCount(1);
    expect($changes[0]['field'])->toBe('start_time');
    expect($changes[0]['category'])->toBe('significant');
});

test('classifies location name change as significant', function () {
    $changes = $this->service->detectChanges($this->activity, [
        'location_name' => 'Completely Different Venue',
    ]);

    expect($changes)->toHaveCount(1);
    expect($changes[0]['field'])->toBe('location_name');
    expect($changes[0]['category'])->toBe('significant');
});

test('classifies price increase as blocked', function () {
    $changes = $this->service->detectChanges($this->activity, [
        'price_cents' => 7500, // Increase from 5000
    ]);

    expect($changes)->toHaveCount(1);
    expect($changes[0]['field'])->toBe('price_cents');
    expect($changes[0]['category'])->toBe('blocked');
});

test('classifies price decrease as minor', function () {
    $changes = $this->service->detectChanges($this->activity, [
        'price_cents' => 3000, // Decrease from 5000
    ]);

    expect($changes)->toHaveCount(1);
    expect($changes[0]['field'])->toBe('price_cents');
    expect($changes[0]['category'])->toBe('minor');
});

test('classifies paid to free as blocked', function () {
    $changes = $this->service->detectChanges($this->activity, [
        'payment_type' => 'free',
    ]);

    expect($changes)->toHaveCount(1);
    expect($changes[0]['field'])->toBe('payment_type');
    expect($changes[0]['category'])->toBe('blocked');
});

test('getBlockedChanges filters correctly', function () {
    $changes = [
        ['field' => 'title', 'category' => 'significant'],
        ['field' => 'price_cents', 'category' => 'blocked'],
        ['field' => 'description', 'category' => 'cosmetic'],
    ];

    $blocked = $this->service->getBlockedChanges($changes);

    expect($blocked)->toHaveCount(1);
    expect(array_values($blocked)[0]['field'])->toBe('price_cents');
});

test('getSignificantChanges filters correctly', function () {
    $changes = [
        ['field' => 'title', 'category' => 'significant'],
        ['field' => 'price_cents', 'category' => 'blocked'],
        ['field' => 'description', 'category' => 'cosmetic'],
    ];

    $significant = $this->service->getSignificantChanges($changes);

    expect($significant)->toHaveCount(1);
    expect($significant[0]['field'])->toBe('title');
});

