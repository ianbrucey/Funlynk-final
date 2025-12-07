# F05 Private Direct Messaging - Quick Start Guide

## Before You Begin

### Prerequisites Checklist
- [ ] E01 Core Infrastructure complete (database, auth, notifications)
- [ ] Unified chat system implemented (Conversation, Message, ChatComponent)
- [ ] User follow system working (User::mutuals() relationship)
- [ ] Laravel Echo + Reverb configured for real-time updates
- [ ] Galaxy theme UI standards reviewed

### Key Files to Review
1. `context-engine/domain-contexts/chat-architecture.md` - Understand unified chat system
2. `app/Services/ChatService.php` - Existing chat service methods
3. `app/Models/User.php` - Check `mutuals()` relationship (line 177-185)
4. `app/Livewire/Chat/ChatComponent.php` - Reusable chat UI component
5. `context-engine/domain-contexts/ui-design-standards.md` - Galaxy theme guidelines

---

## Quick Implementation Checklist

### Phase 1: Backend Foundation (Agent A - 9-12 hours)

#### T01: ChatService DM Methods ✅
```bash
php artisan make:test --pest Feature/DirectMessageRoutingTest --no-interaction
```
- [ ] Add `createDirectMessageConversation(User $sender, User $recipient)`
- [ ] Add `isMutualFollower(User $userA, User $userB)`
- [ ] Add `shouldRouteToRequests(User $sender, User $recipient)`
- [ ] Modify `sendMessage()` to fire DM events
- [ ] Test mutual follower routing
- [ ] Test non-mutual follower routing

#### T02: Database Schema ✅
```bash
php artisan make:migration add_request_status_to_conversation_participants_table --no-interaction
```
- [ ] Add `request_status` enum to `conversation_participants`
- [ ] Add index on `request_status`
- [ ] Update `ConversationParticipant` model casts
- [ ] Run migration
- [ ] Test schema changes

#### T03: MessageRequestService ✅
```bash
php artisan make:class Services/MessageRequestService --no-interaction
php artisan make:test --pest Feature/MessageRequestServiceTest --no-interaction
```
- [ ] Create `MessageRequestService` class
- [ ] Implement `acceptRequest()`
- [ ] Implement `declineRequest()`
- [ ] Implement `getMessageRequests()`
- [ ] Implement `getDirectInbox()`
- [ ] Implement `getUnreadRequestCount()`
- [ ] Write comprehensive tests

**🔄 CHECKPOINT**: Agent B can now start full UI implementation

---

### Phase 2: Parallel Development (10-13 hours)

#### Agent A: T06 Notifications ✅
```bash
php artisan make:event DirectMessageReceived --no-interaction
php artisan make:listener SendDirectMessageNotification --no-interaction
php artisan make:event MessageRequestReceived --no-interaction
php artisan make:listener SendMessageRequestNotification --no-interaction
```
- [ ] Create `DirectMessageReceived` event
- [ ] Create `SendDirectMessageNotification` listener
- [ ] Create `MessageRequestReceived` event
- [ ] Create `SendMessageRequestNotification` listener
- [ ] Register in `EventServiceProvider`
- [ ] Test notification delivery

#### Agent A: T08 Authorization ✅
```bash
php artisan make:policy ConversationPolicy --model=Conversation --no-interaction
```
- [ ] Create `ConversationPolicy`
- [ ] Implement `view()` method
- [ ] Implement `sendMessage()` method
- [ ] Implement `acceptRequest()` method
- [ ] Implement `declineRequest()` method
- [ ] Register in `AuthServiceProvider`
- [ ] Test all policy methods

#### Agent B: T04 Inbox Components ✅
```bash
php artisan make:livewire DirectMessages/InboxList --no-interaction
php artisan make:livewire DirectMessages/RequestsList --no-interaction
```
- [ ] Create `InboxList` component
- [ ] Create `RequestsList` component
- [ ] Design Blade views with galaxy theme
- [ ] Implement real-time updates
- [ ] Add accept/decline buttons
- [ ] Test component functionality

#### Agent B: T05 Profile Button ✅
```bash
php artisan make:livewire Profile/MessageButton --no-interaction
```
- [ ] Add "Message" button to profile page
- [ ] Implement `startConversation()` method
- [ ] Add galaxy-themed button styling
- [ ] Test conversation creation
- [ ] Test navigation to inbox

---

### Phase 3: Final Integration (4-5 hours)

