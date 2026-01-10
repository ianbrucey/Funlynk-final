<?php

namespace App\Livewire\DirectMessages;

use App\Services\MessageRequestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class InboxList extends Component
{
    use WithPagination;

    public $selectedConversationId = null;

    public string $search = '';

    protected $queryString = ['search'];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function selectConversation($conversationId)
    {
        $this->selectedConversationId = $conversationId;
        $this->dispatch('conversation-selected', conversationId: $conversationId);
    }

    public function render()
    {
        $messageRequestService = app(MessageRequestService::class);
        $conversationsQuery = $messageRequestService->getDirectInbox(Auth::user());

        // Apply search filter
        if ($this->search) {
            $searchTerm = strtolower($this->search);
            $conversationsQuery = $conversationsQuery->filter(function ($conversation) use ($searchTerm) {
                $otherUser = $conversation->participants
                    ->where('id', '!=', Auth::id())
                    ->first();

                // Search by name or username
                return str_contains(strtolower($otherUser->name), $searchTerm)
                    || str_contains(strtolower($otherUser->username), $searchTerm);
            });
        }

        // Transform for pagination (simple slice-based pagination for collections)
        $perPage = 15;
        $page = $this->getPage();
        $total = $conversationsQuery->count();
        $conversationsSlice = $conversationsQuery->slice(($page - 1) * $perPage, $perPage);

        // Transform Conversation models to UI structure
        $conversations = $conversationsSlice->map(function ($conversation) {
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

            // Build avatar URL properly
            $avatarUrl = $otherUser->profile_image_url
                ? Storage::url($otherUser->profile_image_url)
                : 'https://ui-avatars.com/api/?name='.urlencode($otherUser->name).'&background=ec4899&color=fff';

            return [
                'id' => $conversation->id,
                'other_user' => [
                    'name' => $otherUser->name,
                    'username' => $otherUser->username,
                    'avatar' => $avatarUrl,
                ],
                'latest_message' => $latestMessage?->body ?? 'No messages yet',
                'last_message_at' => $conversation->last_message_at,
                'unread_count' => $unreadCount,
            ];
        })->values()->toArray();

        return view('livewire.direct-messages.inbox-list', [
            'conversations' => $conversations,
            'hasMorePages' => ($page * $perPage) < $total,
            'totalConversations' => $total,
        ]);
    }
}
