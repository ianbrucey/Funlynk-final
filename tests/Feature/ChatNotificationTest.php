<?php

use App\Jobs\NotifyChatParticipants;
use App\Livewire\Chat\ChatComponent;
use App\Models\ConversationParticipant;
use App\Models\Group;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use App\Services\ChatNotifier;
use App\Services\ChatService;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function chatPerson(array $attributes = []): User
{
    return User::factory()->create($attributes + ['onboarding_completed_at' => now(), 'notification_preference' => 'all']);
}

/** A post chat with the owner plus $others as participants who last read an hour ago. */
function chatWith(User $owner, array $others, array $postAttributes = []): array
{
    $post = Post::factory()->create($postAttributes + ['user_id' => $owner->id, 'title' => 'Thursday skate']);
    $conversation = app(ChatService::class)->getOrCreateConversation($post);
    foreach (array_merge([$owner], $others) as $user) {
        app(ChatService::class)->addParticipant($conversation, $user);
    }
    ConversationParticipant::where('conversation_id', $conversation->id)->update(['last_read_at' => now()->subHour()]);

    return [$post, $conversation];
}

function chatNotes(User $user, ?string $type = null)
{
    return Notification::where('user_id', $user->id)->when($type, fn ($q) => $q->where('type', $type))->whereIn('type', ChatNotifier::TYPES)->get();
}

function post_as(User $user, App\Models\Conversation $conversation, string $body, ?string $replyTo = null, array $mentionIds = [])
{
    $service = app(ChatService::class);

    return $service->sendMessage($conversation, $user, $body, $replyTo, $service->resolveMentions($conversation, $body, $mentionIds));
}

test('a mention notifies the person even in a mentions-only chat', function () {
    [$owner, $ana, $lee] = [chatPerson(), chatPerson(['display_name' => 'Ana Rivera']), chatPerson()];
    [, $conversation] = chatWith($owner, [$ana, $lee]);
    app(ChatService::class)->setNotifyLevel($conversation, $ana, 'mentions');
    app(ChatService::class)->setNotifyLevel($conversation, $lee, 'mentions');

    post_as($owner, $conversation, 'hey @Ana bring the speaker', null, [$ana->id]);

    expect(chatNotes($ana, 'chat_mention'))->toHaveCount(1);
    expect(chatNotes($ana)->first()->data['url'])->toContain('/chat');
    expect(chatNotes($lee))->toHaveCount(0);
});

test('mentions are limited to participants whose handle is in the text', function () {
    [$owner, $ana] = [chatPerson(), chatPerson(['display_name' => 'Ana Rivera'])];
    $stranger = chatPerson(['display_name' => 'Zoe Stranger']);
    [, $conversation] = chatWith($owner, [$ana]);
    $service = app(ChatService::class);

    expect($service->resolveMentions($conversation, '@Ana and @Zoe', [$ana->id, $stranger->id]))->toBe([['id' => (string) $ana->id, 'handle' => 'Ana']]);
    expect($service->resolveMentions($conversation, 'no handle here', [$ana->id]))->toBe([]);
    expect(post_as($owner, $conversation, 'hi @Ana', null, [$ana->id])->metadata['mentions'][0]['id'])->toBe((string) $ana->id);
});

test('a reply notifies the author of the original message', function () {
    [$owner, $ana] = [chatPerson(), chatPerson()];
    [, $conversation] = chatWith($owner, [$ana]);
    $original = post_as($ana, $conversation, 'can we start at 6?');
    Notification::query()->delete();

    post_as($owner, $conversation, 'yes 6 works', $original->id);

    expect(chatNotes($ana, 'chat_reply'))->toHaveCount(1);
    expect(chatNotes($ana, 'chat_messages'))->toHaveCount(0);
});

test('a new pin notifies the other participants once', function () {
    [$owner, $ana] = [chatPerson(), chatPerson()];
    [$post, $conversation] = chatWith($owner, [$ana]);
    $message = post_as($owner, $conversation, 'PLAN: 6pm at the pier');
    Notification::query()->delete();

    Livewire::actingAs($owner)->test(ChatComponent::class, ['conversationable' => $post])->call('pinMessage', $message->id);
    Livewire::actingAs($owner)->test(ChatComponent::class, ['conversationable' => $post])->call('pinMessage', null)->call('pinMessage', $message->id);

    expect(chatNotes($ana, 'chat_pinned'))->toHaveCount(1);
    expect(chatNotes($owner))->toHaveCount(0);
});

test('ordinary messages collapse into one notification and update its count', function () {
    [$owner, $ana] = [chatPerson(), chatPerson()];
    [, $conversation] = chatWith($owner, [$ana]);

    foreach (['one', 'two', 'three'] as $word) {
        post_as($owner, $conversation, $word);
    }

    $notes = chatNotes($ana, 'chat_messages');
    expect($notes)->toHaveCount(1)->and($notes->first()->data['count'])->toBe(3)->and($notes->first()->title)->toContain('3 new messages');

    $this->travel(20)->minutes();
    post_as($owner, $conversation, 'later');
    expect(chatNotes($ana, 'chat_messages'))->toHaveCount(2);
});

