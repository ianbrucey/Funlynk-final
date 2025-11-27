<?php

namespace App\Events;

use App\Models\Post;
use App\Models\PostReaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostReacted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Post $post;

    public PostReaction $reaction;

    /** @var array<string, mixed> */
    public array $eligibility;

    /**
     * @param  array<string, mixed>  $eligibility
     */
    public function __construct(Post $post, PostReaction $reaction, array $eligibility)
    {
        $this->post = $post;
        $this->reaction = $reaction;
        $this->eligibility = $eligibility;
    }

    public function broadcastOn(): Channel
    {
        // Broadcast to the post owner's user channel
        return new Channel("user.{$this->post->user_id}");
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }

    public function broadcastWith(): array
    {
        $reactor = $this->reaction->user;
        
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'post_reaction',
            'subtype' => $this->reaction->reaction_type,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'post_id' => $this->post->id,
                'post_title' => $this->post->title,
                'reactor_id' => $reactor->id,
                'reactor_name' => $reactor->name,
                'reactor_avatar' => $reactor->avatar_url ?? null,
                'reaction_count' => $this->post->reaction_count,
                'conversion_eligible' => $this->eligibility['eligible'],
            ],
            'actions' => [
                ['label' => 'View Post', 'route' => "/posts/{$this->post->id}"],
            ],
        ];
    }
}
