<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ChatService
{
    /**
     * Get or create a conversation for a conversationable (Post or Activity)
     */
    public function getOrCreateConversation(Model $conversationable): Conversation
    {
        // Check if conversation already exists
        $conversation = $conversationable->morphOne(Conversation::class, 'conversationable')->first();

        if ($conversation) {
            return $conversation;
        }

        // Determine conversation type based on conversationable
        $type = match (get_class($conversationable)) {
            'App\Models\Post' => 'public',
            'App\Models\Activity' => 'group',
            default => 'public',
        };

        // Create new conversation
        $conversation = Conversation::create([
            'type' => $type,
            'conversationable_type' => get_class($conversationable),
            'conversationable_id' => $conversationable->id,
            'last_message_at' => now(),
        ]);

        return $conversation;
    }

    /**
     * Create or get a direct message conversation between two users
     */
    public function createDirectMessageConversation(User $sender, User $recipient): Conversation
    {
        // Check if conversation already exists (either direction)
        $existingConversation = Conversation::where('type', 'private')
            ->whereNull('conversationable_type')
            ->whereNull('conversationable_id')
            ->whereHas('participants', function ($query) use ($sender) {
                $query->where('user_id', $sender->id);
            })
            ->whereHas('participants', function ($query) use ($recipient) {
                $query->where('user_id', $recipient->id);
            })
            ->first();

        if ($existingConversation) {
            return $existingConversation;
        }

        // Determine if this should be a message request
        $isRequest = $this->shouldRouteToRequests($sender, $recipient);

        // Create new DM conversation
        $conversation = Conversation::create([
            'type' => 'private',
            'conversationable_type' => null,
            'conversationable_id' => null,
            'last_message_at' => now(),
            'metadata' => [
                'is_dm' => true,
                'is_request' => $isRequest,
                'recipient_id' => $recipient->id,
            ],
        ]);

        // Add both users as participants
        $conversation->participants()->attach($sender->id, [
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'role' => 'member',
            'is_muted' => false,
            'last_read_at' => now(),
            'request_status' => null, // Sender doesn't have request status
        ]);

        $conversation->participants()->attach($recipient->id, [
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'role' => 'member',
            'is_muted' => false,
            'last_read_at' => null, // Recipient hasn't read yet
            'request_status' => $isRequest ? 'pending' : null,
        ]);

        return $conversation;
    }

    /**
     * Check if two users are mutual followers
     */
    public function isMutualFollower(User $userA, User $userB): bool
    {
        return $userA->mutuals()->where('users.id', $userB->id)->exists();
    }

    /**
     * Determine if message should route to requests inbox
     */
    public function shouldRouteToRequests(User $sender, User $recipient): bool
    {
        return ! $this->isMutualFollower($sender, $recipient);
    }

    /**
     * Send a message in a conversation
     */
    /**
     * @param  array<int, array{id: string, handle: string}>  $mentions
     */
    public function sendMessage(
        Conversation $conversation,
        User $user,
        string $body,
        ?string $replyToMessageId = null,
        array $mentions = []
    ): Message {
        // Add user as participant if not already
        $this->addParticipant($conversation, $user);

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => $body,
            'reply_to_message_id' => $replyToMessageId,
            'type' => 'text',
            'metadata' => $mentions === [] ? null : ['mentions' => $mentions],
        ]);

        // Update conversation's last_message_at
        $conversation->update(['last_message_at' => now()]);

        // Fire appropriate event based on conversation type and request status
        if ($conversation->type === 'private') {
            $isRequest = $conversation->metadata['is_request'] ?? false;

            // Get the recipient (the other user in the conversation)
            $recipient = $conversation->participants()
                ->where('user_id', '!=', $user->id)
                ->first();

            if ($isRequest) {
                event(new \App\Events\MessageRequestReceived($message, $conversation, $user, $recipient));
            } else {
                event(new \App\Events\DirectMessageReceived($message, $conversation, $user, $recipient));
            }
        }

        // Broadcast the message
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        // DMs notify through their own listener; other chats use the chat notifier.
        if ($conversation->type !== 'private') {
            \App\Jobs\NotifyChatParticipants::dispatch($message->id)->afterCommit();
        }

        return $message;
    }

    /**
     * Add a user as a participant in a conversation
     */
    public function addParticipant(Conversation $conversation, User $user, string $role = 'member'): void
    {
        // Check if already a participant
        if ($conversation->participants()->where('user_id', $user->id)->exists()) {
            return;
        }

        $conversation->participants()->attach($user->id, [
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'role' => $role,
            'is_muted' => false,
            'last_read_at' => now(),
        ]);
    }

    public const REACTIONS = ['👍', '❤️', '😂', '🔥', '🙌'];

    /**
     * Get messages for a conversation (the latest $limit, oldest first).
     */
    public function getMessages(Conversation $conversation, int $limit = 50)
    {
        return $conversation->messages()
            ->with(['user', 'replyTo.user', 'reactions'])
            ->latest()
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public const SEARCH_MIN_LENGTH = 2;

    public const SEARCH_PAGE_SIZE = 20;

    /**
     * Search text messages in one conversation, newest first. Deleted and system
     * messages are excluded. LIKE wildcards in the term are treated as plain text.
     *
     * @return array{results: array<int, array<string, mixed>>, hasMore: bool}
     */
    public function searchMessages(Conversation $conversation, string $term, int $page = 1): array
    {
        $term = mb_substr(trim($term), 0, 100);

        if (mb_strlen($term) < self::SEARCH_MIN_LENGTH) {
            return ['results' => [], 'hasMore' => false];
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        $page = max(1, $page);

        /** @var \Illuminate\Support\Collection<int, Message> $rows */
        $rows = $conversation->messages()
            ->with('user:id,display_name,username')
            ->where('type', 'text')
            ->where('body', 'ILIKE', '%'.$escaped.'%')
            ->latest()
            ->orderByDesc('id')
            ->offset(($page - 1) * self::SEARCH_PAGE_SIZE)
            ->limit(self::SEARCH_PAGE_SIZE + 1)
            ->get();

        $results = [];
        foreach ($rows->take(self::SEARCH_PAGE_SIZE) as $message) {
            $results[] = [
                'id' => $message->id,
                'user' => $message->user->display_name ?? $message->user->username ?? 'Someone',
                'date' => $message->created_at->format('M j, Y'),
                'snippet' => $this->snippet((string) $message->body, $term),
            ];
        }

        return ['results' => $results, 'hasMore' => $rows->count() > self::SEARCH_PAGE_SIZE];
    }

    /**
     * Split a body around the first match so the view can highlight it with escaped output.
     *
     * @return array{before: string, match: string, after: string}
     */
    public function snippet(string $body, string $term): array
    {
        $pos = mb_stripos($body, $term);

        if ($pos === false) {
            return ['before' => mb_substr($body, 0, 120), 'match' => '', 'after' => ''];
        }

        $start = max(0, $pos - 40);
        $length = mb_strlen($term);

        return [
            'before' => ($start > 0 ? '…' : '').mb_substr($body, $start, $pos - $start),
            'match' => mb_substr($body, $pos, $length),
            'after' => mb_substr($body, $pos + $length, 80).(mb_strlen($body) > $pos + $length + 80 ? '…' : ''),
        ];
    }

    /**
     * A window of messages around $target (oldest first): up to $each before and after it.
     *
     * @return array{rows: \Illuminate\Support\Collection<int, Message>, hasEarlier: bool, hasLater: bool}
     */
    public function getMessagesAround(Conversation $conversation, Message $target, int $each = 25): array
    {
        $with = ['user', 'replyTo.user', 'reactions'];

        /** @var \Illuminate\Support\Collection<int, Message> $before */
        $before = $conversation->messages()->with($with)
            ->where(fn ($q) => $q->where('created_at', '<', $target->created_at)
                ->orWhere(fn ($q) => $q->where('created_at', $target->created_at)->where('id', '<', $target->id)))
            ->latest()->orderByDesc('id')->limit($each + 1)->get()->reverse()->values();

        /** @var \Illuminate\Support\Collection<int, Message> $after */
        $after = $conversation->messages()->with($with)
            ->where(fn ($q) => $q->where('created_at', '>', $target->created_at)
                ->orWhere(fn ($q) => $q->where('created_at', $target->created_at)->where('id', '>', $target->id)))
            ->oldest()->orderBy('id')->limit($each + 1)->get();

        $hasEarlier = $before->count() > $each;
        $hasLater = $after->count() > $each;

        /** @var \Illuminate\Support\Collection<int, Message> $rows */
        $rows = ($hasEarlier ? $before->slice(1) : $before)
            ->push($target->loadMissing($with))
            ->merge($hasLater ? $after->take($each) : $after)
            ->values();

        return ['rows' => $rows, 'hasEarlier' => $hasEarlier, 'hasLater' => $hasLater];
    }

    /**
     * Plain-array shape of a message for the chat UI and broadcasts.
     *
     * @return array<string, mixed>
     */
    public function messagePayload(Message $message, ?string $viewerId): array
    {
        $message->loadMissing(['user', 'replyTo.user', 'reactions']);

        $reactions = [];
        foreach ($message->reactions->groupBy('reaction') as $emoji => $rows) {
            $reactions[$emoji] = [
                'count' => $rows->count(),
                'mine' => $viewerId !== null && $rows->contains('user_id', $viewerId),
            ];
        }

        return [
            'id' => $message->id,
            'user_id' => $message->user_id,
            'user' => [
                'display_name' => $message->user->display_name ?? $message->user->username,
                'profile_image_url' => $message->user->profile_image_url,
            ],
            'body' => $message->body,
            'created_at' => $message->created_at->toISOString(),
            'is_mine' => $viewerId !== null && $message->user_id === $viewerId,
            'reply_to' => $message->replyTo ? [
                'id' => $message->replyTo->id,
                'body' => $message->replyTo->body,
                'user' => [
                    'display_name' => $message->replyTo->user->display_name ?? $message->replyTo->user->username,
                ],
            ] : null,
            'reactions' => $reactions,
            'mentions' => data_get($message->metadata, 'mentions', []),
        ];
    }

    /**
     * Pin a message of this conversation (or clear the pin with null).
     */
    public function pinMessage(Conversation $conversation, ?Message $message, ?User $actor = null): void
    {
        if ($message !== null && $message->conversation_id !== $conversation->id) {
            throw new \InvalidArgumentException('Message is not part of this conversation.');
        }

        $conversation->update(['pinned_message_id' => $message?->id]);

        if ($message !== null && $actor !== null) {
            \App\Jobs\NotifyChatParticipants::dispatch($message->id, (string) $actor->id)->afterCommit();
        }
    }

    /**
     * Keep only mentions of people in this chat whose @handle is really in the text.
     *
     * @param  array<int, mixed>  $ids
     * @return array<int, array{id: string, handle: string}>
     */
    public function resolveMentions(Conversation $conversation, string $body, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));
        if ($ids === []) {
            return [];
        }

        $allowed = ConversationParticipant::query()->where('conversation_id', $conversation->id)->whereIn('user_id', $ids)->pluck('user_id');
        $resolved = [];
        foreach (User::query()->whereIn('id', $allowed)->get() as $person) {
            $handle = $this->mentionHandle($person);
            if (preg_match('/(^|\s)@'.preg_quote($handle, '/').'(?![\w.])/iu', $body) === 1) {
                $resolved[] = ['id' => (string) $person->id, 'handle' => $handle];
            }
        }

        return $resolved;
    }

    public function mentionHandle(User $user): string
    {
        $name = trim((string) ($user->display_name ?: $user->username));

        return preg_replace('/\s.*/u', '', $name) ?: (string) $user->username;
    }

    public function setNotifyLevel(Conversation $conversation, User $user, string $level): void
    {
        if (! in_array($level, ChatNotifier::LEVELS, true)) {
            throw new \InvalidArgumentException('Unsupported notification level.');
        }

        $this->addParticipant($conversation, $user);
        $conversation->participants()->updateExistingPivot($user->id, ['notify_level' => $level, 'is_muted' => $level === 'muted']);
    }

    /**
     * Mark conversation as read for a user
     */
    public function markAsRead(Conversation $conversation, User $user): void
    {
        $conversation->participants()
            ->updateExistingPivot($user->id, ['last_read_at' => now()]);

        app(ChatNotifier::class)->markChatNotificationsRead($conversation, $user);
    }

    /**
     * Toggle mute for a conversation
     */
    public function toggleMute(Conversation $conversation, User $user): bool
    {
        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            return false;
        }

        $newMuteStatus = ! $participant->pivot->is_muted;

        $conversation->participants()
            ->updateExistingPivot($user->id, ['is_muted' => $newMuteStatus, 'notify_level' => $newMuteStatus ? 'muted' : 'default']);

        return $newMuteStatus;
    }

    /**
     * React to a message
     */
    public function reactToMessage(Message $message, User $user, string $reaction): void
    {
        if (! in_array($reaction, self::REACTIONS, true)) {
            throw new \InvalidArgumentException('Unsupported reaction.');
        }

        // Check if user already reacted with this emoji
        $existingReaction = $message->reactions()
            ->where('user_id', $user->id)
            ->where('reaction', $reaction)
            ->first();

        if ($existingReaction) {
            // Remove reaction (toggle off)
            $existingReaction->delete();
        } else {
            // Add reaction
            $message->reactions()->create([
                'user_id' => $user->id,
                'reaction' => $reaction,
            ]);
        }
    }
}
