<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\User;
use App\Services\ChatNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends in-app chat notifications for a new message or a new pin (spec 013).
 * Queued so sending a message never waits on fan-out.
 */
class NotifyChatParticipants implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        public string $messageId,
        public ?string $pinnedByUserId = null,
    ) {}

    public function handle(ChatNotifier $notifier): void
    {
        $message = Message::query()->with(['conversation.conversationable', 'conversation.group', 'user'])->find($this->messageId);

        if (! $message || ! $message->conversation || ! $message->user) {
            return;
        }

        if ($this->pinnedByUserId !== null) {
            $actor = User::query()->find($this->pinnedByUserId);

            if ($actor && $message->conversation->pinned_message_id === $message->id) {
                $notifier->notifyForPin($message->conversation, $message, $actor);
            }

            return;
        }

        $notifier->notifyForMessage($message);
    }
}
