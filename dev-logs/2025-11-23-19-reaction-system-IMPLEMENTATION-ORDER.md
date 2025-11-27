# Post Reaction System - Implementation Order
**Date**: 2025-11-23 19:30  
**For**: Agent B (Backend), Agent A (UI/UX), Agent C (Integration)

## Phase 1: Foundation (Agent B) - Day 1

### Step 1.1: Update Reaction Types (30 min)
**Goal**: Rename "join_me" to "invite_friends"

1. Update `app/Models/PostReaction.php`:
   ```php
   return ['im_down', 'invite_friends'];
   ```

2. Create migration:
   ```bash
   php artisan make:migration update_post_reaction_types --no-interaction
   ```

3. Update factory:
   ```php
   'reaction_type' => fake()->randomElement(['im_down', 'invite_friends']),
   ```

4. Run migration:
   ```bash
   php artisan migrate
   ```

**Checkpoint**: ✅ Reaction types updated, no breaking changes

---

### Step 1.2: Create Post Invitations Table (1 hour)
**Goal**: Track friend invitations to posts

1. Create migration:
   ```bash
   php artisan make:migration create_post_invitations_table --no-interaction
   ```

2. Create model:
   ```bash
   php artisan make:model PostInvitation --no-interaction
   ```

3. Create factory:
   ```bash
   php artisan make:factory PostInvitationFactory --no-interaction
   ```

4. Run migration:
   ```bash
   php artisan migrate
   ```

**Checkpoint**: ✅ `post_invitations` table exists, model ready

---

### Step 1.3: Add Invitation Methods to PostService (1 hour)
**Goal**: Business logic for inviting friends

Add to `app/Services/PostService.php`:
- `inviteFriendsToPost()`
- `getPostInvitees()`
- `markInvitationViewed()`
- `getUserPendingInvitations()`

**Checkpoint**: ✅ Can invite friends programmatically

---

## Phase 2: Real-Time Notifications (Agent B) - Day 1-2

### Step 2.1: Create User Broadcast Channel (30 min)
**Goal**: Single channel per user

1. Create channel:
   ```bash
   php artisan make:channel UserChannel --no-interaction
   ```

2. Register in `routes/channels.php`:
   ```php
   Broadcast::channel('user.{userId}', UserChannel::class);
   ```

**Checkpoint**: ✅ Users can subscribe to their own channel

---

### Step 2.2: Create Base Notification Event (1 hour)
**Goal**: Standardized notification structure

1. Create event:
   ```bash
   php artisan make:event UserNotification --no-interaction
   ```

2. Implement `ShouldBroadcast` interface

3. Test broadcasting:
   ```bash
   php artisan tinker
   event(new UserNotification($userId, 'test', 'test', []));
   ```

**Checkpoint**: ✅ Can broadcast to user channels

---

### Step 2.3: Update PostReacted Event (1 hour)
**Goal**: Broadcast reactions in real-time

1. Add `ShouldBroadcast` to `app/Events/PostReacted.php`
2. Implement `broadcastOn()`, `broadcastAs()`, `broadcastWith()`
3. Test by reacting to a post

**Checkpoint**: ✅ Post reactions broadcast in real-time

---

### Step 2.4: Create PostInvitationSent Event (1 hour)
**Goal**: Broadcast invitations in real-time

1. Create event:
   ```bash
   php artisan make:event PostInvitationSent --no-interaction
   ```

2. Implement broadcasting
3. Fire event in `PostService::inviteFriendsToPost()`

**Checkpoint**: ✅ Invitations broadcast in real-time

---

## Phase 3: Notification Persistence (Agent B) - Day 2

### Step 3.1: Create NotificationService (1 hour)
**Goal**: Centralized notification management

1. Create service:
   ```bash
   php artisan make:class Services/NotificationService --no-interaction
   ```

2. Implement methods:
   - `sendNotification()`
   - `markAsRead()`
   - `getUnreadNotifications()`
   - `getUserNotifications()`

**Checkpoint**: ✅ Can save and retrieve notifications

---

### Step 3.2: Create Event Listeners (1 hour)
**Goal**: Save notifications to database

1. Create listener:
   ```bash
   php artisan make:listener SendPostReactionNotification --event=PostReacted --no-interaction
   ```

2. Create listener:
   ```bash
   php artisan make:listener SendPostInvitationNotification --event=PostInvitationSent --no-interaction
   ```

3. Register in `EventServiceProvider`

