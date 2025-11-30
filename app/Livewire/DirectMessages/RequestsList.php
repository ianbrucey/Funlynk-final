<?php

namespace App\Livewire\DirectMessages;

use App\Models\Conversation;
use App\Services\MessageRequestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RequestsList extends Component
{
    public $requests = [];

    public function mount()
    {
        $this->loadRequests();
    }

    public function loadRequests()
    {
        $messageRequestService = app(MessageRequestService::class);
        $conversations = $messageRequestService->getMessageRequests(Auth::user());

        // Transform Conversation models to UI structure
        $this->requests = $conversations->map(function ($conversation) {
            // Get the sender (the other user who is NOT the current user)
            $sender = $conversation->participants
                ->where('id', '!=', Auth::id())
                ->first();

            // Get first message (latest message in this case since it's a new request)
            $firstMessage = $conversation->latestMessage;

            return [
                'id' => $conversation->id,
                'sender' => [
                    'name' => $sender->name,
                    'username' => $sender->username,
                    'avatar' => $sender->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($sender->name).'&background=ec4899&color=fff',
                ],
                'first_message' => $firstMessage?->content ?? 'No message',
                'received_at' => $conversation->last_message_at,
            ];
        })->toArray();
    }

    public function acceptRequest($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Authorize
        if (! Auth::user()->can('acceptRequest', $conversation)) {
            session()->flash('error', 'You are not authorized to accept this request');

            return;
        }

        $messageRequestService = app(MessageRequestService::class);
        $messageRequestService->acceptRequest($conversation, Auth::user());

        // Remove from list
        $this->requests = collect($this->requests)
            ->reject(fn ($req) => $req['id'] === $conversationId)
            ->values()
            ->toArray();

        session()->flash('message', 'Message request accepted!');
        $this->dispatch('request-accepted');
    }

    public function declineRequest($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Authorize
        if (! Auth::user()->can('declineRequest', $conversation)) {
            session()->flash('error', 'You are not authorized to decline this request');

            return;
        }

        $messageRequestService = app(MessageRequestService::class);
        $messageRequestService->declineRequest($conversation, Auth::user());

        // Remove from list
        $this->requests = collect($this->requests)
            ->reject(fn ($req) => $req['id'] === $conversationId)
            ->values()
            ->toArray();

        session()->flash('message', 'Message request declined.');
    }

    public function render()
    {
        return view('livewire.direct-messages.requests-list');
    }
}