test('sender, active viewers and muted participants get nothing', function () {
    [$owner, $viewer, $muted, $quiet] = [chatPerson(), chatPerson(), chatPerson(), chatPerson()];
    [, $conversation] = chatWith($owner, [$viewer, $muted, $quiet]);
    ConversationParticipant::where('conversation_id', $conversation->id)->where('user_id', $viewer->id)->update(['last_read_at' => now()]);
    app(ChatService::class)->setNotifyLevel($conversation, $muted, 'muted');

    post_as($owner, $conversation, 'anyone here?');

    expect(chatNotes($owner))->toHaveCount(0)->and(chatNotes($viewer))->toHaveCount(0)->and(chatNotes($muted))->toHaveCount(0);
    expect(chatNotes($quiet))->toHaveCount(1);

    post_as($owner, $conversation, 'hey @'.app(ChatService::class)->mentionHandle($muted), null, [$muted->id]);
    expect(chatNotes($muted))->toHaveCount(0);
});

test('mute states are stored per user and defaults follow chat size', function () {
    $notifier = app(ChatNotifier::class);
    expect($notifier->effectiveLevel('default', 10))->toBe('all')->and($notifier->effectiveLevel('default', 11))->toBe('mentions');
    expect($notifier->effectiveLevel('muted', 3))->toBe('muted')->and($notifier->effectiveLevel('all', 40))->toBe('all');

    [$owner, $ana] = [chatPerson(), chatPerson()];
    [$post, $conversation] = chatWith($owner, [$ana]);
    Livewire::actingAs($ana)->test(ChatComponent::class, ['conversationable' => $post])->call('setNotifyLevel', 'muted')->assertSet('notifyLevel', 'muted');
    expect($notifier->levelFor($conversation, $ana))->toBe('muted')->and($notifier->levelFor($conversation, $owner))->toBe('all');
    expect(ConversationParticipant::where('user_id', $ana->id)->value('is_muted'))->toBeTrue();
    expect(fn () => app(ChatService::class)->setNotifyLevel($conversation, $ana, 'bogus'))->toThrow(InvalidArgumentException::class);

    $outsider = chatPerson();
    $group = Group::factory()->create(['privacy' => 'public', 'created_by' => $owner->id]);
    $groupPost = Post::factory()->create(['user_id' => $owner->id, 'group_id' => $group->id]);
    Livewire::actingAs($outsider)->test(ChatComponent::class, ['conversationable' => $groupPost])->call('setNotifyLevel', 'muted')->assertForbidden();
});

test('someone no longer allowed to view the chat gets nothing', function () {
    [$owner, $former] = [chatPerson(), chatPerson()];
    $group = Group::factory()->create(['privacy' => 'private', 'created_by' => $owner->id]);
    $group->memberships()->create(['user_id' => $owner->id, 'role' => 'admin']);
    [, $conversation] = chatWith($owner, [$former], ['group_id' => $group->id]);

    post_as($owner, $conversation, 'members only plan');

    expect(chatNotes($former))->toHaveCount(0);
});

test('the global preference suppresses chat and direct message notifications', function () {
    [$owner, $none, $emailOnly, $normal] = [chatPerson(), chatPerson(['notification_preference' => 'none']), chatPerson(['notification_preference' => 'email_only']), chatPerson()];
    [, $conversation] = chatWith($owner, [$none, $emailOnly, $normal]);

    post_as($owner, $conversation, 'ping');
    expect(chatNotes($none))->toHaveCount(0)->and(chatNotes($emailOnly))->toHaveCount(0)->and(chatNotes($normal))->toHaveCount(1);

    $dm = app(ChatService::class)->createDirectMessageConversation($owner, $none);
    app(ChatService::class)->sendMessage($dm, $owner, 'direct hello');
    expect(Notification::where('user_id', $none->id)->where('type', 'direct_message')->count())->toBe(0);

    $dm2 = app(ChatService::class)->createDirectMessageConversation($owner, $normal);
    app(ChatService::class)->setNotifyLevel($dm2, $normal, 'muted');
    app(ChatService::class)->sendMessage($dm2, $owner, 'muted dm');
    expect(Notification::where('user_id', $normal->id)->where('type', 'direct_message')->count())->toBe(0);
});

test('chat notifications are queued', function () {
    Queue::fake();
    [$owner, $ana] = [chatPerson(), chatPerson()];
    [$post] = chatWith($owner, [$ana]);

    Livewire::actingAs($owner)->test(ChatComponent::class, ['conversationable' => $post])->set('newMessage', 'queued?')->call('sendMessage');

    Queue::assertPushed(NotifyChatParticipants::class);
    expect(chatNotes($ana))->toHaveCount(0);
});

test('unread state is true after others post and false after the viewer reads', function () {
    [$owner, $ana] = [chatPerson(), chatPerson()];
    [$post, $conversation] = chatWith($owner, [$ana]);
    $notifier = app(ChatNotifier::class);

    post_as($owner, $conversation, 'new plan');
    expect($notifier->hasUnread($conversation, $ana))->toBeTrue()->and($notifier->hasUnread($conversation, $owner))->toBeFalse();

    $this->actingAs($ana)->get(route('posts.show', $post))->assertOk()->assertSee('Unread messages');

    Livewire::actingAs($ana)->test(ChatComponent::class, ['conversationable' => $post]);
    expect($notifier->hasUnread($conversation, $ana))->toBeFalse();
    expect(chatNotes($ana)->where('is_read', false))->toHaveCount(0);
    expect($notifier->hasUnread($conversation, chatPerson()))->toBeFalse();
});

test('a chat notification opens the room and jumps to the message', function () {
    [$owner, $ana] = [chatPerson(), chatPerson()];
    [$post, $conversation] = chatWith($owner, [$ana]);
    $message = post_as($owner, $conversation, 'look at this one');
    $url = chatNotes($ana)->first()->data['url'];

    expect($url)->toBe(route('posts.chat', $post).'?m='.$message->id);
    $this->actingAs($ana)->get($url)->assertOk()->assertSee("jump('{$message->id}')", false);
});
