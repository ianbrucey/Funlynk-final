<?php

namespace App\Listeners;

use App\Events\PostConvertedToEvent;
use App\Mail\PostConvertedToEventMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyInterestedUsers implements ShouldQueue
{
    /**
     * Handle the event.
     * Sends in-app notifications and emails to all users who reacted to the post.
     */
    public function handle(PostConvertedToEvent $event): void
    {
        // Get all users who reacted with "I'm down" to the post
        $interestedUsers = $event->post->reactions()
            ->where('reaction_type', 'im_down')
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // Don't notify the post owner
        $interestedUsers = array_filter($interestedUsers, function ($userId) use ($event) {
            return $userId !== $event->post->user_id;
        });

        // Create notifications for each interested user
        foreach ($interestedUsers as $userId) {
            $user = User::find($userId);
            if (!$user) {
                continue;
            }

            // Skip if user has disabled all notifications
            if ($user->notification_preference === 'none') {
                continue;
            }

            $hostName = $event->post->user->display_name ?? $event->post->user->username;
            $price = $event->activity->price_cents ? ($event->activity->price_cents / 100) : 0;

            // Create in-app notification if not email-only
            if ($user->notification_preference !== 'email_only') {
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'post_converted_to_event',
                    'title' => '🎉 Post Became an Event!',
                    'message' => "{$hostName} created an event based on the post you were interested in.",
                    'data' => [
                        'post_id' => $event->post->id,
                        'post_title' => $event->post->title,
                        'activity_id' => $event->activity->id,
                        'activity_title' => $event->activity->title,
                        'host_name' => $hostName,
                        'start_time' => $event->activity->start_time->toIso8601String(),
                        'location' => $event->activity->location_name,
                        'price' => $price,
                        'is_free' => !$event->activity->is_paid,
                        'url' => route('activities.show', $event->activity->id),
                    ],
                    'delivery_method' => 'in_app',
                    'delivery_status' => 'sent',
                ]);
            }

            // Send email if user has enabled it
            if ($this->shouldSendEmail($user)) {
                try {
                    Mail::send(new PostConvertedToEventMail(
                        $user,
                        $event->post,
                        $event->activity,
                        $event->post->user,
                    ));
                } catch (\Exception $e) {
                    // Log error but don't fail the entire process
                    Log::error('Failed to send conversion email', [
                        'user_id' => $user->id,
                        'post_id' => $event->post->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Determine if email should be sent based on user preferences.
     */
    private function shouldSendEmail(User $user): bool
    {
        // Check overall notification preference
        if ($user->notification_preference === 'none') {
            return false;
        }

        // If preference is 'in_app_only', don't send email
        if ($user->notification_preference === 'in_app_only') {
            return false;
        }

        // Check specific email preference for post conversions
        return $user->email_on_post_converted ?? true;
    }
}
