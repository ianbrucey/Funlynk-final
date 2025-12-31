<?php

namespace App\Events\Video;

use App\Models\Video;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when video processing completes successfully.
 * 
 * Use cases:
 * - Notify user video is ready
 * - Update feed with new video
 * - Trigger moderation check
 */
class VideoProcessingCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Video $video,
        public readonly array $availableQualities,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->video->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'video.processing.completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'video_id' => $this->video->id,
            'status' => 'ready',
            'thumbnail_url' => $this->video->thumbnail_url,
            'available_qualities' => $this->availableQualities,
            'duration' => $this->video->duration,
            'message' => 'Video ready to play!',
        ];
    }
}
