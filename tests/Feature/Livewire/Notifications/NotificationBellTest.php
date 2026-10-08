<?php

use App\Livewire\Notifications\NotificationBell;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('renders without notifications', function () {
    $user = User::factory()->create();
    $component = Livewire::actingAs($user)
        ->test(NotificationBell::class);

    dump($component->html()); // Uncomment to see HTML

    $component->assertSee('Notifications'); // Header
    $component->assertSee('No new notifications');
});

/*
test('renders notifications with rich data', function () {
    // ...
});

test('handleNotificationClick marks as read and redirects', function () {
    // ...
});

test('handleNotificationClick works without explicit URL', function () {
    // ...
});
*/
test('markAllAsReadOnOpen clears unread count', function () {
    $user = User::factory()->create();

    Notification::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'type' => 'test_notification',
        'title' => 'Test Title',
        'message' => 'Test Message',
        'delivery_method' => 'in_app',
        'data' => ['message' => 'Hello'],
        'read_at' => null,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($user)
        ->test(NotificationBell::class);

    $component->assertSet('unreadCount', 1);

    $component->call('markAllAsReadOnOpen');

    $component->assertSet('unreadCount', 0);

    expect(Notification::where('user_id', $user->id)->whereNull('read_at')->count())->toBe(0);
});
