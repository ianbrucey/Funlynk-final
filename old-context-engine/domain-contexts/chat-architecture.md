# Unified Chat Architecture

## Overview

FunLynk uses a **unified chat architecture** that replaces traditional comment systems with a real-time, conversation-based approach. This architecture treats all communication—whether on posts, activities, or direct messages—as conversations with participants, enabling a consistent and dynamic user experience.

## Why Unified Chat?

### The Problem with Traditional Comments

Traditional comment systems create architectural duplication and inconsistent user experiences:

1. **Code Duplication**: Separate implementations for:
   - Post comments
   - Activity comments  
   - Direct messages (DMs)
   - Each requiring its own models, controllers, views, and real-time logic

2. **Inconsistent Features**: Different capabilities across contexts:
   - Comments might have threading but not reactions
   - DMs might have read receipts but comments don't
   - Real-time updates work differently in each context

3. **Poor Scalability**: Adding new features (reactions, threading, read receipts) requires updating multiple systems

4. **Misaligned with FunLynk's Nature**: FunLynk is about spontaneous, ephemeral coordination. Traditional comments feel static and archival, not conversational.

### The Unified Chat Solution

By treating **all communication as conversations**, we achieve:

- **Single Source of Truth**: One `Conversation` model, one `Message` model, one `ChatComponent`
- **Consistent Features**: Threading, reactions, read receipts, real-time updates work everywhere
- **Easier Maintenance**: New features added once, available everywhere
- **Better UX**: Users learn one interaction pattern that works across the entire app
- **Aligned Philosophy**: Everything feels like a live, ephemeral conversation

## Architecture Overview

### Core Models

#### 1. Conversation Model (`app/Models/Conversation.php`)

The central model that represents any communication channel.

**Key Fields**:
- `id` (UUID): Primary key
- `type` (string): Conversation type - `private`, `group`, or `public`
  - `private`: 1-on-1 DMs between two users
  - `group`: Activity discussions (RSVPed users only)
  - `public`: Post discussions (anyone can join)
- `conversationable_type` (string): Polymorphic type (Post, Activity, or NULL for DMs)
- `conversationable_id` (UUID): Polymorphic ID
- `last_message_at` (timestamp): For sorting conversation lists
- `metadata` (JSON): Extensible data storage

**Key Relationships**:
```php
// Polymorphic - attached to Post or Activity
public function conversationable(): MorphTo

// Many-to-many with users (participants)
public function participants(): BelongsToMany
    ->withPivot(['id', 'role', 'is_muted', 'last_read_at'])

// One-to-many messages
public function messages(): HasMany

// Latest message for previews
public function latestMessage(): HasOne
```

#### 2. Message Model (`app/Models/Message.php`)

Represents individual messages within conversations.

**Key Fields**:
- `id` (UUID): Primary key
- `conversation_id` (UUID): Parent conversation
- `user_id` (UUID): Message sender
- `body` (text): Message content
- `type` (string): Message type - `text`, `image`, `system`
- `reply_to_message_id` (UUID): For threading/replies
- `attachment_path` (string): File attachments
- `metadata` (JSON): Extensible data
- `deleted_at` (timestamp): Soft deletes

**Key Relationships**:
```php
public function conversation(): BelongsTo
public function user(): BelongsTo
public function replyTo(): BelongsTo // Self-referencing for threading
public function reactions(): HasMany // MessageReaction model
```

#### 3. Conversation Participants (Pivot Table)

The `conversation_participants` table manages who can access each conversation.

**Key Fields**:
- `id` (UUID): Primary key (required for UUID pivot)
- `conversation_id` (UUID)
- `user_id` (UUID)
- `role` (string): `member` or `admin`
- `is_muted` (boolean): User muted this conversation
- `last_read_at` (timestamp): For unread indicators

### Service Layer

#### ChatService (`app/Services/ChatService.php`)

Centralized business logic for all chat operations.

**Key Methods**:

```php
// Get or create conversation for a Post/Activity
getOrCreateConversation(Model $conversationable): Conversation

// Send a message (handles participants, broadcasting, timestamps)
sendMessage(Conversation $conversation, User $user, string $body, ?string $replyToMessageId): Message

// Add user as participant (with role)
addParticipant(Conversation $conversation, User $user, string $role = 'member'): void

// Retrieve messages with relationships
getMessages(Conversation $conversation, int $limit = 50)

// Mark conversation as read
markAsRead(Conversation $conversation, User $user): void

// Toggle mute status
toggleMute(Conversation $conversation, User $user): bool

// React to message (toggle on/off)
reactToMessage(Message $message, User $user, string $reaction): void
```

**Conversation Type Logic**:
- **Posts** → `public` conversations (anyone viewing can join)
- **Activities** → `group` conversations (RSVPed users become participants)
- **DMs** → `private` conversations (exactly 2 participants)

### UI Layer

#### ChatComponent (`app/Livewire/Chat/ChatComponent.php`)

Single Livewire component used for all chat contexts.

**Usage Patterns**:

