<?php

namespace App\Listeners;

use App\Events\PostReacted;
use App\Models\Notification;

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

        $reactor = $event->reaction->user;

        Notification::create([
            'user_id' => $event->post->user_id,
            'type' => 'post_reaction',
            'title' => "{$reactor->name} reacted to your post",
            'message' => "Someone is down for \"{$event->post->title}\"",
            'data' => [
                'post_id' => $event->post->id,
                'post_title' => $event->post->title,
                'post_location' => $event->post->location_name,
                'post_description' => $event->post->description ?
                    \Illuminate\Support\Str::limit($event->post->description, 100) : null,
                'reactor_id' => $reactor->id,
                'reactor_name' => $reactor->display_name ?? $reactor->name,
                'reactor_avatar' => $reactor->profile_image_url,
                'reaction_count' => $event->post->reaction_count,
                'reaction_type' => $event->reaction->reaction_type,
                'url' => route('posts.show', $event->post->id),
            ],
            'delivery_method' => 'in_app',
            'delivery_status' => 'sent',
        ]);
    }
}
