<?php

namespace App\Livewire\DirectMessages;

use App\Models\User;
use App\Services\MessageRequestService;
use Livewire\Component;

class InboxIcon extends Component
{
    public int $unreadInboxCount = 0;

    public int $unreadRequestsCount = 0;

    public function getListeners()
    {
        $userId = auth()->id();

        return [
            "echo-private:user.{$userId},DirectMessageReceived" => 'onMessageReceived',
            "echo-private:user.{$userId},MessageRequestReceived" => 'onRequestReceived',
            'messageReceived' => 'loadCounts',
            'requestReceived' => 'loadCounts',
        ];
    }

    public function onMessageReceived($event): void
    {
        $this->loadCounts();
    }

    public function onRequestReceived($event): void
    {
        $this->loadCounts();
    }

    public function mount(): void
    {
        $this->loadCounts();
    }

    public function loadCounts(): void
    {
        $service = app(MessageRequestService::class);
        /** @var User $user */
        $user = auth()->user();

        // Get unread count from inbox (accepted conversations)
        $this->unreadInboxCount = $service->getDirectInbox($user)
            ->filter(function ($conversation) use ($user) {
                $participant = $conversation->participants()
                    ->where('user_id', $user->id)
                    ->first();

                return $participant && $participant->pivot->unread_count > 0;
            })
            ->count();

        // Get pending requests count
        $this->unreadRequestsCount = $service->getUnreadRequestCount($user);
    }

    public function getTotalUnreadCount(): int
    {
        return $this->unreadInboxCount + $this->unreadRequestsCount;
    }

    public function render()
    {
        return view('livewire.direct-messages.inbox-icon');
    }
}
