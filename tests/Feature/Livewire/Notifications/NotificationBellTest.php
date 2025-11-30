<?php

use App\Livewire\Notifications\NotificationBell;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
