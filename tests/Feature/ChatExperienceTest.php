<?php

use App\Livewire\Chat\ChatComponent;
use App\Models\Activity;
use App\Models\Group;
use App\Models\Message;
use App\Models\Post;
use App\Models\User;
use App\Services\ChatService;
use Livewire\Livewire;

function roomUser(array $attributes = []): User
{
    return User::factory()->create($attributes + ['onboarding_completed_at' => now()]);
}

function roomFor(User $owner, ?Group $group = null): array
{
    $post = Post::factory()->create(['user_id' => $owner->id, 'group_id' => $group?->id]);
    $conversation = app(ChatService::class)->getOrCreateConversation($post);

    return [$post, $conversation];
}

function say(App\Models\Conversation $conversation, User $user, string $body, ?string $at = null, ?string $replyTo = null): Message
{
    $message = app(ChatService::class)->sendMessage($conversation, $user, $body, $replyTo);
    if ($at) {
        $message->forceFill(['created_at' => $at])->save();
    }

    return $message;
}

test('send appends the new message without reloading history', function () {
    $user = roomUser();
    [$post, $conversation] = roomFor($user);
    say($conversation, $user, 'first');

    $component = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post]);
    $other = roomUser();
    say($conversation, $other, 'sneaked in after load');

    $component->set('newMessage', 'second')->call('sendMessage')->assertHasNoErrors();

    $bodies = collect($component->get('messages'))->pluck('body')->all();
    expect($bodies)->toBe(['first', 'second']);
    expect(Message::where('conversation_id', $conversation->id)->count())->toBe(3);
});

test('consecutive messages group and a day separator appears when the day changes', function () {
    $user = roomUser();
    $other = roomUser(['display_name' => 'Pat Poster']);
    [$post, $conversation] = roomFor($user);
    $noon = now()->startOfDay()->addHours(12);
    say($conversation, $other, 'older day', $noon->copy()->subDays(2)->toDateTimeString());
    say($conversation, $other, 'm1', $noon->copy()->subMinutes(30)->toDateTimeString());
    say($conversation, $other, 'm2', $noon->copy()->subMinutes(29)->toDateTimeString());
    say($conversation, $user, 'mine', $noon->copy()->subMinutes(10)->toDateTimeString());

    $html = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post])->html();

    expect(substr_count($html, 'text-gray-400">Pat Poster</div>'))->toBe(2);
    expect($html)->toContain('Today')->toContain($noon->copy()->subDays(2)->format('M j'));
});

test('load earlier pages without duplicates', function () {
    $user = roomUser();
    [$post, $conversation] = roomFor($user);
    foreach (range(1, 60) as $i) {
        say($conversation, $user, sprintf('msg %02d', $i), now()->subMinutes(100 - $i)->toDateTimeString());
    }

    $component = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post]);
    expect($component->get('messages'))->toHaveCount(50)->and($component->get('hasEarlier'))->toBeTrue();
    expect($component->get('messages')[0]['body'])->toBe('msg 11');

    $component->call('loadEarlier');
    $ids = collect($component->get('messages'))->pluck('id');
    expect($ids)->toHaveCount(60)->and($ids->unique())->toHaveCount(60)->and($component->get('hasEarlier'))->toBeFalse();
    expect($component->get('messages')[0]['body'])->toBe('msg 01');
});

test('unread divider marks the first message the viewer has not read', function () {
    $owner = roomUser();
    $viewer = roomUser();
    [$post, $conversation] = roomFor($owner);
    say($conversation, $owner, 'already read', now()->subHour()->toDateTimeString());
    app(ChatService::class)->addParticipant($conversation, $viewer);
    app(ChatService::class)->markAsRead($conversation, $viewer);
    $new = say($conversation, $owner, 'brand new', now()->addMinute()->toDateTimeString());
    say($conversation, $owner, 'newer still', now()->addMinutes(2)->toDateTimeString());

    $component = Livewire::actingAs($viewer)->test(ChatComponent::class, ['conversationable' => $post]);

    expect($component->get('firstUnreadId'))->toBe($new->id);
    $component->assertSee('New messages');
});

