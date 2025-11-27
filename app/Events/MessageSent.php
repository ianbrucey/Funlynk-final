<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load(['user', 'replyTo.user']);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('conversation.' . $this->message->conversation_id);
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'user' => [
                'display_name' => $this->message->user->display_name ?? $this->message->user->username,
                'profile_image_url' => $this->message->user->profile_image_url,
            ],
            'body' => $this->message->body,
            'created_at' => $this->message->created_at->toISOString(),
            'is_mine' => $this->message->user_id === auth()->id(),
            'reply_to' => $this->message->replyTo ? [
                'id' => $this->message->replyTo->id,
                'body' => $this->message->replyTo->body,
                'user' => [
                    'display_name' => $this->message->replyTo->user->display_name ?? $this->message->replyTo->user->username,
                ],
            ] : null,
        ];
    }
}
