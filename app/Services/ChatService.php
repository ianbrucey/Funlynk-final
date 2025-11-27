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
        $conversation = $conversationable->conversation;

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

    /**
     * Get messages for a conversation
     */
    public function getMessages(Conversation $conversation, int $limit = 50)
    {
        return $conversation->messages()
            ->with(['user', 'replyTo.user', 'reactions.user'])
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
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

        if (!$participant) {
            return false;
        }

        $newMuteStatus = !$participant->pivot->is_muted;

        $conversation->participants()
            ->updateExistingPivot($user->id, ['is_muted' => $newMuteStatus]);

        return $newMuteStatus;
    }

    /**
     * React to a message
     */
    public function reactToMessage(Message $message, User $user, string $reaction): void
    {
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
