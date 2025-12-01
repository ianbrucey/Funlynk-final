<?php

use App\Models\Conversation;
use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use App\Services\GroupChatService;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disable event listeners to avoid transaction issues in tests
    Event::fake();

    $this->groupChatService = new GroupChatService;
    $this->groupService = new GroupService;
    $this->user = User::factory()->create(['email' => 'groupchatservice_user@example.com']);
    $this->group = Group::factory()->create();
    $this->groupService->addMember($this->group, $this->user, 'admin');
    $this->actingAs($this->user);
});

describe('GroupChatService', function () {
    it('can get or create a group chat', function () {
        $conversation = $this->groupChatService->getOrCreateGroupChat($this->group);

        expect($conversation)->toBeInstanceOf(Conversation::class);
        expect($conversation->type)->toBe('group');
        expect($conversation->group_id)->toBe($this->group->id);
        expect($conversation->participants()->count())->toBe(1); // Creator is added as participant

        // Calling again should return the same conversation
        $sameConversation = $this->groupChatService->getOrCreateGroupChat($this->group);
        expect($sameConversation->id)->toBe($conversation->id);
    });

    it('can send a group message', function () {
        $messageContent = 'Hello group!';
        $message = $this->groupChatService->sendGroupMessage($this->group, $this->user, $messageContent);

        expect($message)->toBeInstanceOf(Message::class);
        expect($message->body)->toBe($messageContent);
        expect($message->user_id)->toBe($this->user->id);
        $this->group->load('conversation');
        expect($message->conversation_id)->toBe($this->group->conversation->id);
    });

    it('cannot send a group message if user is not a member', function () {
        $nonMember = User::factory()->create(['email' => 'nonmember_chat@example.com']);
        $messageContent = 'Secret message';

        $this->groupChatService->sendGroupMessage($this->group, $nonMember, $messageContent);
    })->throws(\Exception::class, 'User is not a member of this group.');

    it('can get group chat messages', function () {
        $this->groupChatService->sendGroupMessage($this->group, $this->user, 'Message 1');
        $this->groupChatService->sendGroupMessage($this->group, $this->user, 'Message 2');

        $messages = $this->groupChatService->getGroupChatMessages($this->group);

        expect($messages->count())->toBe(2);
        expect($messages->first()->body)->toBe('Message 1');
        expect($messages->last()->body)->toBe('Message 2');
    });

    it('can add a participant to group chat', function () {
        $conversation = $this->groupChatService->getOrCreateGroupChat($this->group);
        $newUser = User::factory()->create(['email' => 'newuser_add_participant@example.com']);

        $this->groupChatService->addParticipantToGroupChat($this->group, $newUser);

        expect($conversation->participants()->where('user_id', $newUser->id)->exists())->toBeTrue();
    });

    it('can remove a participant from group chat', function () {
        $conversation = $this->groupChatService->getOrCreateGroupChat($this->group);
        $newUser = User::factory()->create(['email' => 'newuser_remove_participant@example.com']);
        $this->groupChatService->addParticipantToGroupChat($this->group, $newUser);

        $this->groupChatService->removeParticipantFromGroupChat($this->group, $newUser);

        expect($conversation->participants()->where('user_id', $newUser->id)->exists())->toBeFalse();
    });
});
