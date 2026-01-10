<?php

use App\Models\Activity;
use App\Models\User;
use Livewire\Livewire;

test('public event page is accessible without authentication', function () {
    $host = User::factory()->create();
    $activity = Activity::factory()->create([
        'host_id' => $host->id,
        'slug' => 'test-event',
    ]);

    $response = $this->get(route('events.public', $activity->slug));

    $response->assertStatus(200);
});

test('public event page displays activity details', function () {
    $host = User::factory()->create();
    $activity = Activity::factory()->create([
        'host_id' => $host->id,
        'title' => 'Test Event',
        'description' => 'This is a test event',
    ]);

    Livewire::test(\App\Livewire\PublicEventView::class, ['activity' => $activity])
        ->assertSee('Test Event')
        ->assertSee('This is a test event');
});

test('guest can express interest in event', function () {
    $host = User::factory()->create();
    $activity = Activity::factory()->create([
        'host_id' => $host->id,
    ]);

    Livewire::test(\App\Livewire\PublicEventView::class, ['activity' => $activity])
        ->set('email', 'guest@example.com')
        ->call('expressInterest')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('event_interests', [
        'activity_id' => $activity->id,
        'email' => 'guest@example.com',
    ]);
});

test('rsvp button redirects to login', function () {
    $host = User::factory()->create();
    $activity = Activity::factory()->create([
        'host_id' => $host->id,
    ]);

    Livewire::test(\App\Livewire\PublicEventView::class, ['activity' => $activity])
        ->call('rsvp')
        ->assertRedirect(route('login'));
});