test('reply to own and others carries the original id', function () {
    $user = roomUser();
    $other = roomUser();
    [$post, $conversation] = roomFor($user);
    $theirs = say($conversation, $other, 'their message');
    $mine = say($conversation, $user, 'my message');

    $component = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post]);
    foreach ([$theirs, $mine] as $original) {
        $component->call('replyTo', $original->id)->set('newMessage', 'reply to '.$original->body)->call('sendMessage');
        $sent = Message::where('body', 'reply to '.$original->body)->first();
        expect($sent->reply_to_message_id)->toBe($original->id);
    }
});

test('reactions toggle per user and emoji, count, and are authorized', function () {
    $user = roomUser();
    $other = roomUser();
    $outsider = roomUser();
    $group = Group::factory()->create(['privacy' => 'private', 'created_by' => $user->id]);
    $group->memberships()->create(['user_id' => $user->id, 'role' => 'admin']);
    $group->memberships()->create(['user_id' => $other->id, 'role' => 'member']);
    [$post, $conversation] = roomFor($user, $group);
    $message = say($conversation, $user, 'plan: 6pm');

    Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post])->call('reactToMessage', $message->id, '👍');
    $viewerOther = Livewire::actingAs($other)->test(ChatComponent::class, ['conversationable' => $post])->call('reactToMessage', $message->id, '👍');

    expect($message->reactions()->count())->toBe(2);
    expect($viewerOther->get('messages')[0]['reactions']['👍'])->toBe(['count' => 2, 'mine' => true]);

    $viewerOther->call('reactToMessage', $message->id, '👍');
    expect($message->reactions()->count())->toBe(1);

    Livewire::actingAs($outsider)->test(ChatComponent::class, ['conversationable' => $post])->assertForbidden();
    expect(fn () => app(ChatService::class)->reactToMessage($message, $user, 'not-an-emoji'))->toThrow(InvalidArgumentException::class);
});

test('pin rules: owner, host or admin may pin, others and DMs may not', function () {
    $owner = roomUser();
    $member = roomUser();
    [$post, $conversation] = roomFor($owner);
    $message = say($conversation, $member, 'let us meet at six');

    Livewire::actingAs($member)->test(ChatComponent::class, ['conversationable' => $post])->call('pinMessage', $message->id)->assertForbidden();
    expect($conversation->fresh()->pinned_message_id)->toBeNull();

    Livewire::actingAs($owner)->test(ChatComponent::class, ['conversationable' => $post])
        ->call('pinMessage', $message->id)->assertSet('pinnedMessageId', $message->id);
    expect($conversation->fresh()->pinned_message_id)->toBe($message->id);

    Livewire::actingAs($owner)->test(ChatComponent::class, ['conversationable' => $post])->call('pinMessage', null);
    expect($conversation->fresh()->pinned_message_id)->toBeNull();

    $host = roomUser();
    $event = Activity::factory()->create(['host_id' => $host->id, 'status' => 'published', 'start_time' => now()->addDay(), 'is_public' => true]);
    $eventConversation = app(ChatService::class)->getOrCreateConversation($event);
    $eventMessage = say($eventConversation, $member, 'bring chairs');
    Livewire::actingAs($member)->test(ChatComponent::class, ['conversationable' => $event])->call('pinMessage', $eventMessage->id)->assertForbidden();
    Livewire::actingAs($host)->test(ChatComponent::class, ['conversationable' => $event])->call('pinMessage', $eventMessage->id);
    expect($eventConversation->fresh()->pinned_message_id)->toBe($eventMessage->id);

    $dm = app(ChatService::class)->createDirectMessageConversation($owner, $member);
    $dmMessage = say($dm, $owner, 'hey');
    Livewire::actingAs($owner)->test(ChatComponent::class, ['conversationId' => $dm->id])->call('pinMessage', $dmMessage->id)->assertForbidden();

    $otherConversation = roomFor($owner)[1];
    $foreign = say($otherConversation, $owner, 'elsewhere');
    expect(fn () => Livewire::actingAs($owner)->test(ChatComponent::class, ['conversationable' => $post])->call('pinMessage', $foreign->id))->toThrow(Exception::class);
});

test('pinned message shows in the room and on the post page preview', function () {
    $owner = roomUser();
    [$post, $conversation] = roomFor($owner);
    $message = say($conversation, $owner, 'PLAN: Thursday 6pm at the pier');
    app(ChatService::class)->pinMessage($conversation, $message);

    Livewire::actingAs($owner)->test(ChatComponent::class, ['conversationable' => $post])->assertSee('PLAN: Thursday 6pm at the pier');
    $this->actingAs($owner)->get(route('posts.show', $post))->assertOk()->assertSee('PLAN: Thursday 6pm at the pier');
});

