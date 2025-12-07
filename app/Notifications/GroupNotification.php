<?php

namespace App\Notifications;

use App\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GroupNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected string $title,
        protected string $message,
        protected string $type,
        protected string $groupId,
        protected ?string $relatedId = null,
        protected ?string $actorId = null,
        protected ?string $actorName = null,
        protected ?string $actorAvatar = null,
    ) {}

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
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'delivery_method' => 'in_app',
            'data' => [
                'group_id' => $this->groupId,
                'related_id' => $this->relatedId,
                'actor_id' => $this->actorId,
                'actor_name' => $this->actorName,
                'actor_avatar' => $this->actorAvatar,
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
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'group_id' => $this->groupId,
            'related_id' => $this->relatedId,
            'actor_id' => $this->actorId,
            'actor_name' => $this->actorName,
            'actor_avatar' => $this->actorAvatar,
        ];
    }
}
