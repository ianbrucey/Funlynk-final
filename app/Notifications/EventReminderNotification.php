<?php

namespace App\Notifications;

use App\Channels\CustomDatabaseChannel;
use App\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Activity $activity;

    protected int $minutesBefore;

    /**
     * Create a new notification instance.
     */
    public function __construct(Activity $activity, int $minutesBefore = 30)
    {
        $this->activity = $activity;
        $this->minutesBefore = $minutesBefore;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [CustomDatabaseChannel::class];
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $timeText = $this->minutesBefore >= 60
            ? ($this->minutesBefore / 60).' hour'.($this->minutesBefore > 60 ? 's' : '')
            : $this->minutesBefore.' minutes';

        return [
            'title' => "⏰ {$this->activity->title} starts in {$timeText}!",
            'message' => "📍 {$this->activity->location_name}",
            'type' => 'event_reminder',
            'delivery_method' => 'in_app',
            'data' => [
                'activity_id' => $this->activity->id,
                'group_id' => $this->activity->group_id,
                'start_time' => $this->activity->start_time->toIso8601String(),
                'location_name' => $this->activity->location_name,
                'action_url' => route('events.show', $this->activity),
            ],
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $timeText = $this->minutesBefore >= 60
            ? ($this->minutesBefore / 60).' hour'.($this->minutesBefore > 60 ? 's' : '')
            : $this->minutesBefore.' minutes';

        return [
            'title' => "⏰ {$this->activity->title} starts in {$timeText}!",
            'message' => "📍 {$this->activity->location_name}",
            'type' => 'event_reminder',
            'activity_id' => $this->activity->id,
            'group_id' => $this->activity->group_id,
            'start_time' => $this->activity->start_time->toIso8601String(),
            'action_url' => route('events.show', $this->activity),
        ];
    }
}
