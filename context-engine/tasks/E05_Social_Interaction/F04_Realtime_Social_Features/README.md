# F04 Real-time Social Features

## Feature Overview

Enable live social interactions through WebSocket-powered chat, instant messaging, presence indicators, and real-time notifications using Laravel 12, Filament v4, Laravel Echo, and Pusher/Soketi. This feature transforms FunLynk into a dynamic, real-time social platform where users can chat about activities, send direct messages, and see who's online. Builds on E01 notifications and integrates with E05/F01-F03 for live updates.

**Key Architecture**: Uses Laravel Broadcasting with Laravel Echo (client-side) and Pusher or Soketi (WebSocket server). Activity-specific chat rooms allow attendees to coordinate, while DMs enable private conversations. Presence channels show who's viewing an activity.

## Feature Scope

### In Scope
- **Activity chat rooms**: Live group chat for activity attendees
- **Direct messaging**: One-on-one private messages between users
- **Presence indicators**: Show who's online and viewing activities
- **Real-time notifications**: Instant notification delivery via WebSockets
- **Typing indicators**: Show when someone is typing in chat
- **Message read receipts**: Track message read status

### Out of Scope
- **Video/voice chat**: Phase 2 feature
- **File attachments**: Phase 2 feature
- **Message search**: Handled in E04 Discovery
- **Community chat**: Uses activity chat for community discussions

## Tasks Breakdown

### T01: Chat & Messaging Database Schema
**Estimated Time**: 3-4 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:migration create_chat_rooms_table --no-interaction
php artisan make:migration create_chat_messages_table --no-interaction
php artisan make:migration create_direct_messages_table --no-interaction
php artisan make:migration create_message_reads_table --no-interaction
```

**Description**: Create database tables for chat rooms (linked to activities), chat messages, direct messages, and read receipts.

**Key Implementation Details**:
- `chat_rooms` table: `id`, `activity_id`, `type` (activity/community), `created_at`
- `chat_messages` table: `id`, `chat_room_id`, `user_id`, `message`, `created_at`
- `direct_messages` table: `id`, `sender_id`, `recipient_id`, `message`, `read_at`, `created_at`
- `message_reads` table: `id`, `chat_message_id`, `user_id`, `read_at`
- Index on foreign keys and timestamps for performance

**Deliverables**:
- [ ] Migration files for all messaging tables
- [ ] Indexes on frequently queried columns
- [ ] Soft deletes on messages for user privacy
- [ ] Schema tests

---

### T02: Chat & Message Models
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:model ChatRoom --no-interaction
php artisan make:model ChatMessage --no-interaction
php artisan make:model DirectMessage --no-interaction
php artisan make:factory ChatMessageFactory --model=ChatMessage --no-interaction
php artisan make:factory DirectMessageFactory --model=DirectMessage --no-interaction
```

**Description**: Create Eloquent models with relationships to User and Activity. Implement `casts()` for timestamps and implement helper methods.

**Key Implementation Details**:
- Use `casts()` method (Laravel 12)
- Relationships: `ChatRoom belongsTo Activity`, `ChatMessage belongsTo ChatRoom`, `DirectMessage belongsTo User (sender/recipient)`
- Implement `isUnread()`, `markAsRead()` helper methods
- Factories for testing with realistic data

**Deliverables**:
- [ ] ChatRoom, ChatMessage, DirectMessage models
- [ ] Relationships with User and Activity
- [ ] Helper methods for read/unread status
- [ ] Factories for all models

---

### T03: Laravel Broadcasting Configuration
**Estimated Time**: 5-6 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
# Install Laravel Echo and Pusher
composer require pusher/pusher-php-server

# Create broadcast events
php artisan make:event MessageSent --no-interaction
php artisan make:event UserTyping --no-interaction
php artisan make:event UserOnline --no-interaction

