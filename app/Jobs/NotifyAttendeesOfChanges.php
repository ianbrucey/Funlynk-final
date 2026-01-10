<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\ActivityRefundWindow;
use App\Models\Notification;
use App\Models\Rsvp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyAttendeesOfChanges implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Activity $activity,
        public ActivityRefundWindow $refundWindow
    ) {
        //
    }

    /**
     * Execute the job.
     * Sends notifications to all paid attendees about significant changes.
     */
    public function handle(): void
    {
        // Get all paid RSVPs for this activity
        $paidRsvps = Rsvp::where('activity_id', $this->activity->id)
            ->where('is_paid', true)
            ->where('status', 'attending')
            ->with('user')
            ->get();

        $notificationCount = 0;

        foreach ($paidRsvps as $rsvp) {
            // Create notification for each attendee
            Notification::create([
                'user_id' => $rsvp->user_id,
                'type' => 'event_changes',
                'title' => 'Event Details Changed',
                'message' => "The host has made significant changes to \"{$this->activity->title}\". You have 72 hours to request a full refund if you're not satisfied.",
                'data' => [
                    'activity_id' => $this->activity->id,
                    'refund_window_id' => $this->refundWindow->id,
                    'changes' => $this->refundWindow->changes_summary,
                    'expires_at' => $this->refundWindow->expires_at->toIso8601String(),
                ],
                'action_url' => route('events.show', $this->activity),
            ]);

            $notificationCount++;
        }

        Log::info("Sent {$notificationCount} change notifications for activity {$this->activity->id}");
    }
}
