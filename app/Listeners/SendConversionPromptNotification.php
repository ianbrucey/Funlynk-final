<?php

namespace App\Listeners;

use App\Events\PostAutoConverted;
use App\Events\PostConversionPrompted;
use App\Events\PostConversionSuggested;
use App\Models\Notification;

class SendConversionPromptNotification
{
    /**
     * Handle the event.
     * Accepts PostConversionPrompted, PostConversionSuggested, and PostAutoConverted events
     */
    public function handle(PostConversionPrompted|PostConversionSuggested|PostAutoConverted $event): void
    {
        // Determine threshold level
        if ($event instanceof PostConversionPrompted) {
            $threshold = $event->threshold; // 'soft' or 'strong'
            $reactionCount = $event->post->reaction_count;
        } elseif ($event instanceof PostAutoConverted) {
            // PostAutoConverted - strong threshold
            $reactionCount = $event->eligibility['reaction_count'];
            $threshold = 'strong';
        } else {
            // PostConversionSuggested - soft threshold
            $reactionCount = $event->eligibility['reaction_count'];
            $threshold = 'soft';
        }

        Notification::create([
            'user_id' => $event->post->user_id,
            'type' => 'post_conversion_prompt',
            'title' => 'Your post is getting attention!',
            'message' => $this->getMessage($threshold, $reactionCount),
            'data' => [
                'post_id' => $event->post->id,
                'post_title' => $event->post->title,
                'reaction_count' => $reactionCount,
                'threshold' => $threshold,
                'url' => route('posts.show', $event->post->id),
            ],
            'delivery_method' => 'in_app',
            'delivery_status' => 'sent',
        ]);
    }

    /**
     * Get notification message based on threshold
     */
    protected function getMessage(string $threshold, int $count): string
    {
        if ($threshold === 'strong') {
            return "🔥 {$count}+ people want to join! Turn this into an event now.";
        }

        return "🎉 {$count} people are interested! Consider creating an event.";
    }
}
