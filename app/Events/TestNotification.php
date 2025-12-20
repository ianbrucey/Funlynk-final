<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $userId,
        public string $message = 'Test notification from Reverb!'
    ) {
        $this->notification = [
            'type' => 'test',
            'subtype' => 'reverb_test',
            'data' => [
                'reactor_name' => 'System Test',
                'message' => $this->message,
            ],
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('user.' . $this->userId);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return $this->notification;
    }
}

