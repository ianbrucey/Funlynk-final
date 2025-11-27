<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Services\ChatService;
use Livewire\Component;

class ChatComponent extends Component
{
    public $conversationId;
    public $conversationable; // Post or Activity model
    public $newMessage = '';
    public $messages = [];
    public $replyingTo = null;

    protected ChatService $chatService;

    public function boot(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function mount($conversationId = null, $conversationable = null)
    {
        $this->conversationId = $conversationId;
        $this->conversationable = $conversationable;
        
        $this->loadMessages();
    }

    protected function loadMessages()
    {
        // If conversationable is provided, get or create conversation
        if ($this->conversationable) {
            $conversation = $this->chatService->getOrCreateConversation($this->conversationable);
            $this->conversationId = $conversation->id;
        }

        // If no conversation ID, show empty state
        if (!$this->conversationId) {
            $this->messages = [];
            return;
        }

        // Load messages from database
        $conversation = Conversation::find($this->conversationId);
        
        if (!$conversation) {
            $this->messages = [];
            return;
        }

        $dbMessages = $this->chatService->getMessages($conversation);

        // Transform to array format for view
        $this->messages = $dbMessages->map(function ($message) {
            return [
                'id' => $message->id,
                'user' => [
                    'display_name' => $message->user->display_name ?? $message->user->username,
                    'profile_image_url' => $message->user->profile_image_url,
                ],
                'body' => $message->body,
                'created_at' => $message->created_at,
                'is_mine' => $message->user_id === auth()->id(),
                'reply_to' => $message->replyTo ? [
                    'id' => $message->replyTo->id,
                    'body' => $message->replyTo->body,
                    'user' => [
                        'display_name' => $message->replyTo->user->display_name ?? $message->replyTo->user->username,
                    ],
                ] : null,
            ];
        })->toArray();

        // Mark as read
        if (auth()->check()) {
            $this->chatService->markAsRead($conversation, auth()->user());
        }
    }

    public function sendMessage()
    {
        if (trim($this->newMessage) === '') {
            return;
        }

        if (!auth()->check()) {
            return;
        }

        $conversation = Conversation::find($this->conversationId);

        if (!$conversation) {
            return;
        }

        // Send message via service
        $this->chatService->sendMessage(
            $conversation,
            auth()->user(),
            $this->newMessage,
            $this->replyingTo['id'] ?? null
        );

        // Reload messages
        $this->loadMessages();

        // Clear input
        $this->newMessage = '';
        $this->replyingTo = null;
    }

    public function replyTo($messageId)
    {
        $message = collect($this->messages)->firstWhere('id', $messageId);
        if ($message) {
            $this->replyingTo = [
                'id' => $message['id'],
                'body' => $message['body'],
                'user' => $message['user'],
            ];
        }
    }

    public function cancelReply()
    {
        $this->replyingTo = null;
    }

    public function render()
    {
        return view('livewire.chat.chat-component');
    }
}