```blade
{{-- Post discussion --}}
<livewire:chat.chat-component :conversationable="$post" />

{{-- Activity discussion --}}
<livewire:chat.chat-component :conversationable="$activity" />

{{-- Direct message (by conversation ID) --}}
<livewire:chat.chat-component :conversationId="$conversationId" />
```

**Key Features**:
- **Auto-conversation creation**: If `conversationable` provided, gets or creates conversation
- **Real-time updates**: Listens to `MessageSent` event via Laravel Echo
- **Threading support**: Reply to specific messages with `replyTo()` method
- **Read receipts**: Automatically marks conversation as read when viewing
- **Optimistic UI**: Adds messages immediately, then syncs with server

**Real-time Broadcasting**:
```php
// Listens to: "echo:conversation.{$conversationId},MessageSent"
public function onMessageReceived($event)
{
    // Adds message to UI in real-time
}
```

## Integration with Posts and Activities

### Polymorphic Relationship Setup

Both `Post` and `Activity` models have a `morphOne` relationship:

```php
// app/Models/Post.php
public function conversation(): \Illuminate\Database\Eloquent\Relations\MorphOne
{
    return $this->morphOne(Conversation::class, 'conversationable');
}

// app/Models/Activity.php
public function conversation(): \Illuminate\Database\Eloquent\Relations\MorphOne
{
    return $this->morphOne(Conversation::class, 'conversationable');
}
```

### Usage in Views

**Post Detail Page** (`resources/views/livewire/posts/post-detail.blade.php`):

```blade
{{-- Discussion Section --}}
<div class="relative p-6 lg:p-8 glass-card lg:rounded-xl">
    <h3 class="text-xl font-bold text-white mb-6">Discussion</h3>

    {{-- Unified Chat Component --}}
    <livewire:chat.chat-component :conversationable="$post" />
</div>
```

**Activity Detail Page** (similar pattern):

```blade
<livewire:chat.chat-component :conversationable="$activity" />
```

### Automatic Participant Management

When a user interacts with a conversation:

1. **First message**: `ChatService::sendMessage()` calls `addParticipant()` automatically
2. **RSVP to activity**: Activity RSVP logic should add user as participant
3. **View post**: User can join public conversation by sending first message

## What Was Replaced

### Old Comment System

The traditional comment system included:

**Models** (removed):
- `Comment` - Individual comments on posts/activities
- `CommentReaction` - Reactions to comments (👍, ❤️, etc.)

**Database Tables** (removed):
- `comments` table with columns:
  - `id`, `user_id`, `activity_id`, `parent_comment_id`
  - `content`, `is_deleted`, `created_at`, `updated_at`
- `comment_reactions` table

**Relationships** (removed):
- `Post::comments()` - HasMany Comment
- `Activity::comments()` - HasMany Comment
- `Comment::replies()` - HasMany Comment (self-referencing)
- `Comment::reactions()` - HasMany CommentReaction

**Filament Resources** (removed):
- `CommentResource` - Admin CRUD for comments

**Factories** (removed):
- `CommentFactory` - Test data generation

### New Chat System

**Models** (added):
- `Conversation` - Polymorphic parent for all discussions
- `Message` - Individual messages in conversations
- `MessageReaction` - Reactions to messages

**Database Tables** (added):
- `conversations` - Polymorphic conversations
- `conversation_participants` - Many-to-many pivot with roles
- `messages` - Messages with threading support
- `message_reactions` - Message reactions

**Key Improvements**:
- ✅ **Unified**: One system for posts, activities, and DMs
- ✅ **Real-time**: Laravel Echo + Reverb for instant updates
- ✅ **Threading**: Reply to specific messages
- ✅ **Participants**: Explicit membership with roles
- ✅ **Read receipts**: Track last_read_at per user
- ✅ **Muting**: Users can mute conversations
- ✅ **Soft deletes**: Messages can be deleted without losing history

## Key Design Decisions

### 1. Polymorphic Conversations

**Decision**: Use polymorphic relationships (`conversationable_type`, `conversationable_id`) instead of separate tables.

**Rationale**:
- Single source of truth for all conversations
- Easy to add new conversationable types (e.g., Communities, Events)
- Consistent querying and relationships

**Trade-off**: Slightly more complex queries, but worth it for flexibility.

### 2. Explicit Participant Management

**Decision**: Use pivot table with roles instead of implicit access.

**Rationale**:
- Clear membership model (who can see what)
- Supports different roles (admin, member)
- Enables features like muting, read receipts
- Allows private conversations within public posts

**Trade-off**: More database writes, but enables richer features.

### 3. Message Threading (reply_to_message_id)

**Decision**: Self-referencing foreign key instead of nested tree structure.

**Rationale**:
- Simple one-level threading (reply to message, not reply to reply)
- Matches modern chat UX (Slack, Discord)
- Easier to query and display

**Trade-off**: No deep nesting, but that's intentional for UX simplicity.

### 4. Soft Deletes for Messages

**Decision**: Use `deleted_at` instead of hard deletes.

