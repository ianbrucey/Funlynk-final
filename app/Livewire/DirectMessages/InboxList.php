<?php

namespace App\Livewire\DirectMessages;

use App\Services\MessageRequestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class InboxList extends Component
{
    public $conversations = [];

    public $selectedConversationId = null;

    public function mount()
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        $messageRequestService = app(MessageRequestService::class);
        $conversations = $messageRequestService->getDirectInbox(Auth::user());

        // Transform Conversation models to UI structure
        $this->conversations = $conversations->map(function ($conversation) {
            // Get the other user (not the current user)
            $otherUser = $conversation->participants
                ->where('id', '!=', Auth::id())
                ->first();

            // Get latest message
            $latestMessage = $conversation->latestMessage;

            // Calculate unread count (messages after last_read_at)
            $lastReadAt = $conversation->participants
                ->where('id', Auth::id())
                ->first()
                ->pivot
                ->last_read_at;

            $unreadCount = $conversation->messages()
                ->where('user_id', '!=', Auth::id())
                ->when($lastReadAt, fn ($q) => $q->where('created_at', '>', $lastReadAt))
                ->count();

            return [
                'id' => $conversation->id,
                'other_user' => [
                    'name' => $otherUser->name,
                    'username' => $otherUser->username,
                    'avatar' => $otherUser->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($otherUser->name).'&background=ec4899&color=fff',
                ],
                'latest_message' => $latestMessage?->content ?? 'No messages yet',
                'last_message_at' => $conversation->last_message_at,
                'unread_count' => $unreadCount,
            ];
        })->toArray();
    }

    public function selectConversation($conversationId)
    {
        $this->selectedConversationId = $conversationId;
        $this->dispatch('conversation-selected', conversationId: $conversationId);
    }

    public function render()
    {
        return view('livewire.direct-messages.inbox-list');
    }
}
