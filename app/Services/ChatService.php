<?php

namespace App\Services;

use App\Models\Conversation;
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
    public function sendMessage(
        Conversation $conversation,
        User $user,
        string $body,
        ?string $replyToMessageId = null
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
        ];
    }

    /**
     * Pin a message of this conversation (or clear the pin with null).
     */
    public function pinMessage(Conversation $conversation, ?Message $message): void
    {
        if ($message !== null && $message->conversation_id !== $conversation->id) {
            throw new \InvalidArgumentException('Message is not part of this conversation.');
        }

        $conversation->update(['pinned_message_id' => $message?->id]);
    }

    /**
     * Mark conversation as read for a user
     */
    public function markAsRead(Conversation $conversation, User $user): void
    {
        $conversation->participants()
            ->updateExistingPivot($user->id, ['last_read_at' => now()]);
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
            ->updateExistingPivot($user->id, ['is_muted' => $newMuteStatus]);

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
