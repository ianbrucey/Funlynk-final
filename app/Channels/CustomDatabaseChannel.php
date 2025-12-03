<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;

class CustomDatabaseChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $data = $notification->toDatabase($notifiable);

        $notifiable->notifications()->create([
            'id' => $notification->id,
            'type' => get_class($notification),
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'data' => $data['data'] ?? [],
            'delivery_method' => $data['delivery_method'] ?? 'in_app',
            'delivery_status' => 'sent',
            'is_read' => false,
        ]);
    }
}

