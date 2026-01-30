<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Notifications\EventReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendEventReminders implements ShouldQueue
{
    use Queueable;

    /**
     * The number of minutes before the event to send reminders.
     */
    protected int $minutesBefore;

    /**
     * Create a new job instance.
     */
    public function __construct(int $minutesBefore = 30)
    {
        $this->minutesBefore = $minutesBefore;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Find events starting within the reminder window
        // We look for events starting between now + (minutesBefore - 5) and now + (minutesBefore + 5)
        // This gives us a 10-minute window to catch events when the job runs every 10 minutes
        $windowStart = now()->addMinutes($this->minutesBefore - 5);
        $windowEnd = now()->addMinutes($this->minutesBefore + 5);

        $upcomingEvents = Activity::query()
            ->where('start_time', '>=', $windowStart)
            ->where('start_time', '<=', $windowEnd)
            ->where('status', '!=', 'cancelled')
            ->whereHas('rsvps', fn ($q) => $q->where('status', 'going'))
            ->with(['rsvps.user', 'group'])
            ->get();

        Log::info('SendEventReminders: Checking for events', [
            'window_start' => $windowStart->toDateTimeString(),
            'window_end' => $windowEnd->toDateTimeString(),
            'events_found' => $upcomingEvents->count(),
        ]);

        foreach ($upcomingEvents as $event) {
            $this->sendRemindersForEvent($event);
        }
    }

    /**
     * Send reminders to all users who RSVPed to the event.
     */
    protected function sendRemindersForEvent(Activity $event): void
    {
        $goingRsvps = $event->rsvps->where('status', 'going');

        Log::info('SendEventReminders: Sending reminders for event', [
            'event_id' => $event->id,
            'event_title' => $event->title,
            'rsvp_count' => $goingRsvps->count(),
        ]);

        foreach ($goingRsvps as $rsvp) {
            // Skip if user is the host (they don't need a reminder for their own event)
            if ($rsvp->user_id === $event->host_id) {
                continue;
            }

            // Check if we already sent a reminder for this event to this user
            // by checking if a notification with this event_id exists in the last hour
            $alreadySent = $rsvp->user->notifications()
                ->where('type', EventReminderNotification::class)
                ->where('created_at', '>=', now()->subHour())
                ->whereJsonContains('data->activity_id', $event->id)
                ->exists();

            if ($alreadySent) {
                Log::debug('SendEventReminders: Skipping duplicate reminder', [
                    'user_id' => $rsvp->user_id,
                    'event_id' => $event->id,
                ]);
                continue;
            }

            // Send the notification
            $rsvp->user->notify(new EventReminderNotification($event, $this->minutesBefore));

            Log::info('SendEventReminders: Sent reminder', [
                'user_id' => $rsvp->user_id,
                'event_id' => $event->id,
            ]);
        }
    }
}
