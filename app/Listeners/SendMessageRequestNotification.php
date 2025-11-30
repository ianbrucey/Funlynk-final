<?php

namespace App\Listeners;

use App\Events\MessageRequestReceived;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMessageRequestNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageRequestReceived $event): void
    {
        // Get recipient's notification preferences
        $recipient = $event->recipient;
        $preferences = $recipient->notification_preferences ?? [];

        // Check if user wants message request notifications
        if (isset($preferences['message_requests']) && $preferences['message_requests'] === false) {
            return;
        }

        // Create in-app notification
        Notification::create([
            'user_id' => $recipient->id,
            'type' => 'message_request',
            'title' => "Message request from {$event->sender->display_name}",
            'message' => $event->message->body,
            'data' => [
                'conversation_id' => $event->conversation->id,
                'message_id' => $event->message->id,
                'sender_id' => $event->sender->id,
                'sender_name' => $event->sender->display_name ?? $event->sender->username,
                'sender_avatar' => $event->sender->profile_image_url,
                'url' => \Illuminate\Support\Facades\Route::has('messages.requests')
                    ? route('messages.requests')
                    : '/messages/requests',
            ],
            'delivery_method' => 'in_app',
            'delivery_status' => 'sent',
        ]);

        // TODO: Add push notification support when implemented
        // if (isset($preferences['push_notifications']) && $preferences['push_notifications'] === true) {
        //     // Send push notification
        // }
    }
}
