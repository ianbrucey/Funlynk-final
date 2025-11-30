<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostAutoConverted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Post $post,
        public array $eligibility
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("user.{$this->post->user_id}");
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'post_conversion',
            'subtype' => 'auto_converted',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'post_id' => $this->post->id,
                'post_title' => $this->post->title,
                'reaction_count' => $this->eligibility['reaction_count'],
                'threshold' => $this->eligibility['threshold_strong'],
            ],
            'actions' => [
                ['label' => 'View Event', 'route' => "/events/{$this->post->converted_to_activity_id}"],
            ],
        ];
    }
}
