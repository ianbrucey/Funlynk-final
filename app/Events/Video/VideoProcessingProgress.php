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
 * Fired periodically during video processing to report progress.
 * 
 * Use cases:
 * - Update progress bar in UI
 * - Monitor long-running jobs
 */
class VideoProcessingProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Video $video,
        public readonly int $progress,
        public readonly string $stage,
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
        return 'video.processing.progress';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'video_id' => $this->video->id,
            'progress' => $this->progress,
            'stage' => $this->stage,
            'message' => "Processing: {$this->stage} ({$this->progress}%)",
        ];
    }
}
