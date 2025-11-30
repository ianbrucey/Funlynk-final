# Agent A: Private Direct Messaging - Backend & Business Logic

You are **Agent A** implementing the backend and business logic for the Private Direct Messaging feature in FunLynk.

## Your Role

**Focus**: Database, services, routing logic, notifications, authorization
**Estimated Time**: 19-25 hours
**Tasks**: T01, T02, T03, T06, T08

## Documentation to Read First

**CRITICAL - Read these before starting**:
1. `context-engine/tasks/E05_Social_Interaction/F05_Private_Direct_Messaging/README.md` - Main task document
2. `context-engine/tasks/E05_Social_Interaction/F05_Private_Direct_Messaging/IMPLEMENTATION_GUIDE.md` - Code examples
3. `context-engine/tasks/E05_Social_Interaction/F05_Private_Direct_Messaging/QUICK_START.md` - Quick reference

**Context files to review**:
- `context-engine/domain-contexts/chat-architecture.md` - Unified chat system
- `app/Services/ChatService.php` - Existing chat service (you'll extend this)
- `app/Models/User.php` - Check `mutuals()` relationship (lines 177-185)
- `app/Models/Conversation.php` - Conversation model
- `app/Models/ConversationParticipant.php` - Pivot model

## Your Task Sequence

### 🚨 Phase 1: Foundation (CRITICAL - Agent B is blocked until this is done)

#### T01: Extend ChatService for DM Routing Logic (4-5 hours)

**Artisan Commands**:
```bash
php artisan make:test --pest Feature/DirectMessageRoutingTest --no-interaction
```

**What to Build**:
Extend `app/Services/ChatService.php` with these methods:

1. **`createDirectMessageConversation(User $sender, User $recipient): Conversation`**
   - Check if DM conversation already exists between users (either direction)
   - Determine if message should route to requests using `shouldRouteToRequests()`
   - Create new `Conversation` with `type = 'private'`
   - Set metadata: `is_dm = true`, `is_request = bool`, `recipient_id`
   - Attach both users as participants
   - Recipient gets `request_status = 'pending'` if not mutual followers

2. **`isMutualFollower(User $userA, User $userB): bool`**
   - Use existing `User::mutuals()` relationship
   - Return true if users follow each other

3. **`shouldRouteToRequests(User $sender, User $recipient): bool`**
   - Return `!$this->isMutualFollower($sender, $recipient)`

4. **Modify `sendMessage()` method**:
   - After creating message, check if conversation is DM
   - Fire `DirectMessageReceived` event if mutual followers
   - Fire `MessageRequestReceived` event if not mutual followers

**Code Example**: See `IMPLEMENTATION_GUIDE.md` lines 7-100

**Tests to Write**:
- Mutual followers create DM → `is_request = false`
- Non-mutual followers create DM → `is_request = true`
- Existing conversation is reused (not duplicated)
- Both users are participants
- Correct events are fired

**Deliverables**:
- [ ] Modified `app/Services/ChatService.php`
- [ ] `tests/Feature/DirectMessageRoutingTest.php`
- [ ] All tests passing

---

#### T02: Database Schema Enhancements (2-3 hours)

**Artisan Commands**:
```bash
php artisan make:migration add_request_status_to_conversation_participants_table --no-interaction
```

**What to Build**:

1. **Migration**: Add `request_status` field to `conversation_participants` table
   - Type: `enum('pending', 'accepted', 'declined')`
   - Nullable: `true`
   - Position: After `last_read_at`
   - Index: Yes

2. **Update Model**: Modify `app/Models/ConversationParticipant.php`
   - Add `request_status` to `casts()` method

**Code Example**: See `IMPLEMENTATION_GUIDE.md` lines 130-167

**Deliverables**:
- [ ] Migration file created
- [ ] Migration run successfully (`php artisan migrate`)
- [ ] `ConversationParticipant` model updated
- [ ] Database index created

---

#### T03: Message Request Management Service (3-4 hours)

**Artisan Commands**:
```bash
php artisan make:class Services/MessageRequestService --no-interaction
php artisan make:test --pest Feature/MessageRequestServiceTest --no-interaction
```

**What to Build**:
Create `app/Services/MessageRequestService.php` with these methods:

1. **`acceptRequest(Conversation $conversation, User $recipient): void`**
   - Update recipient's pivot: `request_status = 'accepted'`
   - Update conversation metadata: `is_request = false`
   - Create notification for sender: "X accepted your message request"

2. **`declineRequest(Conversation $conversation, User $recipient): void`**
   - Update recipient's pivot: `request_status = 'declined'`
   - NO notification to sender (privacy)

3. **`getMessageRequests(User $user): Collection`**
   - Query conversations where user has `request_status = 'pending'`
   - Eager load participants and latest message
   - Order by `last_message_at DESC`

4. **`getDirectInbox(User $user): Collection`**
   - Query conversations where `request_status IN (null, 'accepted')`
   - Eager load participants and latest message
   - Order by `last_message_at DESC`

5. **`getUnreadRequestCount(User $user): int`**
   - Count pending requests where `last_read_at IS NULL`

**Code Example**: See `IMPLEMENTATION_GUIDE.md` lines 172-268

**Tests to Write**:
- Accept request updates status and sends notification
- Decline request updates status without notification
- `getMessageRequests()` returns only pending requests
- `getDirectInbox()` excludes pending/declined requests
- Unread count is accurate

**Deliverables**:
- [ ] `app/Services/MessageRequestService.php`
- [ ] `tests/Feature/MessageRequestServiceTest.php`
- [ ] All tests passing

---

### 🔄 CHECKPOINT: Notify Agent B

After completing T01-T03, **notify Agent B** that backend services are ready. Share:
- `ChatService::createDirectMessageConversation()` method signature
- `MessageRequestService` method signatures
- Any important implementation notes

---

### Phase 2: Parallel Work (Can work simultaneously with Agent B)

#### T06: Notification System Integration (4-5 hours)

**Artisan Commands**:
```bash
php artisan make:event DirectMessageReceived --no-interaction
php artisan make:listener SendDirectMessageNotification --no-interaction
php artisan make:event MessageRequestReceived --no-interaction
php artisan make:listener SendMessageRequestNotification --no-interaction
```

**What to Build**:

1. **Events**:
   - `app/Events/DirectMessageReceived.php` - Constructor takes `Message $message, Conversation $conversation`
   - `app/Events/MessageRequestReceived.php` - Same constructor

2. **Listeners**:
   - `app/Listeners/SendDirectMessageNotification.php` - Creates notification with type `new_direct_message`
   - `app/Listeners/SendMessageRequestNotification.php` - Creates notification with type `message_request`

3. **Notification Data Structure**:
   ```php
   'data' => [
       'conversation_id' => $conversation->id,
       'sender_id' => $sender->id,
       'sender_name' => $sender->display_name ?? $sender->username,
       'sender_avatar' => $sender->profile_image_url,
       'message_preview' => Str::limit($message->body, 50),
       'url' => route('messages.show', $conversation->id), // or route('messages.requests')
   ]
   ```

4. **Register in `EventServiceProvider`**:
   ```php
   protected $listen = [
       DirectMessageReceived::class => [SendDirectMessageNotification::class],
       MessageRequestReceived::class => [SendMessageRequestNotification::class],
   ];
   ```

**Coordinate with Agent B**:
- Route names: `messages.index`, `messages.show`, `messages.requests`
- Confirm these match what Agent B is creating in T07

**Deliverables**:
- [ ] Event classes created
- [ ] Listener classes created
- [ ] Registered in `EventServiceProvider`
- [ ] Notifications created correctly
- [ ] Tests for notification delivery

---

#### T08: Policies and Authorization (3-4 hours)

**Artisan Commands**:
```bash
php artisan make:policy ConversationPolicy --model=Conversation --no-interaction
php artisan make:test --pest Feature/DirectMessageAuthorizationTest --no-interaction
```

**What to Build**:
Create `app/Policies/ConversationPolicy.php` with these methods:

1. **`view(User $user, Conversation $conversation): bool`**
   - User must be a participant

2. **`sendMessage(User $user, Conversation $conversation): bool`**
   - User must be a participant
   - If DM with pending request, only original sender can send

3. **`acceptRequest(User $user, Conversation $conversation): bool`**
   - User must be the recipient with `request_status = 'pending'`

4. **`declineRequest(User $user, Conversation $conversation): bool`**
   - Same as `acceptRequest()`

**Register in `AuthServiceProvider`**:
```php
protected $policies = [
    Conversation::class => ConversationPolicy::class,
];
```

**Tests to Write**:
- Participants can view conversations
- Non-participants cannot view
- Only sender can message pending requests
- Only recipient can accept/decline requests

**Deliverables**:
- [ ] `app/Policies/ConversationPolicy.php`
- [ ] Registered in `AuthServiceProvider`
- [ ] `tests/Feature/DirectMessageAuthorizationTest.php`
- [ ] All tests passing

---

## Testing Strategy

### Run Tests Frequently
```bash
# Individual test suites
php artisan test --filter=DirectMessageRouting
php artisan test --filter=MessageRequestService
php artisan test --filter=DirectMessageNotification
php artisan test --filter=DirectMessageAuthorization

# All DM tests
php artisan test --filter=DirectMessage

# Full test suite
php artisan test
```

### Code Formatting
```bash
vendor/bin/pint --dirty
```

---

## Integration Points with Agent B

### After T01 (ChatService)
Share with Agent B:
```php
// Method signature
public function createDirectMessageConversation(User $sender, User $recipient): Conversation

// Usage in Livewire component
$conversation = app(ChatService::class)->createDirectMessageConversation(auth()->user(), $otherUser);
return redirect()->route('messages.show', $conversation->id);
```

### After T03 (MessageRequestService)
Share with Agent B:
```php
// Method signatures
public function getDirectInbox(User $user): Collection
public function getMessageRequests(User $user): Collection
public function acceptRequest(Conversation $conversation, User $recipient): void
public function declineRequest(Conversation $conversation, User $recipient): void
public function getUnreadRequestCount(User $user): int

// Usage in Livewire component
$inbox = app(MessageRequestService::class)->getDirectInbox(auth()->user());
$requests = app(MessageRequestService::class)->getMessageRequests(auth()->user());
```

### During T06 (Notifications)
Coordinate route names:
- `messages.index` - Main messages page
- `messages.show` - Specific conversation
- `messages.requests` - Message requests page

### After T08 (Policies)
Share authorization usage:
```php
// In Livewire components
$this->authorize('view', $conversation);
$this->authorize('acceptRequest', $conversation);
```

---

## Success Criteria

Before marking your work complete:
- [ ] All service methods tested and working
- [ ] Database migrations run successfully
- [ ] Notifications firing correctly
- [ ] Policies enforcing authorization
- [ ] All backend tests passing
- [ ] Code formatted with Pint
- [ ] Agent B notified after T01-T03 checkpoint
- [ ] Route names coordinated with Agent B

---

## Common Pitfalls

1. **Mutual follow check is slow**: Cache the result for active conversations
2. **Conversation not found**: Check both user directions in query
3. **Events not firing**: Ensure they're dispatched in `sendMessage()`
4. **Policy not applied**: Register in `AuthServiceProvider`

---

## Questions?

If you encounter blockers:
1. Check `IMPLEMENTATION_GUIDE.md` for code examples
2. Review existing `ChatService` implementation
3. Ask Agent B about route names or UI requirements
4. Consult `context-engine/domain-contexts/chat-architecture.md`

---

**Start with T01 and work sequentially: T01 → T02 → T03 → [Notify Agent B] → T06 + T08 in parallel**

Good luck! 🚀