**Checkpoint**: ✅ Notifications persist in database

---

## Phase 4: API Endpoints (Agent B) - Day 2-3

### Step 4.1: Create Reaction Controller (2 hours)
**Goal**: API for reactions and invitations

1. Create controller:
   ```bash
   php artisan make:controller Api/PostReactionController --no-interaction
   ```

2. Implement methods:
   - `react()`
   - `unreact()`
   - `getReactions()`
   - `invite()`

3. Add routes to `routes/api.php`

**Checkpoint**: ✅ Can react/invite via API

---

### Step 4.2: Add Livewire Actions (1 hour)
**Goal**: Wire up UI to backend

Update `app/Livewire/Posts/PostCard.php`:
- `toggleReaction()`
- `openInviteModal()`
- `inviteFriends()`

**Checkpoint**: ✅ Post card can trigger reactions

---

## Phase 5: Conversion Logic (Agent B) - Day 3

### Step 5.1: Create Conversion Job (1 hour)
**Goal**: Check and trigger conversions

1. Create job:
   ```bash
   php artisan make:job CheckPostConversionEligibility --no-interaction
   ```

2. Implement conversion logic (5+ suggest, 10+ auto-convert)

**Checkpoint**: ✅ Conversion job works

---

### Step 5.2: Create Conversion Listener (1 hour)
**Goal**: Dispatch job on reactions

1. Create listener:
   ```bash
   php artisan make:listener CheckPostConversion --event=PostReacted --no-interaction
   ```

2. Dispatch job only for "im_down" reactions

**Checkpoint**: ✅ Conversions trigger automatically

---

## Phase 6: UI Components (Agent A) - Day 3-4

### Step 6.1: Notification Bell (2 hours)
**Goal**: Show notifications in navbar

1. Create component:
   ```bash
   php artisan make:livewire Notifications/NotificationBell --no-interaction
   ```

2. Add to navbar
3. Show unread count
4. Dropdown with recent notifications

**Checkpoint**: ✅ Notification bell works

---

### Step 6.2: Notifications Page (2 hours)
**Goal**: Full notification history

1. Create component:
   ```bash
   php artisan make:livewire Notifications/Index --no-interaction
   ```

2. List all notifications (paginated)
3. Mark as read/unread
4. Filter by type

**Checkpoint**: ✅ Notifications page works

---

### Step 6.3: Friend Selector Modal (2-3 hours)
**Goal**: Invite friends to posts

1. Create component:
   ```bash
   php artisan make:livewire Posts/InviteFriendsModal --no-interaction
   ```

2. Search friends
3. Multi-select
4. Send invitations

**Checkpoint**: ✅ Can invite friends via modal

---

### Step 6.4: WebSocket Integration (1 hour)
**Goal**: Real-time UI updates

1. Create `resources/js/notifications.js`
2. Subscribe to `user.{userId}` channel
3. Handle notification types
4. Update UI in real-time

**Checkpoint**: ✅ Real-time notifications work

---

## Phase 7: Testing (Agent C) - Day 4-5

### Step 7.1: Unit Tests (2 hours)
- Test reaction creation
- Test invitation creation
- Test conversion logic

### Step 7.2: Feature Tests (2 hours)
- Test API endpoints
- Test notification creation
- Test broadcasting

### Step 7.3: Browser Tests (2 hours)
- Test clicking buttons
- Test modal interactions
- Test real-time updates

**Checkpoint**: ✅ All tests passing

---

## Total Timeline

- **Agent B**: 3-4 days (12-15 hours)
- **Agent A**: 2 days (5-7 hours) - starts Day 3
- **Agent C**: 1-2 days (3-4 hours) - starts Day 4

**Total**: 4-5 days for complete implementation

---

## Daily Checkpoints

### End of Day 1
✅ Reaction types updated
✅ Invitations table created
✅ User broadcast channel working
✅ Base notification event working

### End of Day 2
✅ PostReacted broadcasts in real-time
✅ PostInvitationSent broadcasts in real-time
✅ Notifications persist in database
✅ API endpoints working

### End of Day 3
✅ Conversion logic working
✅ Notification bell in navbar
✅ Livewire actions wired up

### End of Day 4
✅ Notifications page complete
✅ Friend selector modal complete
✅ WebSocket integration complete
✅ Unit tests passing

### End of Day 5
✅ Feature tests passing
✅ Browser tests passing
✅ Documentation complete
✅ **READY FOR PRODUCTION** 🚀

