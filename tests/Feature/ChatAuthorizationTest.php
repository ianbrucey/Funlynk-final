<?php

use App\Events\MessageSent;
use App\Livewire\Chat\ChatComponent;
use App\Models\Activity;
use App\Models\Group;
use App\Models\Message;
use App\Models\Post;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

function chatUser(): User
{
    return User::factory()->create(['onboarding_completed_at' => now()]);
}

function dmBetween(User $a, User $b, string $body = 'SECRET DM'): App\Models\Conversation
{
    $dm = app(ChatService::class)->createDirectMessageConversation($a, $b);
    app(ChatService::class)->sendMessage($dm, $a, $body);

    return $dm;
}

test('a stranger cannot read a direct message conversation', function () {
    $dm = dmBetween(chatUser(), chatUser());

    Livewire::actingAs(chatUser())->test(ChatComponent::class, ['conversationId' => $dm->id])->assertForbidden();
});

test('a participant can still read and send in a direct message', function () {
    [$alice, $bob] = [chatUser(), chatUser()];
    $dm = dmBetween($alice, $bob);

    Livewire::actingAs($alice)->test(ChatComponent::class, ['conversationId' => $dm->id])
        ->set('newMessage', 'hello again')
        ->call('sendMessage')
        ->assertHasNoErrors();
    expect(Message::where('conversation_id', $dm->id)->where('body', 'hello again')->exists())->toBeTrue();

    $recipient = Livewire::actingAs($bob)->test(ChatComponent::class, ['conversationId' => $dm->id])->assertSee('SECRET DM');
    $recipient->set('newMessage', 'not yet accepted')->call('sendMessage')->assertForbidden();
    expect(Message::where('body', 'not yet accepted')->exists())->toBeFalse();
});

test('the conversation id cannot be changed from the browser', function () {
    $dm = dmBetween(chatUser(), chatUser());
    $mallory = chatUser();
    $own = Post::factory()->create(['user_id' => $mallory->id]);

    expect(fn () => Livewire::actingAs($mallory)->test(ChatComponent::class, ['conversationable' => $own])
        ->set('conversationId', $dm->id))->toThrow(Exception::class, 'Cannot update locked property');
    expect(Message::where('conversation_id', $dm->id)->count())->toBe(1);
});

test('private group post chat and page are closed to non members', function () {
    $owner = chatUser();
    $member = chatUser();
    $outsider = chatUser();
    $group = Group::factory()->create(['privacy' => 'private', 'created_by' => $owner->id]);
    $group->memberships()->create(['user_id' => $member->id, 'role' => 'member']);
    $post = Post::factory()->create(['user_id' => $owner->id, 'group_id' => $group->id]);

    $this->actingAs($outsider)->get(route('posts.chat', $post))->assertForbidden();
    $this->actingAs($outsider)->get(route('posts.show', $post))->assertForbidden();
    $this->actingAs($member)->get(route('posts.chat', $post))->assertOk();
    $this->actingAs($member)->get(route('posts.show', $post))->assertOk();
});

test('only group members can send in a group post chat, but plain post chat stays open', function () {
    $owner = chatUser();
    $member = chatUser();
    $outsider = chatUser();
    $group = Group::factory()->create(['privacy' => 'public', 'created_by' => $owner->id]);
    $group->memberships()->create(['user_id' => $member->id, 'role' => 'member']);
    $groupPost = Post::factory()->create(['user_id' => $owner->id, 'group_id' => $group->id]);
    $plainPost = Post::factory()->create(['user_id' => $owner->id]);

    Livewire::actingAs($outsider)->test(ChatComponent::class, ['conversationable' => $groupPost])
        ->set('newMessage', 'let me in')->call('sendMessage')->assertForbidden();
    Livewire::actingAs($member)->test(ChatComponent::class, ['conversationable' => $groupPost])
        ->set('newMessage', 'member here')->call('sendMessage')->assertHasNoErrors();
    Livewire::actingAs($outsider)->test(ChatComponent::class, ['conversationable' => $plainPost])
        ->set('newMessage', 'open chat')->call('sendMessage')->assertHasNoErrors();

    expect(Message::where('body', 'let me in')->exists())->toBeFalse();
    expect(Message::where('body', 'member here')->exists())->toBeTrue();
    expect(Message::where('body', 'open chat')->exists())->toBeTrue();
});

test('private group event chat is closed to non members', function () {
    $group = Group::factory()->create(['privacy' => 'private']);
    $event = Activity::factory()->create(['group_id' => $group->id, 'status' => 'published', 'start_time' => now()->addDay()]);

    Livewire::actingAs(chatUser())->test(ChatComponent::class, ['conversationable' => $event])->assertForbidden();
});

test('messages are limited to 2000 characters and rate limited', function () {
    $user = chatUser();
    $post = Post::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post])
        ->set('newMessage', str_repeat('x', 2001))->call('sendMessage')->assertHasErrors(['newMessage' => 'max']);
    expect(Message::count())->toBe(0);

    RateLimiter::clear('chat-send:'.$user->id);
    $component = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post]);
    foreach (range(1, 31) as $i) {
        $component->set('newMessage', "msg {$i}")->call('sendMessage');
    }
    expect(Message::count())->toBe(30);
    $component->assertHasErrors('newMessage');
});

test('chat messages broadcast on a private channel', function () {
    [$a, $b] = [chatUser(), chatUser()];
    $dm = dmBetween($a, $b);
    $channel = (new MessageSent($dm->messages()->first()))->broadcastOn();

    expect($channel)->toBeInstanceOf(PrivateChannel::class)->and($channel->name)->toBe('private-conversation.'.$dm->id);
});
