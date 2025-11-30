# F05 Private Direct Messaging

## Feature Overview

Enable private 1-on-1 direct messaging between users with intelligent message routing based on mutual follow status. Messages from mutual followers go directly to inbox, while messages from non-mutual followers go to a "Message Requests" inbox for approval.

**Key Architecture**: Leverages the existing unified chat architecture (`Conversation`, `Message`, `ChatComponent`) with new routing logic and UI for DM-specific features.

## Feature Scope

### In Scope
- **Message routing logic**: Mutual followers → direct inbox, non-mutual → message requests
- **Mutual follow detection**: Efficient checking via existing `User::mutuals()` relationship
- **DM conversation creation**: Private conversations between two users
- **Message requests inbox**: Separate view for pending message requests
- **Accept/decline requests**: User can approve or reject message requests
- **Notifications**: In-app notifications for new DMs and message requests
- **Profile integration**: "Message" button on user profiles
- **DM inbox page**: Dedicated page listing all DM conversations

### Out of Scope
- **Group DMs**: Only 1-on-1 conversations (group chats handled by Activities)
- **Message search**: Full-text search across messages (future enhancement)
- **Voice/video calls**: Text-only messaging
- **Read receipts**: Already handled by existing chat architecture
- **Message reactions**: Already handled by existing chat architecture

## Business Rules

### Mutual Follow Definition
Two users are "mutual followers" if:
- User A follows User B **AND**
- User B follows User A

Check via: `$userA->mutuals()->where('users.id', $userB->id)->exists()`

### Message Delivery Logic

| Scenario | Delivery Destination | Notification Type |
|----------|---------------------|-------------------|
| User A and User B are mutual followers | Direct inbox (immediate) | `new_direct_message` |
| User A follows User B, but B doesn't follow A | Message Requests inbox | `message_request` |
| User A doesn't follow User B at all | Message Requests inbox | `message_request` |

### Request Acceptance Flow
1. User B receives message request from User A
2. User B can:
   - **Accept**: Conversation moves to direct inbox, User B can reply
   - **Decline**: Conversation hidden from User B's view, User A unaware
   - **Ignore**: Conversation stays in requests inbox indefinitely

## Tasks Breakdown

### T01: Extend ChatService for DM Routing Logic
**Estimated Time**: 4-5 hours
**Dependencies**: None (builds on existing ChatService)
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/DirectMessageRoutingTest --no-interaction
```

**Description**: Add methods to `ChatService` for creating DM conversations with routing logic based on mutual follow status.

**Key Implementation Details**:
- Add `createDirectMessageConversation(User $sender, User $recipient): Conversation`
  - Check if conversation already exists between users
  - Set `type = 'private'`, `conversationable_type = null`, `conversationable_id = null`
  - Store recipient ID in `metadata->recipient_id` for easy querying
- Add `isMutualFollower(User $userA, User $userB): bool`
  - Use `$userA->mutuals()->where('users.id', $userB->id)->exists()`
- Add `shouldRouteToRequests(User $sender, User $recipient): bool`
  - Return `!$this->isMutualFollower($sender, $recipient)`
- Modify `sendMessage()` to check routing on first message in DM conversation
  - If should route to requests, set `metadata->is_request = true`

**Deliverables**:
- [ ] New methods in `ChatService`
- [ ] Routing logic based on mutual follows
- [ ] Metadata fields for DM tracking
- [ ] Passing feature tests for all scenarios

---

### T02: Database Schema Enhancements
**Estimated Time**: 2-3 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:migration add_dm_fields_to_conversations_table --no-interaction
php artisan make:migration add_request_status_to_conversation_participants_table --no-interaction
```

**Description**: Add fields to support DM routing and request management without breaking existing chat architecture.

**Key Implementation Details**:
- **conversations table**: No changes needed (metadata JSON handles DM-specific data)
- **conversation_participants pivot**: Add `request_status` enum field
  - Values: `null` (not a DM), `pending`, `accepted`, `declined`
  - Index on `request_status` for filtering
