<?php

namespace App\Listeners;

use App\Events\PostConvertedToEvent;

class NotifyInterestedUsers
{
    /**
     * Handle the event.
     *
     * Note: This listener will be fully implemented in A5 with batched notifications.
     * For now, it serves as a placeholder to complete the event system architecture.
     */
    public function handle(PostConvertedToEvent $event): void
    {
        // Batched notification logic will be implemented in A5
        // This ensures the event system is properly wired up
    }
}