test('post page shows the last three messages and Open chat, not the full chat', function () {
    $owner = roomUser();
    [$post, $conversation] = roomFor($owner);
    foreach (['one', 'two', 'three', 'four'] as $i => $word) {
        say($conversation, $owner, "word-{$word}", now()->subMinutes(10 - $i)->toDateTimeString());
    }

    $this->actingAs($owner)->get(route('posts.show', $post))->assertOk()
        ->assertDontSee('word-one')->assertSee('word-two')->assertSee('word-three')->assertSee('word-four')
        ->assertSee('Open chat')->assertSee(route('posts.chat', $post), false)
        ->assertDontSeeLivewire(ChatComponent::class);

    $this->actingAs($owner)->get(route('posts.chat', $post))->assertOk()->assertSeeLivewire(ChatComponent::class);
});

test('a reader who cannot send sees a message instead of the composer', function () {
    $owner = roomUser();
    $outsider = roomUser();
    $group = Group::factory()->create(['privacy' => 'public', 'name' => 'Skate Crew', 'created_by' => $owner->id]);
    [$post] = roomFor($owner, $group);

    Livewire::actingAs($outsider)->test(ChatComponent::class, ['conversationable' => $post])
        ->assertSee('Only members of Skate Crew can send messages here.')->assertDontSeeHtml('aria-label="Send"');
});

function searchRoom(): array
{
    $user = roomUser();
    [$post, $conversation] = roomFor($user);

    return [$user, $post, $conversation];
}

test('search is scoped to the conversation, newest first, and matches case-insensitively', function () {
    [$user, $post, $conversation] = searchRoom();
    [, $otherConversation] = roomFor($user);
    say($conversation, $user, 'Bringing SKATE wax', now()->subDays(3)->toDateTimeString());
    say($conversation, $user, 'skate key anyone?', now()->subDays(1)->toDateTimeString());
    say($conversation, $user, 'nothing relevant');
    say($otherConversation, $user, 'skate in the other chat');

    $component = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post])->set('searchQuery', 'Skate');
    $hits = collect($component->get('searchResults'));

    expect($hits->map(fn ($h) => $h['snippet']['before'].$h['snippet']['match'].$h['snippet']['after'])->all())->toBe(['skate key anyone?', 'Bringing SKATE wax']);
    expect($hits->first()['snippet']['match'])->toBe('skate');
});

test('a person who cannot view the conversation cannot search or jump', function () {
    $a = roomUser();
    $b = roomUser();
    $outsider = roomUser();
    $dm = app(ChatService::class)->createDirectMessageConversation($a, $b);
    $message = say($dm, $a, 'private plans');

    Livewire::actingAs($outsider)->test(ChatComponent::class, ['conversationId' => $dm->id])->assertForbidden();
    Livewire::actingAs($b)->test(ChatComponent::class, ['conversationId' => $dm->id])->set('searchQuery', 'plans')->assertCount('searchResults', 1);
    expect($message->conversation_id)->toBe($dm->id);
});

test('search treats wildcards as plain text and ignores very short queries', function () {
    [$user, $post, $conversation] = searchRoom();
    say($conversation, $user, '100% sure about this');
    say($conversation, $user, 'a_b is a name');
    say($conversation, $user, 'plain words here');
    $component = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post]);

    expect($component->set('searchQuery', '%')->get('searchResults'))->toBe([]);
    expect($component->set('searchQuery', '0% s')->get('searchResults'))->toHaveCount(1);
    expect($component->set('searchQuery', 'a_')->get('searchResults'))->toHaveCount(1);
    expect($component->set('searchQuery', '__')->get('searchResults'))->toBe([]);
    expect($component->set('searchQuery', 'a\\')->get('searchResults'))->toBe([]);
    expect($component->set('searchQuery', 'pl')->get('searchResults'))->toHaveCount(1);
});