- Store in `conversations.metadata`:
  - `is_dm: true` (boolean)
  - `is_request: true/false` (boolean)
  - `recipient_id: uuid` (for easy querying)

**Deliverables**:
- [ ] Migration files
- [ ] Updated `ConversationParticipant` model with cast
- [ ] Database indexes for performance
- [ ] Schema tests

---

### T03: Message Request Management Service
**Estimated Time**: 3-4 hours
**Dependencies**: T01, T02
**Artisan Commands**:
```bash
php artisan make:class Services/MessageRequestService --no-interaction
php artisan make:test --pest Feature/MessageRequestServiceTest --no-interaction
```

**Description**: Create service class to handle accepting, declining, and managing message requests.

**Key Implementation Details**:
- `acceptRequest(Conversation $conversation, User $recipient): void`
  - Update pivot: `request_status = 'accepted'`
  - Update conversation metadata: `is_request = false`
  - Send notification to sender: "X accepted your message request"
- `declineRequest(Conversation $conversation, User $recipient): void`
  - Update pivot: `request_status = 'declined'`
  - Hide conversation from recipient's view
  - No notification to sender (privacy)
- `getMessageRequests(User $user): Collection`
  - Query conversations where user is participant with `request_status = 'pending'`
  - Order by `last_message_at DESC`
- `getDirectInbox(User $user): Collection`
  - Query conversations where:
    - User is participant
    - `type = 'private'`
    - `request_status IN (null, 'accepted')`
  - Order by `last_message_at DESC`

**Deliverables**:
- [ ] `MessageRequestService` class
- [ ] Accept/decline methods with notifications
- [ ] Query methods for inbox filtering
- [ ] Comprehensive unit tests

---

### T04: DM Inbox Livewire Component
**Estimated Time**: 5-6 hours
**Dependencies**: T03
**Artisan Commands**:
```bash
php artisan make:livewire DirectMessages/InboxList --no-interaction
php artisan make:livewire DirectMessages/RequestsList --no-interaction
php artisan make:test --pest Feature/DirectMessageInboxTest --no-interaction
```

**Description**: Create Livewire components for viewing DM conversations and message requests.

**Key Implementation Details**:
- **InboxList Component**:
  - Display all accepted DM conversations
  - Show latest message preview, unread count, timestamp
  - Click to open conversation in ChatComponent
  - Real-time updates via Laravel Echo
- **RequestsList Component**:
  - Display pending message requests
  - Show sender info, first message preview
  - Accept/Decline buttons
  - Badge count for unread requests
- Use `MessageRequestService` for data fetching
- Integrate with existing `ChatComponent` for message viewing

**Deliverables**:
- [ ] `InboxList` Livewire component
- [ ] `RequestsList` Livewire component
- [ ] Galaxy-themed UI with glass cards
- [ ] Real-time updates
- [ ] Feature tests

---

### T05: Profile "Message" Button Integration
**Estimated Time**: 3-4 hours
**Dependencies**: T01, T04
**Artisan Commands**:
```bash
php artisan make:livewire Profile/MessageButton --no-interaction
```

**Description**: Add "Message" button to user profiles that initiates DM conversations.

**Key Implementation Details**:
- Add button next to Follow/Unfollow on `show-profile.blade.php`
- Button behavior:
  - If conversation exists → redirect to DM inbox with conversation open
  - If no conversation → create new DM conversation, redirect to inbox
- Use `ChatService::createDirectMessageConversation()`
- Show mutual follower badge if applicable
- Disable for own profile

**Deliverables**:
- [ ] "Message" button on profile page
- [ ] Conversation creation/navigation logic
- [ ] Mutual follower indicator
- [ ] Galaxy-themed button styling
- [ ] Tests for button behavior

---

