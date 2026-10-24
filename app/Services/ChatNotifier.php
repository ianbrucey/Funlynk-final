<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Group;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Decides who hears about a chat message or pin, and writes the in-app
 * notifications (spec 013). In-app only; honours the global preference,
 * per-chat notify level and the chat's own view rule.
 */
class ChatNotifier
{
    public const LARGE_CHAT_THRESHOLD = 10;

    public const COLLAPSE_MINUTES = 15;

    public const VIEWING_SECONDS = 60;

    public const LEVELS = ['all', 'mentions', 'muted'];

    public const TYPES = ['chat_mention', 'chat_reply', 'chat_pinned', 'chat_messages'];

    /**
     * Resolve a stored level ('default' or explicit) into all / mentions / muted.
     */
    public function effectiveLevel(string $stored, int $participantCount): string
    {
        if (in_array($stored, self::LEVELS, true)) {
            return $stored;
        }

        return $participantCount > self::LARGE_CHAT_THRESHOLD ? 'mentions' : 'all';
    }

    public function levelFor(Conversation $conversation, User $user): string
    {
        $stored = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->value('notify_level') ?? 'default';

        return $this->effectiveLevel((string) $stored, $conversation->participants()->count());
    }

    public function notifyForMessage(Message $message): void
    {
        $conversation = $message->conversation;
        $count = $conversation->participants()->count();
        $mentioned = array_map(fn ($m) => (string) ($m['id'] ?? ''), (array) data_get($message->metadata, 'mentions', []));
        $replyAuthorId = $message->reply_to_message_id ? (string) Message::query()->whereKey($message->reply_to_message_id)->value('user_id') : null;
        $senderName = $message->user->display_name ?? $message->user->username;
        $title = $this->subjectTitle($conversation);

        foreach ($this->recipients($conversation, $message->user_id) as $row) {
            $user = $row['user'];
            $level = $this->effectiveLevel($row['level'], $count);
            $isMention = in_array((string) $user->id, $mentioned, true);
            $isReply = $replyAuthorId !== null && (string) $user->id === $replyAuthorId;

            if ($level === 'muted') {
                continue;
            }

            if ($isMention || $isReply) {
                $this->create($user, $conversation, $isMention ? 'chat_mention' : 'chat_reply', $isMention ? "{$senderName} mentioned you" : "{$senderName} replied to you", "in {$title}: ".str($message->body)->limit(80), $message->id);

                continue;
            }

            if ($level === 'all') {
                $this->collapse($user, $conversation, $title, $senderName, $message->id);
            }
        }
    }

    public function notifyForPin(Conversation $conversation, Message $message, User $actor): void
    {
        $title = $this->subjectTitle($conversation);
        $count = $conversation->participants()->count();

        foreach ($this->recipients($conversation, (string) $actor->id, false) as $row) {
            if ($this->effectiveLevel($row['level'], $count) === 'muted') {
                continue;
            }

            $exists = Notification::query()->where('user_id', $row['user']->id)->where('type', 'chat_pinned')->where('data->message_id', $message->id)->exists();
            if (! $exists) {
                $this->create($row['user'], $conversation, 'chat_pinned', 'Plan pinned in '.$title, (string) str($message->body)->limit(100), $message->id);
            }
        }
    }

    /**
     * Participants (other than the actor) who pass the global preference, the
     * chat's view rule and, for messages, are not looking at the chat right now.
     *
     * @return \Generator<int, array{user: User, level: string}>
     */
    protected function recipients(Conversation $conversation, string $actorId, bool $skipViewers = true): \Generator
    {
        $participants = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $actorId)
            ->get(['user_id', 'notify_level', 'last_read_at']);

        foreach ($participants as $participant) {
            $user = User::query()->find($participant->user_id);

            if (! $user || ! $user->is_active || ! $user->wantsInAppNotifications()) {
                continue;
            }

            if ($skipViewers && $participant->last_read_at && \Illuminate\Support\Carbon::parse($participant->last_read_at)->gt(now()->subSeconds(self::VIEWING_SECONDS))) {
                continue;
            }

            if (! Gate::forUser($user)->allows('view', $conversation)) {
                continue;
            }

            yield ['user' => $user, 'level' => (string) ($participant->notify_level ?? 'default')];
        }
    }

    /**
     * One "N new messages" notification per chat, started at most every 15 minutes.
     */
    protected function collapse(User $user, Conversation $conversation, string $title, string $senderName, string $messageId): void
    {
        $existing = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', 'chat_messages')
            ->where('is_read', false)
            ->where('data->conversation_id', $conversation->id)
            ->latest('created_at')
            ->first();

        $startedAt = $existing ? data_get($existing->getAttribute('data'), 'started_at') : null;

        if (! $existing || ! is_string($startedAt) || \Illuminate\Support\Carbon::parse($startedAt)->lte(now()->subMinutes(self::COLLAPSE_MINUTES))) {
            $this->create($user, $conversation, 'chat_messages', "New messages in {$title}", "Latest from {$senderName}", $messageId, ['count' => 1, 'started_at' => now()->toIso8601String()]);

            return;
        }

        /** @var array<string, mixed> $data */
        $data = (array) $existing->getAttribute('data');
        $data['count'] = ((int) ($data['count'] ?? 1)) + 1;
        $data['message_id'] = $messageId;
        $existing->update([
            'title' => "{$data['count']} new messages in {$title}",
            'message' => "Latest from {$senderName}",
            'data' => $data,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function create(User $user, Conversation $conversation, string $type, string $title, string $message, string $messageId, array $extra = []): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => [
                'conversation_id' => $conversation->id,
                'message_id' => $messageId,
                'url' => $this->urlFor($conversation, $messageId),
            ] + $extra,
            'delivery_method' => 'in_app',
            'delivery_status' => 'sent',
        ]);
    }

    public function subjectTitle(Conversation $conversation): string
    {
        $subject = $conversation->conversationable;

        if ($subject instanceof Post || $subject instanceof Activity) {
            return (string) $subject->title;
        }

        if ($conversation->group_id !== null && $conversation->group instanceof Group) {
            return $conversation->group->name;
        }

        return 'your chat';
    }

    public function urlFor(Conversation $conversation, string $messageId): string
    {
        $subject = $conversation->conversationable;

        $url = match (true) {
            $subject instanceof Post => route('posts.chat', $subject),
            $subject instanceof Activity => route('events.show', $subject).'#discussion',
            $conversation->group instanceof Group => route('groups.show', $conversation->group->slug),
            default => route('messages.show', $conversation->id),
        };

        return str_contains($url, '#') ? $url : $url.'?m='.$messageId;
    }

    /**
     * Does the user have messages from others they have not read yet?
     */
    public function hasUnread(Conversation $conversation, User $user): bool
    {
        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first(['last_read_at']);

        if (! $participant) {
            return false;
        }

        $lastRead = $participant->getRawOriginal('last_read_at');

        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))
            ->exists();
    }

    public function markChatNotificationsRead(Conversation $conversation, User $user): void
    {
        Notification::query()
            ->where('user_id', $user->id)
            ->whereIn('type', self::TYPES)
            ->where('is_read', false)
            ->where('data->conversation_id', $conversation->id)
            ->update(['is_read' => true, 'read_at' => now()]);
    }
}