# Create channel authorization
php artisan make:class Broadcasting/ChatChannel --no-interaction
```

**Description**: Configure Laravel Broadcasting with Pusher or Soketi. Create broadcast events for messages, typing indicators, and presence. Implement channel authorization for private chats.

**Key Implementation Details**:
- Configure `config/broadcasting.php` with Pusher or Soketi credentials
- Create private channels: `private-chat.{roomId}`, `private-dm.{userId}.{recipientId}`
- Create presence channels: `presence-activity.{activityId}`
- Authorize channel access: only activity attendees can join chat
- Broadcast events on message send, typing, user online/offline
- Configure Laravel Echo on frontend (Alpine.js or vanilla JS)

**Deliverables**:
- [ ] Broadcasting config with Pusher/Soketi
- [ ] MessageSent, UserTyping, UserOnline events
- [ ] Channel authorization logic
- [ ] Frontend Echo configuration
- [ ] Tests for broadcasting

---

### T04: ChatService & MessagingService
**Estimated Time**: 5-6 hours
**Dependencies**: T02, T03
**Artisan Commands**:
```bash
php artisan make:class Services/ChatService --no-interaction
php artisan make:class Services/MessagingService --no-interaction
php artisan make:test --pest Feature/ChatServiceTest --no-interaction
```

**Description**: Build service classes handling chat room creation, message sending, read receipts, and direct messaging. Implement business logic for rate limiting and spam prevention.

**Key Implementation Details**:
- `ChatService`: `getOrCreateRoom($activity)`, `sendMessage()`, `getMessages()`, `markAsRead()`
- `MessagingService`: `sendDirectMessage()`, `getConversation()`, `markConversationAsRead()`, `getUnreadCount()`
- Rate limiting: 10 messages per minute per user
- Spam detection: block repeated identical messages
- Pagination: load 50 messages at a time, lazy load history
- Push notifications for offline users

**Deliverables**:
- [ ] ChatService with room and message management
- [ ] MessagingService for DMs
- [ ] Rate limiting and spam prevention
- [ ] Tests for all service methods

---

### T05: Filament Chat Management
**Estimated Time**: 3-4 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:filament-resource ChatMessage --generate --no-interaction
php artisan make:filament-resource DirectMessage --generate --no-interaction
```

**Description**: Create Filament admin resources for monitoring and moderating chat messages. Add tools for flagging inappropriate content and banning users from chat.

**Key Implementation Details**:
- Use Filament v4 `->components([])` for forms
- Display chat messages with user and timestamp
- Add filters: by activity, by user, by flagged
- Bulk actions: delete messages, ban user from chat
- Analytics: message volume, active rooms, engagement metrics

**Deliverables**:
- [ ] ChatMessage resource with moderation tools
- [ ] DirectMessage resource for admin review
- [ ] Bulk moderation actions
- [ ] Chat analytics widget

---

### T06: Livewire Chat Components
**Estimated Time**: 7-8 hours
**Dependencies**: T03, T04
**Artisan Commands**:
```bash
php artisan make:livewire Chat/ChatWindow --no-interaction
php artisan make:livewire Chat/MessageList --no-interaction
php artisan make:livewire Chat/DirectMessageList --no-interaction
php artisan make:livewire Chat/PresenceIndicator --no-interaction
php artisan make:test --pest Feature/ChatComponentsTest --no-interaction
```

**Description**: Build user-facing Livewire components for chat windows, DMs, and presence indicators. Integrate with Laravel Echo for real-time updates.

**Key Implementation Details**:
- `ChatWindow`: full chat interface with message list, input, typing indicators
- `MessageList`: displays messages, scrolls to bottom, lazy loads history
- `DirectMessageList`: shows conversations with unread counts
- `PresenceIndicator`: displays online users with avatars
- Wire up Laravel Echo listeners for real-time updates
- Use DaisyUI styling with galaxy theme
- Implement optimistic UI for sent messages

**Deliverables**:
- [ ] ChatWindow component with real-time messaging
- [ ] MessageList with lazy loading and scroll behavior
- [ ] DirectMessageList with conversation threads
- [ ] PresenceIndicator showing online users
- [ ] Tests for all Livewire interactions

