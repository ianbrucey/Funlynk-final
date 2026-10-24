<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\Conversation;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ConversationPolicy
{
    /**
     * Determine whether the user can view the conversation.
     * User must be a participant and request must not be declined.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        $subject = $conversation->conversationable;

        if ($subject instanceof Post || $subject instanceof Activity) {
            return Gate::forUser($user)->allows('view', $subject);
        }

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
        $subject = $conversation->conversationable;

        if ($subject instanceof Post) {
            if (! Gate::forUser($user)->allows('view', $subject)) {
                return false;
            }

            return $subject->group_id === null
                || $subject->group->members()->where('user_id', $user->id)->exists();
        }

        if ($subject instanceof Activity) {
            return Gate::forUser($user)->allows('view', $subject);
        }

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

    /**
     * Who may pin a message: the post owner, event host or group admin
     * (whoever may update the thing the chat belongs to), or the group admin
     * for a group chat. Direct messages have no pin.
     */
    public function pin(User $user, Conversation $conversation): bool
    {
        $subject = $conversation->conversationable;

        if ($subject instanceof Post || $subject instanceof Activity) {
            return Gate::forUser($user)->allows('update', $subject);
        }

        if ($conversation->group_id !== null && $conversation->group !== null) {
            return Gate::forUser($user)->allows('update', $conversation->group);
        }

        return false;
    }
}
