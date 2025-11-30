<?php

use App\Models\Conversation;
use App\Models\Notification;
use App\Models\User;
use App\Services\ChatService;
use App\Services\MessageRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('acceptRequest updates recipient pivot status to accepted', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    // Verify it starts as pending
    $recipientPivot = $conversation->participants()
        ->where('user_id', $recipient->id)
        ->first()
        ->pivot;
    expect($recipientPivot->request_status)->toBe('pending');

    // Accept the request
    $messageRequestService = app(MessageRequestService::class);
    $messageRequestService->acceptRequest($conversation, $recipient);

    // Verify status changed
    $conversation->refresh();
    $recipientPivot = $conversation->participants()
        ->where('user_id', $recipient->id)
        ->first()
        ->pivot;
    expect($recipientPivot->request_status)->toBe('accepted');
});

test('acceptRequest updates conversation metadata is_request to false', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    expect($conversation->metadata['is_request'])->toBeTrue();

    $messageRequestService = app(MessageRequestService::class);
    $messageRequestService->acceptRequest($conversation, $recipient);

    $conversation->refresh();
    expect($conversation->metadata['is_request'])->toBeFalse();
});

test('acceptRequest sends notification to sender', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create(['display_name' => 'Jane Doe']);

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    $messageRequestService = app(MessageRequestService::class);
    $messageRequestService->acceptRequest($conversation, $recipient);

    $notification = Notification::where('user_id', $sender->id)
        ->where('type', 'message_request_accepted')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toContain('Jane Doe')
        ->and($notification->data['conversation_id'])->toBe($conversation->id)
        ->and($notification->data['recipient_id'])->toBe($recipient->id);
});

test('declineRequest updates recipient pivot status to declined', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    $messageRequestService = app(MessageRequestService::class);
    $messageRequestService->declineRequest($conversation, $recipient);

    $conversation->refresh();
    $recipientPivot = $conversation->participants()
        ->where('user_id', $recipient->id)
        ->first()
        ->pivot;
    expect($recipientPivot->request_status)->toBe('declined');
});

test('declineRequest does not send notification to sender', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    $messageRequestService = app(MessageRequestService::class);
    $messageRequestService->declineRequest($conversation, $recipient);

    $notificationCount = Notification::where('user_id', $sender->id)->count();
    expect($notificationCount)->toBe(0);
});

test('getMessageRequests returns only pending requests for user', function () {
    $user = User::factory()->create();
    $sender1 = User::factory()->create();
    $sender2 = User::factory()->create();
    $sender3 = User::factory()->create();

    $chatService = app(ChatService::class);

    // Create 2 pending requests
    $conversation1 = $chatService->createDirectMessageConversation($sender1, $user);
    $conversation2 = $chatService->createDirectMessageConversation($sender2, $user);

    // Create 1 accepted request
    $conversation3 = $chatService->createDirectMessageConversation($sender3, $user);
    $messageRequestService = app(MessageRequestService::class);
    $messageRequestService->acceptRequest($conversation3, $user);

    // Get message requests
    $requests = $messageRequestService->getMessageRequests($user);

    expect($requests)->toHaveCount(2)
        ->and($requests->pluck('id')->toArray())->toContain($conversation1->id, $conversation2->id)
        ->and($requests->pluck('id')->toArray())->not->toContain($conversation3->id);
});

test('getMessageRequests orders by last_message_at desc', function () {
    $user = User::factory()->create();
    $sender1 = User::factory()->create();
    $sender2 = User::factory()->create();

    $chatService = app(ChatService::class);

    $conversation1 = $chatService->createDirectMessageConversation($sender1, $user);
    $conversation1->update(['last_message_at' => now()->subHours(2)]);

    $conversation2 = $chatService->createDirectMessageConversation($sender2, $user);
    $conversation2->update(['last_message_at' => now()->subHour()]);

    $messageRequestService = app(MessageRequestService::class);
    $requests = $messageRequestService->getMessageRequests($user);

    expect($requests->first()->id)->toBe($conversation2->id)
        ->and($requests->last()->id)->toBe($conversation1->id);
});

