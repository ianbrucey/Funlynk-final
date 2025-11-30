<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Determine whether the user can view the conversation.
     * User must be a participant and request must not be declined.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            return false;
        }

        // Cannot view declined requests
        if ($participant->pivot->request_status === 'declined') {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can send a message in the conversation.
     * User must be a participant and request must be accepted or null.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            return false;
        }

        // Cannot send messages to declined requests
        if ($participant->pivot->request_status === 'declined') {
            return false;
        }

        // For DM conversations, check if this is a pending request
        if ($conversation->type === 'private' && $conversation->metadata['is_request'] ?? false) {
            // Sender can always send messages (they initiated the request)
            // Recipient can only send if they've accepted
            $isRecipient = $participant->pivot->request_status === 'pending';

            if ($isRecipient) {
                return false; // Recipient must accept first
            }
        }

        return true;
    }

    /**
     * Determine whether the user can accept a message request.
     * User must be the recipient with pending status.
     */
    public function acceptRequest(User $user, Conversation $conversation): bool
    {
        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            return false;
        }

        // Only pending requests can be accepted
        return $participant->pivot->request_status === 'pending';
    }

    /**
     * Determine whether the user can decline a message request.
     * User must be the recipient with pending status.
     */
    public function declineRequest(User $user, Conversation $conversation): bool
    {
        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            return false;
        }

        // Only pending requests can be declined
        return $participant->pivot->request_status === 'pending';
    }
}
