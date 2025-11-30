<?php

use App\Events\DirectMessageReceived;
use App\Events\MessageRequestReceived;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('DirectMessageReceived event creates in-app notification', function () {
    Event::fake([DirectMessageReceived::class]);

    $sender = User::factory()->create(['display_name' => 'John Doe']);
    $recipient = User::factory()->create();

    // Create mutual followers
    $sender->following()->attach($recipient->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $recipient->following()->attach($sender->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    // Create conversation
    $conversation = Conversation::create([
        'type' => 'private',
        'conversationable_type' => null,
        'conversationable_id' => null,
        'last_message_at' => now(),
        'metadata' => ['is_dm' => true, 'is_request' => false],
    ]);

    $conversation->participants()->attach($sender->id, [
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'role' => 'member',
        'is_muted' => false,
        'last_read_at' => now(),
    ]);

    $conversation->participants()->attach($recipient->id, [
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'role' => 'member',
        'is_muted' => false,
        'last_read_at' => null,
    ]);

    // Create message
    $message = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $sender->id,
        'body' => 'Hello there!',
    ]);

    // Dispatch event
    $event = new DirectMessageReceived($message, $conversation, $sender, $recipient);
    $listener = new \App\Listeners\SendDirectMessageNotification;
    $listener->handle($event);

    // Assert notification was created
    $notification = Notification::where('user_id', $recipient->id)
        ->where('type', 'direct_message')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toContain('John Doe')
        ->and($notification->message)->toBe('Hello there!')
        ->and($notification->data['conversation_id'])->toBe($conversation->id)
        ->and($notification->data['sender_id'])->toBe($sender->id);
});

test('DirectMessageReceived respects notification preferences', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create([
        'notification_preferences' => ['direct_messages' => false],
    ]);

    $conversation = Conversation::create([
        'type' => 'private',
        'conversationable_type' => null,
        'conversationable_id' => null,
        'last_message_at' => now(),
        'metadata' => ['is_dm' => true],
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $sender->id,
        'body' => 'Hello!',
    ]);

    $event = new DirectMessageReceived($message, $conversation, $sender, $recipient);
    $listener = new \App\Listeners\SendDirectMessageNotification;
    $listener->handle($event);

    // Assert no notification was created
    $notificationCount = Notification::where('user_id', $recipient->id)->count();
    expect($notificationCount)->toBe(0);
});

test('MessageRequestReceived event creates in-app notification', function () {
    Event::fake([MessageRequestReceived::class]);

    $sender = User::factory()->create(['display_name' => 'Jane Smith']);
    $recipient = User::factory()->create();

    // Create conversation with request
    $conversation = Conversation::create([
        'type' => 'private',
        'conversationable_type' => null,
        'conversationable_id' => null,
        'last_message_at' => now(),
        'metadata' => ['is_dm' => true, 'is_request' => true],
    ]);

    $conversation->participants()->attach($sender->id, [
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'role' => 'member',
        'is_muted' => false,
        'last_read_at' => now(),
    ]);

    $conversation->participants()->attach($recipient->id, [
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'role' => 'member',
        'is_muted' => false,
        'last_read_at' => null,
        'request_status' => 'pending',
    ]);

    // Create message
    $message = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $sender->id,
        'body' => 'Hey, want to connect?',
    ]);

    // Dispatch event
    $event = new MessageRequestReceived($message, $conversation, $sender, $recipient);
    $listener = new \App\Listeners\SendMessageRequestNotification;
    $listener->handle($event);

    // Assert notification was created
    $notification = Notification::where('user_id', $recipient->id)
        ->where('type', 'message_request')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toContain('Jane Smith')
        ->and($notification->message)->toBe('Hey, want to connect?')
        ->and($notification->data['conversation_id'])->toBe($conversation->id)
        ->and($notification->data['sender_id'])->toBe($sender->id);
});