test('getDirectInbox returns accepted and null request_status conversations', function () {
    $user = User::factory()->create();
    $sender1 = User::factory()->create();
    $sender2 = User::factory()->create();
    $sender3 = User::factory()->create();

    // Make sender1 mutual follower (null request_status)
    $user->following()->attach($sender1->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $sender1->following()->attach($user->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    $chatService = app(ChatService::class);
    $messageRequestService = app(MessageRequestService::class);

    // Mutual follower conversation (null request_status)
    $conversation1 = $chatService->createDirectMessageConversation($sender1, $user);

    // Accepted request
    $conversation2 = $chatService->createDirectMessageConversation($sender2, $user);
    $messageRequestService->acceptRequest($conversation2, $user);

    // Pending request (should not appear)
    $conversation3 = $chatService->createDirectMessageConversation($sender3, $user);

    $inbox = $messageRequestService->getDirectInbox($user);

    expect($inbox)->toHaveCount(2)
        ->and($inbox->pluck('id')->toArray())->toContain($conversation1->id, $conversation2->id)
        ->and($inbox->pluck('id')->toArray())->not->toContain($conversation3->id);
});

test('getDirectInbox excludes declined requests', function () {
    $user = User::factory()->create();
    $sender1 = User::factory()->create();
    $sender2 = User::factory()->create();

    $chatService = app(ChatService::class);
    $messageRequestService = app(MessageRequestService::class);

    $conversation1 = $chatService->createDirectMessageConversation($sender1, $user);
    $messageRequestService->acceptRequest($conversation1, $user);

    $conversation2 = $chatService->createDirectMessageConversation($sender2, $user);
    $messageRequestService->declineRequest($conversation2, $user);

    $inbox = $messageRequestService->getDirectInbox($user);

    expect($inbox)->toHaveCount(1)
        ->and($inbox->first()->id)->toBe($conversation1->id);
});

test('getDirectInbox orders by last_message_at desc', function () {
    $user = User::factory()->create();
    $sender1 = User::factory()->create();
    $sender2 = User::factory()->create();

    $user->following()->attach($sender1->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $sender1->following()->attach($user->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $user->following()->attach($sender2->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $sender2->following()->attach($user->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    $chatService = app(ChatService::class);

    $conversation1 = $chatService->createDirectMessageConversation($sender1, $user);
    $conversation1->update(['last_message_at' => now()->subHours(2)]);

    $conversation2 = $chatService->createDirectMessageConversation($sender2, $user);
    $conversation2->update(['last_message_at' => now()->subHour()]);

    $messageRequestService = app(MessageRequestService::class);
    $inbox = $messageRequestService->getDirectInbox($user);

    expect($inbox->first()->id)->toBe($conversation2->id)
        ->and($inbox->last()->id)->toBe($conversation1->id);
});

test('getUnreadRequestCount returns accurate count', function () {
    $user = User::factory()->create();
    $sender1 = User::factory()->create();
    $sender2 = User::factory()->create();
    $sender3 = User::factory()->create();

    $chatService = app(ChatService::class);

    // Create 2 unread pending requests
    $chatService->createDirectMessageConversation($sender1, $user);
    $chatService->createDirectMessageConversation($sender2, $user);

    // Create 1 read pending request
    $conversation3 = $chatService->createDirectMessageConversation($sender3, $user);
    $conversation3->participants()
        ->updateExistingPivot($user->id, ['last_read_at' => now()]);

    $messageRequestService = app(MessageRequestService::class);
    $count = $messageRequestService->getUnreadRequestCount($user);

    expect($count)->toBe(2);
});

test('getUnreadRequestCount excludes accepted requests', function () {
    $user = User::factory()->create();
    $sender1 = User::factory()->create();
    $sender2 = User::factory()->create();

    $chatService = app(ChatService::class);
    $messageRequestService = app(MessageRequestService::class);

    // Pending unread request
    $chatService->createDirectMessageConversation($sender1, $user);

    // Accepted unread request (should not count)
    $conversation2 = $chatService->createDirectMessageConversation($sender2, $user);
    $messageRequestService->acceptRequest($conversation2, $user);

    $count = $messageRequestService->getUnreadRequestCount($user);

    expect($count)->toBe(1);
});
