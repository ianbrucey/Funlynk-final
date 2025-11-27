<?php

namespace App\Livewire\Chat;

use Livewire\Component;

class ChatComponent extends Component
{
    public $conversationId;
    public $conversationable; // Post or Activity model
    public $newMessage = '';
    public $messages = [];
    public $replyingTo = null;

    public function mount($conversationId = null, $conversationable = null)
    {
        $this->conversationId = $conversationId;
        $this->conversationable = $conversationable;
        
        // Load mock data for UI iteration
        $this->loadMockMessages();
    }

    protected function loadMockMessages()
    {
        // Mock data for UI development
        $this->messages = [
            [
                'id' => '1',
                'user' => ['display_name' => 'Alice', 'profile_image_url' => null],
                'body' => 'Hey! Anyone down for tennis this evening?',
                'created_at' => now()->subMinutes(30),
                'is_mine' => false,
                'reply_to' => null,
            ],
            [
                'id' => '2',
                'user' => ['display_name' => 'You', 'profile_image_url' => null],
                'body' => 'I\'m in! What time?',
                'created_at' => now()->subMinutes(25),
                'is_mine' => true,
                'reply_to' => null,
            ],
            [
                'id' => '3',
                'user' => ['display_name' => 'Bob', 'profile_image_url' => null],
                'body' => 'Around 6pm works for me',
                'created_at' => now()->subMinutes(20),
                'is_mine' => false,
                'reply_to' => [
                    'id' => '2',
                    'body' => 'I\'m in! What time?',
                    'user' => ['display_name' => 'You'],
                ],
            ],
        ];
    }

    public function sendMessage()
    {
        if (trim($this->newMessage) === '') {
            return;
        }

        // Mock: Add message to array
        $this->messages[] = [
            'id' => (string) (count($this->messages) + 1),
            'user' => ['display_name' => 'You', 'profile_image_url' => null],
            'body' => $this->newMessage,
            'created_at' => now(),
            'is_mine' => true,
            'reply_to' => $this->replyingTo,
        ];

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