**Rationale**:
- Preserve conversation context (don't break reply chains)
- Allow moderation review
- Enable "undo delete" feature

**Trade-off**: Larger database, but worth it for data integrity.

### 5. Real-time via Laravel Echo + Reverb

**Decision**: Use Laravel's native WebSocket solution instead of Pusher/Ably.

**Rationale**:
- No external dependencies or costs
- Full control over infrastructure
- Consistent with Laravel ecosystem

**Trade-off**: Self-hosting complexity, but manageable with Laravel Forge/Vapor.

## Integration with Reports System

The reports system was updated to reference the new chat architecture:

**Old**: `reported_comment_id` → `comments` table
**New**: `reported_message_id` → `messages` table

**Report Model** (`app/Models/Report.php`):
```php
public function reportedMessage(): BelongsTo
{
    return $this->belongsTo(Message::class, 'reported_message_id');
}
```

Users can now report:
- Messages (instead of comments)
- Activities
- Users
- Posts (if added to reports table)

## Adding Chat to New Content Types

To add conversations to a new model (e.g., `Community`):

### 1. Add Relationship to Model

```php
// app/Models/Community.php
public function conversation(): \Illuminate\Database\Eloquent\Relations\MorphOne
{
    return $this->morphOne(Conversation::class, 'conversationable');
}
```

### 2. Update ChatService Type Logic

```php
// app/Services/ChatService.php
$type = match (get_class($conversationable)) {
    'App\Models\Post' => 'public',
    'App\Models\Activity' => 'group',
    'App\Models\Community' => 'group', // Add new type
    default => 'public',
};
```

### 3. Use ChatComponent in View

```blade
<livewire:chat.chat-component :conversationable="$community" />
```

That's it! The unified architecture handles the rest.

## Real-time Broadcasting

### Event: MessageSent

**Location**: `app/Events/MessageSent.php`

**Broadcast Channel**: `conversation.{conversationId}`

**Payload**:
```php
[
    'id' => $message->id,
    'user' => [
        'display_name' => $message->user->display_name,
        'profile_image_url' => $message->user->profile_image_url,
    ],
    'body' => $message->body,
    'created_at' => $message->created_at,
    'reply_to' => $message->replyTo ? [...] : null,
]
```

**Frontend Listener** (in ChatComponent):
```php
public function getListeners()
{
    return [
        "echo:conversation.{$this->conversationId},MessageSent" => 'onMessageReceived',
    ];
}
```

## Testing Considerations

### Unit Tests

Test `ChatService` methods:
- `getOrCreateConversation()` - Creates conversation for new post/activity
- `sendMessage()` - Creates message, adds participant, broadcasts event
- `addParticipant()` - Handles duplicate prevention
- `markAsRead()` - Updates pivot table

### Feature Tests

Test `ChatComponent`:
- Sending messages updates UI
- Real-time messages appear for other users
- Threading works correctly
- Read receipts update

### Database Tests

Test relationships:
- Post has one conversation
- Conversation has many messages
- Message belongs to user and conversation
- Participants pivot works correctly

## Migration Notes

### From Comments to Messages

The migration from the old comment system to unified chat was completed in November 2025:

**Removed**:
- `comments` table and migration
- `Comment` model
- `CommentFactory`
- `CommentResource` (Filament)
- All comment-related indexes
- Comment seeding in `DatabaseSeeder`

**Added**:
- `conversations`, `conversation_participants`, `messages`, `message_reactions` tables
- `Conversation`, `Message`, `MessageReaction` models
- `ChatService` for business logic
- `ChatComponent` for UI
- Real-time broadcasting with Laravel Echo

**Updated**:
- `reports` table: `reported_comment_id` → `reported_message_id`
- `DatabaseSeeder`: Comment seeding → Conversation/Message seeding
- Post/Activity detail views: Comment sections → ChatComponent

### Data Migration (if needed)

If migrating existing comment data to messages:

```php
// Example migration script
foreach (Comment::all() as $comment) {
    // Get or create conversation for the commentable
    $conversation = ChatService::getOrCreateConversation($comment->commentable);

    // Create message from comment
    Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $comment->user_id,
        'body' => $comment->content,
        'created_at' => $comment->created_at,
    ]);
}
```

## Future Enhancements

Potential features enabled by this architecture:

1. **Voice/Video Calls**: Add `type: 'call'` messages with WebRTC metadata
2. **File Sharing**: Already supported via `attachment_path` field
3. **Message Editing**: Add `edited_at` timestamp and edit history in metadata
4. **Pinned Messages**: Add `is_pinned` to messages table
5. **Conversation Archiving**: Add `archived_at` to pivot table
6. **Typing Indicators**: Broadcast typing events on conversation channel
7. **Message Search**: Full-text search across all user's conversations
8. **Conversation Templates**: Pre-fill messages for common scenarios

## Summary

The unified chat architecture represents a fundamental shift from static comments to dynamic conversations. By treating all communication as conversations with participants, we've created a more maintainable, feature-rich, and user-friendly system that aligns perfectly with FunLynk's spontaneous, ephemeral nature.

**Key Takeaways**:
- ✅ One system for posts, activities, and DMs
- ✅ Real-time updates everywhere
- ✅ Consistent features (threading, reactions, read receipts)
- ✅ Easier to maintain and extend
- ✅ Better user experience
- ✅ Aligned with FunLynk's philosophy


