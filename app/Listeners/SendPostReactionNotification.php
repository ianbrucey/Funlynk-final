<?php

namespace App\Listeners;

use App\Events\PostReacted;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPostReactionNotification
{
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
    public function handle(PostReacted $event): void
    {
        // Don't notify the user if they reacted to their own post
        if ($event->reaction->user_id === $event->post->user_id) {
            return;
        }

        Notification::create([
            'user_id' => $event->post->user_id,
            'type' => 'post_reaction',
            'title' => "{$event->reaction->user->name} reacted to your post",
            'message' => "Someone is down for \"{$event->post->title}\"",
            'data' => [
                'post_id' => $event->post->id,
                'reactor_id' => $event->reaction->user_id,
                'reaction_type' => $event->reaction->reaction_type,
            ],
            'delivery_method' => 'in_app',
            'delivery_status' => 'sent',
        ]);
    }
}
