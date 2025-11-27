# Post Reaction System - Backend Plan (Part 3)
**Continuation of**: `2025-11-23-19-reaction-system-backend-plan-part2.md`

---

#### 4.3 Update Event Listeners
**File**: `app/Listeners/SendPostReactionNotification.php`

**Implementation**:
```php
public function handle(PostReacted $event): void
{
    // Save to database
    $notification = Notification::create([
        'user_id' => $event->post->user_id,
        'type' => 'post_reaction',
        'title' => "{$event->reaction->user->name} reacted to your post",
        'message' => "Someone is down for \"{$event->post->title}\"",
        'data' => [
            'post_id' => $event->post->id,
            'reactor_id' => $event->reaction->user_id,
            'reaction_type' => $event->reaction->reaction_type,
            'conversion_eligible' => $event->conversionEligibility['eligible'],
        ],
    ]);
    
    // Real-time broadcast happens automatically via PostReacted event
}
```

---

### **Task 5: API Endpoints** (Agent B)
**Priority**: P1 - Core Feature
**Epic**: E04 Discovery Engine
**Estimated Time**: 2 hours

#### 5.1 Reaction Endpoints
**File**: `routes/api.php` or `routes/web.php`

**Endpoints**:
```php
// React to post
POST /api/posts/{post}/react
Body: { "reaction_type": "im_down" }
Response: { "success": true, "reaction": {...}, "conversion_eligible": {...} }

// Remove reaction
DELETE /api/posts/{post}/react

// Get post reactions
GET /api/posts/{post}/reactions
Response: { "reactions": [...], "counts": { "im_down": 5, "invite_friends": 2 } }

// Invite friends to post
POST /api/posts/{post}/invite
Body: { "friend_ids": ["uuid1", "uuid2"] }
Response: { "success": true, "invitations": [...] }

// Get user's pending invitations
GET /api/users/me/invitations
Response: { "invitations": [...] }
```

#### 5.2 Create API Controller
**File**: `app/Http/Controllers/Api/PostReactionController.php`

**Methods**:
```php
public function react(Request $request, Post $post): JsonResponse
public function unreact(Post $post): JsonResponse
public function getReactions(Post $post): JsonResponse
public function invite(Request $request, Post $post): JsonResponse
```

#### 5.3 Create Livewire Actions (Alternative to API)
**File**: `app/Livewire/Posts/PostCard.php`

**Methods**:
```php
public function toggleReaction(string $reactionType): void
public function openInviteModal(): void
public function inviteFriends(array $friendIds): void
```

---

### **Task 6: Notification UI Components** (Agent A)
**Priority**: P2 - UI Enhancement
**Epic**: E05 Social Interaction
**Estimated Time**: 3-4 hours

#### 6.1 Create Notification Bell Component
**File**: `resources/views/livewire/notifications/notification-bell.blade.php`

**Features**:
- Bell icon in navbar
- Unread count badge
- Dropdown with recent notifications
- "Mark all as read" button
- Link to full notifications page

#### 6.2 Create Notification Item Component
**File**: `resources/views/components/notification-item.blade.php`

**Features**:
- Icon based on notification type
- User avatar
- Notification message
- Timestamp (relative: "2m ago")
- Action buttons
- Read/unread indicator

#### 6.3 Create Notifications Page
**File**: `resources/views/livewire/notifications/index.blade.php`

**Features**:
- List all notifications (paginated)
- Filter by type
- Mark as read/unread
- Delete notifications
- Empty state

#### 6.4 WebSocket Integration (JavaScript)
**File**: `resources/js/notifications.js`

**Implementation**:
```javascript
// Subscribe to user's notification channel
Echo.channel(`user.${userId}`)
    .listen('.notification', (notification) => {
        // Update notification bell count
        updateNotificationCount();
        
        // Show toast notification
        showToast(notification);
        
        // Route to appropriate handler
        handleNotification(notification);
    });

function handleNotification(notification) {
    switch(notification.type) {
        case 'post_reaction':
            handlePostReaction(notification);
            break;
        case 'post_invitation':
            handlePostInvitation(notification);
            break;
        // ... other types
    }
}
```

---