### T06: Notification System Integration
**Estimated Time**: 4-5 hours
**Dependencies**: T01, T03
**Artisan Commands**:
```bash
php artisan make:event DirectMessageReceived --no-interaction
php artisan make:listener SendDirectMessageNotification --no-interaction
php artisan make:event MessageRequestReceived --no-interaction
php artisan make:listener SendMessageRequestNotification --no-interaction
```

**Description**: Create events and listeners for DM and message request notifications.

**Key Implementation Details**:
- **DirectMessageReceived Event**:
  - Fired when mutual followers send messages
  - Listener creates notification with type `new_direct_message`
  - Include sender info, message preview, conversation link
- **MessageRequestReceived Event**:
  - Fired when non-mutual followers send first message
  - Listener creates notification with type `message_request`
  - Include sender info, message preview, requests inbox link
- **MessageRequestAccepted Event**:
  - Fired when recipient accepts request
  - Notify sender with type `message_request_accepted`
- Register listeners in `EventServiceProvider`
- Respect user notification preferences

**Deliverables**:
- [ ] Event classes for DM notifications
- [ ] Listener classes creating notifications
- [ ] Notification data structures
- [ ] Event registration
- [ ] Tests for notification delivery

---

### T07: Routes and Pages
**Estimated Time**: 3-4 hours
**Dependencies**: T04, T05
**Artisan Commands**:
```bash
php artisan make:livewire DirectMessages/MessagesPage --no-interaction
```

**Description**: Create dedicated routes and pages for DM inbox and message requests.

**Key Implementation Details**:
- **Routes** (`routes/web.php`):
  - `GET /messages` → DM inbox page (authenticated)
  - `GET /messages/requests` → Message requests page (authenticated)
  - `GET /messages/{conversationId}` → Specific conversation view
- **MessagesPage Component**:
  - Tabbed interface: "Inbox" and "Requests"
  - Left sidebar: conversation list
  - Right panel: `ChatComponent` for selected conversation
  - Empty states for no conversations/requests
- Use galaxy theme with glass morphism
- Mobile-responsive layout

**Deliverables**:
- [ ] Routes for DM pages
- [ ] `MessagesPage` Livewire component
- [ ] Tabbed interface with sidebar
- [ ] Galaxy-themed UI
- [ ] Mobile-responsive design
- [ ] Navigation integration

---

### T08: Policies and Authorization
**Estimated Time**: 3-4 hours
**Dependencies**: T01, T03
**Artisan Commands**:
```bash
php artisan make:policy ConversationPolicy --model=Conversation --no-interaction
php artisan make:test --pest Feature/DirectMessageAuthorizationTest --no-interaction
```

**Description**: Implement authorization rules for DM conversations and message requests.

**Key Implementation Details**:
- **ConversationPolicy**:
  - `view(User $user, Conversation $conversation)`: User must be participant
  - `sendMessage(User $user, Conversation $conversation)`:
    - If request pending → only original sender can send
    - If accepted → both participants can send
  - `acceptRequest(User $user, Conversation $conversation)`: User must be recipient
  - `declineRequest(User $user, Conversation $conversation)`: User must be recipient
- Apply policies in `ChatComponent` and `MessageRequestService`
- Return 403 for unauthorized actions

**Deliverables**:
- [ ] `ConversationPolicy` with DM rules
- [ ] Policy registration in `AuthServiceProvider`
- [ ] Authorization checks in components/services
- [ ] Tests for all policy methods

---

