# F05 Private Direct Messaging - Implementation Guide

## Code Examples and Technical Details

### 1. ChatService DM Methods (T01)

#### Creating DM Conversations

```php
// app/Services/ChatService.php

/**
 * Create or get a direct message conversation between two users
 */
public function createDirectMessageConversation(User $sender, User $recipient): Conversation
{
    // Check if conversation already exists (either direction)
    $existingConversation = Conversation::where('type', 'private')
        ->whereNull('conversationable_type')
        ->whereNull('conversationable_id')
        ->whereHas('participants', function ($query) use ($sender) {
            $query->where('user_id', $sender->id);
        })
        ->whereHas('participants', function ($query) use ($recipient) {
            $query->where('user_id', $recipient->id);
        })
        ->first();

    if ($existingConversation) {
        return $existingConversation;
    }

    // Determine if this should be a message request
    $isRequest = $this->shouldRouteToRequests($sender, $recipient);

    // Create new DM conversation
    $conversation = Conversation::create([
        'type' => 'private',
        'conversationable_type' => null,
        'conversationable_id' => null,
        'last_message_at' => now(),
        'metadata' => [
            'is_dm' => true,
            'is_request' => $isRequest,
            'recipient_id' => $recipient->id,
        ],
    ]);

    // Add both users as participants
    $conversation->participants()->attach($sender->id, [
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'role' => 'member',
        'is_muted' => false,
        'last_read_at' => now(),
        'request_status' => null, // Sender doesn't have request status
    ]);

    $conversation->participants()->attach($recipient->id, [
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'role' => 'member',
        'is_muted' => false,
        'last_read_at' => null, // Recipient hasn't read yet
        'request_status' => $isRequest ? 'pending' : null,
    ]);

    return $conversation;
}

/**
 * Check if two users are mutual followers
 */
public function isMutualFollower(User $userA, User $userB): bool
{
    return $userA->mutuals()->where('users.id', $userB->id)->exists();
}

/**
 * Determine if message should route to requests inbox
 */
public function shouldRouteToRequests(User $sender, User $recipient): bool
{
    return !$this->isMutualFollower($sender, $recipient);
}
```

#### Modified sendMessage Method

```php
// app/Services/ChatService.php

public function sendMessage(
    Conversation $conversation,
    User $user,
    string $body,
    ?string $replyToMessageId = null
): Message {
    // Existing logic...
    $this->addParticipant($conversation, $user);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => $body,
        'reply_to_message_id' => $replyToMessageId,
        'type' => 'text',
    ]);

    $conversation->update(['last_message_at' => now()]);

    // NEW: Fire appropriate event based on conversation type and request status
    if ($conversation->type === 'private') {
        $isRequest = $conversation->metadata['is_request'] ?? false;
        
        if ($isRequest) {
            event(new \App\Events\MessageRequestReceived($message, $conversation));
        } else {
            event(new \App\Events\DirectMessageReceived($message, $conversation));
        }
    }

    broadcast(new \App\Events\MessageSent($message))->toOthers();

    return $message;
}
```

---

### 2. Database Migrations (T02)

#### Add request_status to conversation_participants

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_request_status_to_conversation_participants_table.php

public function up(): void
{
    Schema::table('conversation_participants', function (Blueprint $table) {
        $table->enum('request_status', ['pending', 'accepted', 'declined'])
            ->nullable()
            ->after('last_read_at')
            ->index();
    });
}

public function down(): void
{
    Schema::table('conversation_participants', function (Blueprint $table) {
        $table->dropColumn('request_status');
    });
}
```

#### Update ConversationParticipant Model

```php
// app/Models/ConversationParticipant.php

protected function casts(): array
{
    return [
        'is_muted' => 'boolean',
        'last_read_at' => 'datetime',
        'request_status' => 'string', // or create enum cast in Laravel 12
    ];
}
```

---

### 3. MessageRequestService (T03)

```php
// app/Services/MessageRequestService.php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class MessageRequestService
{
    /**
     * Accept a message request
     */
    public function acceptRequest(Conversation $conversation, User $recipient): void
    {
        // Update recipient's pivot status
        $conversation->participants()
            ->updateExistingPivot($recipient->id, [
                'request_status' => 'accepted',
            ]);

        // Update conversation metadata
        $metadata = $conversation->metadata ?? [];
        $metadata['is_request'] = false;
        $conversation->update(['metadata' => $metadata]);

        // Notify sender
        $sender = $conversation->participants()
            ->where('user_id', '!=', $recipient->id)
            ->first();

        if ($sender) {
            Notification::create([
                'user_id' => $sender->id,
                'type' => 'message_request_accepted',
                'title' => "{$recipient->display_name} accepted your message",
                'message' => "You can now chat with {$recipient->display_name}",
                'data' => [
                    'conversation_id' => $conversation->id,
                    'recipient_id' => $recipient->id,
                    'recipient_name' => $recipient->display_name ?? $recipient->username,
                    'recipient_avatar' => $recipient->profile_image_url,
                    'url' => route('messages.show', $conversation->id),
                ],
                'delivery_method' => 'in_app',
                'delivery_status' => 'sent',
            ]);
        }
    }

    /**
     * Decline a message request
     */
    public function declineRequest(Conversation $conversation, User $recipient): void
    {
        // Update recipient's pivot status (hides from their view)
        $conversation->participants()
            ->updateExistingPivot($recipient->id, [
                'request_status' => 'declined',
            ]);

        // No notification to sender (privacy)
    }

    /**
     * Get all pending message requests for a user
     */
    public function getMessageRequests(User $user): Collection
    {
        return Conversation::where('type', 'private')
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('request_status', 'pending');
            })
            ->with(['participants', 'latestMessage.user'])
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    /**
     * Get direct inbox (accepted conversations)
     */
    public function getDirectInbox(User $user): Collection
    {
        return Conversation::where('type', 'private')
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where(function ($q) {
                        $q->whereNull('request_status')
                          ->orWhere('request_status', 'accepted');
                    });
            })
            ->with(['participants', 'latestMessage.user'])
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    /**
     * Get unread message request count
     */
    public function getUnreadRequestCount(User $user): int
    {
        return Conversation::where('type', 'private')
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('request_status', 'pending')
                    ->whereNull('last_read_at');
            })
            ->count();
    }
}
```

---

## Summary

This implementation guide provides detailed code examples for implementing the private direct messaging feature. Key points:

1. **Reuses Existing Architecture**: Builds on the unified chat system (Conversation, Message, ChatComponent)
2. **Mutual Follow Detection**: Uses existing `User::mutuals()` relationship for efficient checking
3. **Message Routing**: Automatically routes to inbox or requests based on follow status
4. **Privacy-First**: Declined requests hidden from recipient, no notification to sender
5. **Real-time Updates**: Leverages existing Laravel Echo + Reverb infrastructure
6. **Galaxy Theme**: All UI components follow the established design system

**Next Steps**: Use these examples as templates when implementing each task (T01-T09) in the main README.md.
