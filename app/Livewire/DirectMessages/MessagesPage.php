<?php

namespace App\Livewire\DirectMessages;

use App\Models\Conversation;
use App\Services\MessageRequestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MessagesPage extends Component
{
    public $activeTab = 'inbox'; // 'inbox' or 'requests'

    public $selectedConversationId = null;

    public $conversation = null;

    public $unreadRequestCount = 0;

    protected $queryString = ['activeTab'];

    public function mount($conversation = null)
    {
        // Load unread request count
        $this->loadUnreadRequestCount();

        // If conversation ID is passed in route, select it and load it
        if ($conversation) {
            $this->loadConversation($conversation);
        }

        // If route is /messages/requests, switch to requests tab
        if (request()->is('messages/requests')) {
            $this->activeTab = 'requests';
        }
    }

    private function loadUnreadRequestCount()
    {
        $messageRequestService = app(MessageRequestService::class);
        $this->unreadRequestCount = $messageRequestService->getUnreadRequestCount(Auth::user());
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedConversationId = null;
        $this->conversation = null;
    }

    protected function getListeners()
    {
        return [
            'conversation-selected' => 'onConversationSelected',
            'request-accepted' => 'onRequestAccepted',
        ];
    }

    public function onConversationSelected($conversationId)
    {
        $this->loadConversation($conversationId);
    }

    public function onRequestAccepted()
    {
        // Reload unread count
        $this->loadUnreadRequestCount();

        // Switch to inbox tab after accepting a request
        $this->activeTab = 'inbox';
    }

    private function loadConversation($conversationId)
    {
        try {
            $conversation = Conversation::findOrFail($conversationId);

            // Authorize - user must be able to view this conversation
            if (! Auth::user()->can('view', $conversation)) {
                session()->flash('error', 'You are not authorized to view this conversation');
                $this->selectedConversationId = null;
                $this->conversation = null;

                return;
            }

            $this->selectedConversationId = $conversationId;
            $this->conversation = $conversation;
        } catch (\Exception $e) {
            session()->flash('error', 'Conversation not found');
            $this->selectedConversationId = null;
            $this->conversation = null;
        }
    }

    public function render()
    {
        return view('livewire.direct-messages.messages-page')
            ->layout('components.galaxy-layout', ['title' => 'Messages']);
    }
}