---

### T07: Real-time Tests & Performance
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/RealtimeMessagingTest --no-interaction
php artisan make:test --pest Feature/PresenceTest --no-interaction
php artisan test --filter=Realtime
```

**Description**: Write comprehensive tests for real-time features including broadcasting, presence, and message delivery. Optimize performance for scalability.

**Key Implementation Details**:
- Test message broadcasting to correct channels
- Test channel authorization (only attendees can access)
- Test presence tracking (online/offline)
- Test typing indicators
- Test rate limiting and spam prevention
- Mock Pusher/Soketi in tests
- Optimize queries: eager load users, cache presence data

**Deliverables**:
- [ ] Broadcasting tests with mocked Pusher
- [ ] Presence channel tests
- [ ] Message delivery tests
- [ ] Performance optimizations
- [ ] All tests passing with >80% coverage

---

## Success Criteria

### Functional Requirements
- [ ] Activity attendees can chat in real-time
- [ ] Users can send direct messages to each other
- [ ] Presence indicators show who's online
- [ ] Typing indicators work correctly
- [ ] Message read receipts track read status
- [ ] Offline users receive push notifications

### Technical Requirements
- [ ] Laravel Broadcasting configured with Pusher or Soketi
- [ ] Laravel Echo integrated on frontend
- [ ] Private channels authorized correctly
- [ ] Presence channels track online users
- [ ] Message broadcasting works in real-time
- [ ] Rate limiting prevents spam

### User Experience Requirements
- [ ] Chat interface intuitive and responsive
- [ ] Messages appear instantly (< 1 second latency)
- [ ] Typing indicators smooth
- [ ] Presence indicators accurate
- [ ] Message history loads fast
- [ ] Galaxy theme applied to chat UI
- [ ] Mobile-friendly chat interface

### Performance Requirements
- [ ] WebSocket connections stable
- [ ] Message delivery <1 second
- [ ] Presence updates efficient
- [ ] Chat history pagination optimized
- [ ] Database queries use eager loading

## Dependencies

### Blocks
- **E07 Moderation**: Chat moderation tools

### External Dependencies
- **E01 Core Infrastructure**: `users`, `activities`, `notifications` tables
- **Laravel Broadcasting**: WebSocket infrastructure
- **Pusher or Soketi**: WebSocket server
- **Laravel Echo**: Client-side WebSocket library

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method instead of `$casts` property in models
- Broadcasting configured in `config/broadcasting.php`
- Channel authorization in `routes/channels.php`

### Filament v4 Conventions
- Use `->components([])` for forms
- Analytics widgets use `StatsOverviewWidget`

### Broadcasting Configuration
```php
// config/broadcasting.php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'host' => env('PUSHER_HOST', 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com'),
        'port' => env('PUSHER_PORT', 443),
        'scheme' => env('PUSHER_SCHEME', 'https'),
    ],
],
```

### Channel Authorization
```php
// routes/channels.php
Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    $room = ChatRoom::find($roomId);
    return $room->activity->hasAttendee($user);
});
```

### Laravel Echo Frontend
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true
});

// Listen for messages
Echo.private(`chat.${roomId}`)
    .listen('MessageSent', (e) => {
        console.log(e.message);
    });
```

### Testing Considerations
- Use Pest v4 for all tests
- Mock Pusher with `Bus::fake()` or `Event::fake()`
- Test channel authorization thoroughly
- Test presence tracking with multiple users
- Run tests with: `php artisan test --filter=Realtime`

### Performance Optimization
- Cache presence data (1 minute TTL)
- Paginate chat history (50 messages per page)
- Eager load message senders: `ChatMessage::with('user')`
- Use database indexes on timestamps and foreign keys
- Consider Redis for high-volume chat storage

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P2
**Epic**: E05 Social Interaction
**Estimated Total Time**: 31-37 hours
**Dependencies**: E01 foundation complete, Pusher or Soketi account