#### Agent B: T07 Routes & Pages ✅
```bash
php artisan make:livewire DirectMessages/MessagesPage --no-interaction
```
- [ ] Add routes to `routes/web.php`
- [ ] Create `MessagesPage` component
- [ ] Build tabbed interface (Inbox/Requests)
- [ ] Integrate `ChatComponent` for messages
- [ ] Apply galaxy theme
- [ ] Make mobile-responsive

#### Agent B: T09 Polish & E2E Tests ✅
```bash
php artisan make:test --pest Feature/DirectMessagingE2ETest --no-interaction
```
- [ ] Add loading skeletons
- [ ] Create empty states
- [ ] Implement error handling
- [ ] Add unread badges
- [ ] Write E2E tests
- [ ] Run `vendor/bin/pint --dirty`

---

## Testing Strategy

### Unit Tests (Agent A)
```bash
php artisan test --filter=DirectMessageRouting
php artisan test --filter=MessageRequestService
php artisan test --filter=DirectMessageNotification
php artisan test --filter=DirectMessageAuthorization
```

### Feature Tests (Agent B)
```bash
php artisan test --filter=DirectMessageInbox
php artisan test --filter=ProfileMessageButton
php artisan test --filter=DirectMessagePages
```

### E2E Tests (Both Agents)
```bash
php artisan test --filter=DirectMessagingE2E
```

---

## Common Pitfalls & Solutions

### Issue: Mutual follow check is slow
**Solution**: Cache the result for active conversations
```php
Cache::remember("mutual_{$userA->id}_{$userB->id}", 3600, fn() => 
    $userA->mutuals()->where('users.id', $userB->id)->exists()
);
```

### Issue: Conversation not found between users
**Solution**: Check both directions when querying
```php
->whereHas('participants', fn($q) => $q->where('user_id', $userA->id))
->whereHas('participants', fn($q) => $q->where('user_id', $userB->id))
```

### Issue: Real-time updates not working
**Solution**: Verify Echo listener in ChatComponent
```php
public function getListeners()
{
    return [
        "echo:conversation.{$this->conversationId},MessageSent" => 'onMessageReceived',
    ];
}
```

### Issue: Authorization failing
**Solution**: Ensure policy is registered and applied
```php
// In AuthServiceProvider
protected $policies = [
    Conversation::class => ConversationPolicy::class,
];

// In component
$this->authorize('view', $conversation);
```

---

## Integration Points

### Agent A → Agent B Handoffs

1. **After T01**: Provide `ChatService` methods
   - `createDirectMessageConversation()`
   - `isMutualFollower()`

2. **After T03**: Provide `MessageRequestService` methods
   - `getDirectInbox()`
   - `getMessageRequests()`
   - `acceptRequest()`
   - `declineRequest()`

3. **After T06**: Coordinate route names
   - `messages.index`
   - `messages.show`
   - `messages.requests`

4. **After T08**: Provide policy methods
   - `view()`
   - `sendMessage()`
   - `acceptRequest()`

---

## Final Checklist

### Before Marking Complete
- [ ] All tests passing (`php artisan test`)
- [ ] Code formatted (`vendor/bin/pint --dirty`)
- [ ] No console errors in browser
- [ ] Real-time updates working
- [ ] Mobile-responsive design verified
- [ ] Galaxy theme applied consistently
- [ ] Documentation updated
- [ ] Database migrations run successfully
- [ ] Notifications firing correctly
- [ ] Authorization working properly

### User Flows to Test Manually
1. **Mutual Followers DM**:
   - User A and B are mutual followers
   - A sends message to B
   - Message appears in B's direct inbox
   - B receives notification
   - B can reply immediately

2. **Non-Mutual Message Request**:
   - User A follows B, but B doesn't follow A
   - A sends message to B
   - Message appears in B's requests inbox
   - B receives message request notification
   - B can accept or decline
   - If accepted, conversation moves to inbox

3. **Profile Integration**:
   - Visit another user's profile
   - Click "Message" button
   - Redirected to messages page with conversation open
   - Can send message immediately

---

## Performance Benchmarks

- [ ] Inbox loads in < 500ms
- [ ] Message request count query < 100ms
- [ ] Mutual follow check < 50ms (cached)
- [ ] Real-time message delivery < 200ms
- [ ] Page is mobile-responsive (< 768px width)

---

## Next Steps After Completion

1. **Monitor Usage**: Track DM adoption rates
2. **Gather Feedback**: User surveys on messaging experience
3. **Future Enhancements**:
   - Message search
   - Typing indicators
   - Voice messages
   - Group DMs (3+ users)
   - Message reactions (already supported)
   - Read receipts (already supported)

---

**Ready to start?** Begin with Phase 1 (T01-T03) and follow the checklist sequentially!