test('search never returns deleted or system messages', function () {
    [$user, $post, $conversation] = searchRoom();
    $gone = say($conversation, $user, 'secret skate plan');
    say($conversation, $user, 'kept skate plan');
    $gone->delete();
    $system = say($conversation, $user, 'system skate notice');
    $system->forceFill(['type' => 'system'])->save();

    $hits = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post])->set('searchQuery', 'skate')->get('searchResults');

    expect($hits)->toHaveCount(1)->and($hits[0]['snippet']['before'])->toBe('kept ');
});

test('search results page twenty at a time without duplicates', function () {
    [$user, $post, $conversation] = searchRoom();
    foreach (range(1, 45) as $i) {
        say($conversation, $user, "hit number {$i}", now()->subMinutes(100 - $i)->toDateTimeString());
    }

    $component = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post])->set('searchQuery', 'hit number');
    expect($component->get('searchResults'))->toHaveCount(20)->and($component->get('searchHasMore'))->toBeTrue();

    $component->call('showMoreResults')->call('showMoreResults');
    $ids = collect($component->get('searchResults'))->pluck('id');
    expect($ids)->toHaveCount(45)->and($ids->unique())->toHaveCount(45)->and($component->get('searchHasMore'))->toBeFalse();
});

test('a search request runs the same number of queries however many messages match', function () {
    [$user, $post, $conversation] = searchRoom();
    $component = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post]);
    $count = function (string $term) use ($component) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $component->set('searchQuery', $term);
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    foreach (range(1, 3) as $i) {
        say($conversation, $user, "few {$i}");
        say($conversation, $user, "many {$i}");
    }
    foreach (range(4, 40) as $i) {
        say($conversation, $user, "many {$i}");
    }

    expect($count('many'))->toBe($count('few') + 0);
});

test('jumping to an old message loads the window around it, and back to latest restores the newest', function () {
    [$user, $post, $conversation] = searchRoom();
    $ids = [];
    foreach (range(1, 160) as $i) {
        $ids[$i] = say($conversation, $user, sprintf('msg %03d', $i), now()->subMinutes(300 - $i)->toDateTimeString())->id;
    }

    $component = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post]);
    expect(collect($component->get('messages'))->pluck('id'))->not->toContain($ids[20]);

    $component->call('jumpTo', $ids[20]);
    $bodies = collect($component->get('messages'))->pluck('body');
    expect($bodies)->toContain('msg 020')->and($bodies->first())->toBe('msg 001')->and($bodies->last())->toBe('msg 045');
    expect($component->get('anchorId'))->toBe($ids[20]);

    $component->call('backToLatest');
    $bodies = collect($component->get('messages'))->pluck('body');
    expect($component->get('anchorId'))->toBeNull()->and($bodies->last())->toBe('msg 160')->and($bodies)->toHaveCount(50);
});

test('a jump target from another conversation is refused', function () {
    [$user, $post, $conversation] = searchRoom();
    [, $other] = roomFor($user);
    say($conversation, $user, 'mine');
    $foreign = say($other, $user, 'elsewhere');

    $component = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post]);
    $component->call('jumpTo', $foreign->id);

    expect($component->get('anchorId'))->toBeNull();
    expect(collect($component->get('messages'))->pluck('body')->all())->toBe(['mine']);
});

test('an old message link opens the window around it, and sending returns to the latest', function () {
    [$user, $post, $conversation] = searchRoom();
    $target = null;
    foreach (range(1, 120) as $i) {
        $m = say($conversation, $user, sprintf('msg %03d', $i), now()->subMinutes(300 - $i)->toDateTimeString());
        $target = $i === 10 ? $m : $target;
    }

    $component = Livewire::actingAs($user)->withQueryParams(['m' => $target->id])->test(ChatComponent::class, ['conversationable' => $post]);
    expect(collect($component->get('messages'))->pluck('body'))->toContain('msg 010')->and($component->get('anchorId'))->toBe($target->id);

    $component->set('newMessage', 'back in the present')->call('sendMessage');
    expect($component->get('anchorId'))->toBeNull()->and(collect($component->get('messages'))->last()['body'])->toBe('back in the present');
});

test('search results escape html in message text', function () {
    [$user, $post, $conversation] = searchRoom();
    say($conversation, $user, 'look <script>alert(1)</script> here');

    $html = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $post])->set('searchQuery', 'script')->html();

    expect($html)->not->toContain('<script>alert(1)</script>')->toContain('&lt;');
});
