<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class MessageRequestService
{
    /**
     * Accept a message request
     */
    public function acceptRequest(Conversation $conversation, User $recipient): void
    {
        // Update recipient's pivot status
        $conversation->participants()
            ->updateExistingPivot($recipient->id, [
                'request_status' => 'accepted',
            ]);

        // Update conversation metadata
        $metadata = $conversation->metadata ?? [];
        $metadata['is_request'] = false;
        $conversation->update(['metadata' => $metadata]);

        // Notify sender
        $sender = $conversation->participants()
            ->where('user_id', '!=', $recipient->id)
            ->first();

        if ($sender) {
            Notification::create([
                'user_id' => $sender->id,
                'type' => 'message_request_accepted',
                'title' => "{$recipient->display_name} accepted your message",
                'message' => "You can now chat with {$recipient->display_name}",
                'data' => [
                    'conversation_id' => $conversation->id,
                    'recipient_id' => $recipient->id,
                    'recipient_name' => $recipient->display_name ?? $recipient->username,
                    'recipient_avatar' => $recipient->profile_image_url,
                    'url' => \Illuminate\Support\Facades\Route::has('messages.show')
                        ? route('messages.show', $conversation->id)
                        : '/messages/'.$conversation->id,
                ],
                'delivery_method' => 'in_app',
                'delivery_status' => 'sent',
            ]);
        }
    }

    /**
     * Decline a message request
     */
    public function declineRequest(Conversation $conversation, User $recipient): void
    {
        // Update recipient's pivot status (hides from their view)
        $conversation->participants()
            ->updateExistingPivot($recipient->id, [
                'request_status' => 'declined',
            ]);

        // No notification to sender (privacy)
    }

    /**
     * Get all pending message requests for a user
     */
    public function getMessageRequests(User $user): Collection
    {
        return Conversation::where('type', 'private')
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('request_status', 'pending');
            })
            ->with(['participants', 'latestMessage.user'])
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    /**
     * Get direct inbox (accepted conversations)
     */
    public function getDirectInbox(User $user): Collection
    {
        return Conversation::where('type', 'private')
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where(function ($q) {
                        $q->whereNull('request_status')
                            ->orWhere('request_status', 'accepted');
                    });
            })
            ->with(['participants', 'latestMessage.user'])
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    /**
     * Get unread message request count
     */
    public function getUnreadRequestCount(User $user): int
    {
        return Conversation::where('type', 'private')
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('request_status', 'pending')
                    ->whereNull('last_read_at');
            })
            ->count();
    }
}
