```php
// app/Services/GroupChatService.php (lines 50-59)
    {
        $conversation = $group->conversation;

        if (! $conversation) {
            return collect(); // No chat yet, return empty collection
        }

        return $conversation->messages()->with('user')->latest()->limit($limit)->get()->reverse();
    }
```

```php
// tests/Feature/Feature/GroupChatServiceTest.php (lines 40-48)
        $messageContent = 'Hello group!';
        $message = $this->groupChatService->sendGroupMessage($this->group, $this->user, $messageContent);

        expect($message)->toBeInstanceOf(Message::class);
        expect($message->body)->toBe($messageContent);
        expect($message->user_id)->toBe($this->user->id);
        $this->group->load('conversation');
        expect($message->conversation_id)->toBe($this->group->conversation->id);
    });
```