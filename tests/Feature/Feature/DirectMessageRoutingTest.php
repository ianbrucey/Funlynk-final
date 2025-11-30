<?php

use App\Models\Conversation;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('mutual followers create DM with is_request false', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    // Make them mutual followers
    $sender->following()->attach($recipient->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $recipient->following()->attach($sender->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    expect($conversation->type)->toBe('private')
        ->and($conversation->conversationable_type)->toBeNull()
        ->and($conversation->conversationable_id)->toBeNull()
        ->and($conversation->metadata['is_dm'])->toBeTrue()
        ->and($conversation->metadata['is_request'])->toBeFalse()
        ->and($conversation->metadata['recipient_id'])->toBe($recipient->id);
});

test('non-mutual followers create DM with is_request true', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    // Sender follows recipient, but not mutual
    $sender->following()->attach($recipient->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    expect($conversation->type)->toBe('private')
        ->and($conversation->metadata['is_dm'])->toBeTrue()
        ->and($conversation->metadata['is_request'])->toBeTrue()
        ->and($conversation->metadata['recipient_id'])->toBe($recipient->id);
});

test('strangers create DM with is_request true', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    // No follow relationship at all

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    expect($conversation->type)->toBe('private')
        ->and($conversation->metadata['is_request'])->toBeTrue();
});

test('existing conversation is reused not duplicated', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);

    // Create first conversation
    $conversation1 = $chatService->createDirectMessageConversation($sender, $recipient);

    // Try to create again
    $conversation2 = $chatService->createDirectMessageConversation($sender, $recipient);

    expect($conversation1->id)->toBe($conversation2->id);

    // Verify only one conversation exists
    $count = Conversation::where('type', 'private')
        ->whereNull('conversationable_type')
        ->count();

    expect($count)->toBe(1);
});

test('existing conversation is found regardless of sender/recipient order', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $chatService = app(ChatService::class);

    // Create conversation with A as sender
    $conversation1 = $chatService->createDirectMessageConversation($userA, $userB);

    // Try to create with B as sender (reversed)
    $conversation2 = $chatService->createDirectMessageConversation($userB, $userA);

    expect($conversation1->id)->toBe($conversation2->id);
});

test('both users are participants in DM conversation', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    $participants = $conversation->participants;

    expect($participants)->toHaveCount(2)
        ->and($participants->pluck('id')->toArray())->toContain($sender->id, $recipient->id);
});

test('recipient has pending request_status when not mutual followers', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    $recipientPivot = $conversation->participants()
        ->where('user_id', $recipient->id)
        ->first()
        ->pivot;

    expect($recipientPivot->request_status)->toBe('pending');
});

test('recipient has null request_status when mutual followers', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    // Make them mutual followers
    $sender->following()->attach($recipient->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $recipient->following()->attach($sender->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    $recipientPivot = $conversation->participants()
        ->where('user_id', $recipient->id)
        ->first()
        ->pivot;

    expect($recipientPivot->request_status)->toBeNull();
});

test('sender always has null request_status', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createDirectMessageConversation($sender, $recipient);

    $senderPivot = $conversation->participants()
        ->where('user_id', $sender->id)
        ->first()
        ->pivot;

    expect($senderPivot->request_status)->toBeNull();
});

test('isMutualFollower returns true for mutual followers', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->following()->attach($userB->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $userB->following()->attach($userA->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    $chatService = app(ChatService::class);

    expect($chatService->isMutualFollower($userA, $userB))->toBeTrue()
        ->and($chatService->isMutualFollower($userB, $userA))->toBeTrue();
});

test('isMutualFollower returns false for one-way follow', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->following()->attach($userB->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    $chatService = app(ChatService::class);

    expect($chatService->isMutualFollower($userA, $userB))->toBeFalse();
});

test('isMutualFollower returns false for strangers', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $chatService = app(ChatService::class);

    expect($chatService->isMutualFollower($userA, $userB))->toBeFalse();
});

test('shouldRouteToRequests returns false for mutual followers', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $sender->following()->attach($recipient->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);
    $recipient->following()->attach($sender->id, ['id' => \Illuminate\Support\Str::uuid()->toString()]);

    $chatService = app(ChatService::class);

    expect($chatService->shouldRouteToRequests($sender, $recipient))->toBeFalse();
});

test('shouldRouteToRequests returns true for non-mutual followers', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $chatService = app(ChatService::class);

    expect($chatService->shouldRouteToRequests($sender, $recipient))->toBeTrue();
});