### T09: UI Polishing and Tests
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T08
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/DirectMessagingE2ETest --no-interaction
```

**Description**: Polish UI, add loading states, error handling, and comprehensive end-to-end tests.

**Key Implementation Details**:
- **UI Enhancements**:
  - Loading skeletons for conversation lists
  - Empty states with illustrations
  - Error messages for failed actions
  - Toast notifications for success/error
  - Unread message badges
  - Typing indicators (optional)
- **Testing**:
  - E2E test: Mutual followers send DM → appears in inbox
  - E2E test: Non-mutual sends DM → appears in requests
  - E2E test: Accept request → conversation moves to inbox
  - E2E test: Decline request → conversation hidden
  - Test notification delivery
  - Test authorization rules

**Deliverables**:
- [ ] Polished UI with loading/error states
- [ ] Galaxy theme consistency
- [ ] Comprehensive E2E tests
- [ ] Edge case handling
- [ ] Pint formatting clean

---

## Success Criteria

### Database & Models
- [ ] `conversation_participants.request_status` field added
- [ ] Conversations store DM metadata correctly
- [ ] Efficient queries for inbox/requests filtering

### Business Logic & Services
- [ ] `ChatService` handles DM creation and routing
- [ ] `MessageRequestService` manages accept/decline flow
- [ ] Mutual follow detection works correctly
- [ ] Notifications sent for all DM events

### User Experience
- [ ] Users can send DMs from profiles
- [ ] Mutual followers see messages in direct inbox
- [ ] Non-mutual followers see messages in requests
- [ ] Accept/decline requests works smoothly
- [ ] Real-time message updates
- [ ] Mobile-friendly interface

### Integration
- [ ] Reuses existing `ChatComponent` for messaging UI
- [ ] Integrates with existing notification system
- [ ] Respects user privacy settings
- [ ] Works with existing follow system

### Authorization
- [ ] Only participants can view conversations
- [ ] Only recipients can accept/decline requests
- [ ] Proper 403 responses for unauthorized actions

## Dependencies

### Blocks
- **E02 User Discovery**: Users need to find profiles to message
- **E05 Social Features**: Enhances social interaction capabilities

### External Dependencies
- **E01 Core**: `users`, `follows`, `conversations`, `messages` tables
- **E01 Core**: `User`, `Conversation`, `Message` models
- **E01 Core**: `ChatService`, `ChatComponent`
- **E01 Core**: Notification system

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` in models (not `$casts`)
- Use `php artisan make:` with `--no-interaction`
- Leverage existing chat architecture

### Filament v4 Conventions
- Admin can view all DM conversations via `ConversationResource`
- Add filters for `type = 'private'` and `request_status`

### Testing
- Pest v4; use `RefreshDatabase`
- Test mutual follow detection thoroughly
- Test all routing scenarios
- Run focused tests: `php artisan test --filter=DirectMessage`

### Performance Considerations
- Index `conversation_participants.request_status`
- Cache mutual follow checks for active conversations
- Paginate conversation lists (50 per page)
- Use eager loading for participants and latest messages

### Privacy Considerations
- Declined requests hidden from recipient (not deleted)
- Sender unaware of decline (no notification)
- Blocked users cannot send DMs (future enhancement)

---

## Parallel Implementation Strategy

This feature can be split between two agents working simultaneously:

### Agent A: Backend & Business Logic (T01, T02, T03, T06, T08)
**Focus**: Database, services, routing logic, notifications, authorization
**Estimated Time**: 19-25 hours
**Deliverables**:
- ChatService DM methods
- Database migrations
- MessageRequestService
- Notification events/listeners
- ConversationPolicy

### Agent B: Frontend & UI (T04, T05, T07, T09)
**Focus**: Livewire components, pages, profile integration, UI polish
**Estimated Time**: 19-23 hours
**Dependencies**: Needs T01-T03 completed first (can start on UI mockups in parallel)
**Deliverables**:
- InboxList and RequestsList components
- Profile message button
- Messages page with tabs
- UI polish and E2E tests

**Integration Point**: After Agent A completes T01-T03, Agent B can begin full implementation. Agent B can start UI design and component structure in parallel.

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P2 (High - Core Social Feature)
**Epic**: E05 Social Interaction
**Estimated Total Time**: 38-48 hours (19-24 hours per agent in parallel)
**Dependencies**: E01 Core Infrastructure (complete), E02 User Profiles (for follow system)

