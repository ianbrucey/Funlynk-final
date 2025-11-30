<?php

use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('view allows participants to view conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Make them mutual followers
    $user1->following()->attach($user2->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $user2->following()->attach($user1->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($user1, $user2);

    expect($user1->can('view', $conversation))->toBeTrue()
        ->and($user2->can('view', $conversation))->toBeTrue();
});

test('view denies non-participants', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $user1->following()->attach($user2->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $user2->following()->attach($user1->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($user1, $user2);

    expect($user3->can('view', $conversation))->toBeFalse();
});

test('view denies declined requests', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    // Decline the request
    $messageRequestService = app(\App\Services\MessageRequestService::class);
    $messageRequestService->declineRequest($conversation, $recipient);

    expect($recipient->can('view', $conversation))->toBeFalse();
});

test('sendMessage allows participants in accepted conversations', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1->following()->attach($user2->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $user2->following()->attach($user1->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($user1, $user2);

    expect($user1->can('sendMessage', $conversation))->toBeTrue()
        ->and($user2->can('sendMessage', $conversation))->toBeTrue();
});

test('sendMessage allows sender in pending requests', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    expect($sender->can('sendMessage', $conversation))->toBeTrue();
});

test('sendMessage denies recipient in pending requests', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    expect($recipient->can('sendMessage', $conversation))->toBeFalse();
});

test('sendMessage denies non-participants', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $user1->following()->attach($user2->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $user2->following()->attach($user1->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($user1, $user2);

    expect($user3->can('sendMessage', $conversation))->toBeFalse();
});

test('acceptRequest allows recipient with pending status', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    expect($recipient->can('acceptRequest', $conversation))->toBeTrue();
});

test('acceptRequest denies sender', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    expect($sender->can('acceptRequest', $conversation))->toBeFalse();
});

test('acceptRequest denies already accepted requests', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    $messageRequestService = app(\App\Services\MessageRequestService::class);
    $messageRequestService->acceptRequest($conversation, $recipient);

    expect($recipient->can('acceptRequest', $conversation))->toBeFalse();
});

test('declineRequest allows recipient with pending status', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    expect($recipient->can('declineRequest', $conversation))->toBeTrue();
});

test('declineRequest denies sender', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    expect($sender->can('declineRequest', $conversation))->toBeFalse();
});