### **Task 7: Friend Selector Modal** (Agent A)
**Priority**: P2 - UI Enhancement
**Epic**: E04 Discovery Engine
**Estimated Time**: 2-3 hours

#### 7.1 Create Friend Selector Component
**File**: `resources/views/livewire/posts/invite-friends-modal.blade.php`

**Features**:
- Search friends by name
- Show mutual friends first
- Multi-select with checkboxes
- Selected friends counter
- "Invite" button
- Close modal on success

#### 7.2 Integration with Post Card
**File**: `resources/views/components/post-card.blade.php`

**Add**:
- "Invite Friends" button
- Wire up to modal
- Show invitation count ("3 friends invited")

---

### **Task 8: Post-to-Event Conversion Trigger** (Agent B)
**Priority**: P1 - Core Feature
**Epic**: E03 Activity Management + E04 Discovery Engine
**Estimated Time**: 2-3 hours

#### 8.1 Create Conversion Job
**File**: `app/Jobs/CheckPostConversionEligibility.php`

**Implementation**:
```php
public function handle(): void
{
    // Check if post reached 10+ reactions
    if ($this->post->reaction_count >= 10) {
        // Auto-convert to event
        $activity = app(ActivityConversionService::class)->createFromPost($this->post);
        
        // Notify creator
        event(new PostAutoConvertedToEvent($this->post, $activity));
    } 
    elseif ($this->post->reaction_count >= 5) {
        // Suggest conversion
        event(new PostConversionSuggested($this->post));
    }
}
```

#### 8.2 Dispatch Job on Reaction
**File**: `app/Listeners/CheckPostConversion.php`

**Implementation**:
```php
public function handle(PostReacted $event): void
{
    // Only check if reaction is "im_down"
    if ($event->reaction->reaction_type === 'im_down') {
        CheckPostConversionEligibility::dispatch($event->post);
    }
}
```

---

## Testing Requirements

### Unit Tests (Agent B)
**File**: `tests/Unit/PostReactionTest.php`
- Test reaction creation
- Test reaction type validation
- Test conversion eligibility logic
- Test invitation creation

### Feature Tests (Agent B)
**File**: `tests/Feature/PostReactionApiTest.php`
- Test react endpoint
- Test unreact endpoint
- Test invite friends endpoint
- Test notification creation

### Browser Tests (Agent A)
**File**: `tests/Browser/PostReactionTest.php`
- Test clicking "I'm Down" button
- Test clicking "Invite Friends" button
- Test friend selector modal
- Test real-time notification display

---

## Configuration

### Broadcasting Configuration
**File**: `config/broadcasting.php`

**Ensure**:
- Laravel Echo configured
- Pusher or Soketi configured
- Channels properly registered

### Environment Variables
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
```

---

## Agent Assignment

### Agent B (Backend Specialist)
**Tasks**: 1, 2, 3, 4, 5, 8
**Estimated Time**: 12-15 hours
**Priority**: Complete Tasks 1-3 first (foundation), then 4-5 (API), then 8 (conversion)

### Agent A (UI/UX Specialist)
**Tasks**: 6, 7
**Estimated Time**: 5-7 hours
**Priority**: Wait for Agent B to complete Tasks 1-5, then build UI

### Agent C (Integration Specialist)
**Tasks**: Testing, Integration, Documentation
**Estimated Time**: 3-4 hours
**Priority**: After Agent A & B complete their tasks

---

## Success Criteria

✅ Users can click "I'm Down" and see real-time notification
✅ Users can click "Invite Friends" and select friends
✅ Invited friends receive real-time notification
✅ Post creator sees all reactions in real-time
✅ Posts with 5+ reactions show "Convert to Event" suggestion
✅ Posts with 10+ reactions auto-convert to events
✅ All notifications persist in database
✅ Notification bell shows unread count
✅ Single WebSocket channel per user (`user.{userId}`)
✅ All tests passing

---

## Next Steps

1. **Agent B**: Start with Task 1 (Update Database Schema)
2. **Agent B**: Continue with Task 2 (Friend Invitation System)
3. **Agent B**: Implement Task 3 (Real-Time Notifications)
4. **Agent A**: Review plan and prepare UI mockups
5. **All**: Review and approve plan before implementation

---

**Questions? Concerns? Ready to start?** 🚀

