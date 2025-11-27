# Post Reaction System - Implementation Summary
**Date**: 2025-11-23 19:30  
**Status**: Planning Complete - Ready for Implementation

## Quick Overview

### What We're Building
Complete backend + frontend for post reactions with real-time notifications:
1. **"I'm Down"** - Signal intent to participate (triggers post-to-event conversion)
2. **"Invite Friends"** - Share post with selected friends (renamed from "Join Me")

### Key Architecture Decision
**Single Channel Per User**: Each user has ONE WebSocket channel (`user.{userId}`) that receives ALL notifications. Client-side routing handles different notification types.

---

## Full Documentation

This plan is split across 3 files for readability:

1. **Part 1** (`2025-11-23-19-reaction-system-backend-plan.md`)
   - Overview & reaction types
   - Single-channel architecture
   - Task 1: Database schema updates
   - Task 2: Friend invitation system
   - Task 3: Real-time notification system (partial)

2. **Part 2** (`2025-11-23-19-reaction-system-backend-plan-part2.md`)
   - Task 3: Real-time notification system (continued)
   - Task 4: Notification persistence

3. **Part 3** (`2025-11-23-19-reaction-system-backend-plan-part3.md`)
   - Task 5: API endpoints
   - Task 6: Notification UI components
   - Task 7: Friend selector modal
   - Task 8: Post-to-event conversion trigger
   - Testing requirements
   - Configuration
   - Agent assignments
   - Success criteria

---

## Quick Task Checklist

### Agent B (Backend) - 12-15 hours
- [ ] **Task 1**: Update database schema (30 min)
  - Rename `join_me` → `invite_friends`
  - Update model validation
  - Create migration
  
- [ ] **Task 2**: Friend invitation system (2-3 hours)
  - Create `post_invitations` table
  - Create `PostInvitation` model
  - Add methods to `PostService`
  
- [ ] **Task 3**: Real-time notifications (3-4 hours)
  - Create `UserChannel` broadcast channel
  - Create `UserNotification` base event
  - Update `PostReacted` event with broadcasting
  - Create `PostInvitationSent` event
  
- [ ] **Task 4**: Notification persistence (1-2 hours)
  - Create `NotificationService`
  - Create event listeners
  - Save notifications to database
  
- [ ] **Task 5**: API endpoints (2 hours)
  - React/unreact endpoints
  - Invite friends endpoint
  - Get reactions endpoint
  - Get invitations endpoint
  
- [ ] **Task 8**: Conversion trigger (2-3 hours)
  - Create `CheckPostConversionEligibility` job
  - Create event listener
  - Integrate with `ActivityConversionService`

### Agent A (UI/UX) - 5-7 hours
- [ ] **Task 6**: Notification UI (3-4 hours)
  - Notification bell component
  - Notification item component
  - Notifications page
  - WebSocket integration (JavaScript)
  
- [ ] **Task 7**: Friend selector modal (2-3 hours)
  - Friend selector component
  - Search functionality
  - Multi-select UI
  - Integration with post card

### Agent C (Integration) - 3-4 hours
- [ ] Write unit tests
- [ ] Write feature tests
- [ ] Write browser tests
- [ ] Integration testing
- [ ] Documentation updates

---

## Key Files to Create/Modify

### New Files (Agent B)
```
database/migrations/2025_11_23_XXXXXX_update_post_reaction_types.php
database/migrations/2025_11_23_XXXXXX_create_post_invitations_table.php
app/Models/PostInvitation.php
app/Broadcasting/UserChannel.php
app/Events/UserNotification.php
app/Events/PostInvitationSent.php
app/Services/NotificationService.php
app/Listeners/SendPostReactionNotification.php
app/Listeners/CheckPostConversion.php
app/Http/Controllers/Api/PostReactionController.php
app/Jobs/CheckPostConversionEligibility.php
```

### Modified Files (Agent B)
```
app/Models/PostReaction.php (update validReactionTypes)
database/factories/PostReactionFactory.php (update reaction types)
app/Services/PostService.php (add invitation methods)
app/Events/PostReacted.php (add broadcasting)
routes/channels.php (register UserChannel)
routes/api.php (add reaction endpoints)
```

### New Files (Agent A)
```
resources/views/livewire/notifications/notification-bell.blade.php
resources/views/components/notification-item.blade.php
resources/views/livewire/notifications/index.blade.php
resources/views/livewire/posts/invite-friends-modal.blade.php
resources/js/notifications.js
```

### Modified Files (Agent A)
```
resources/views/components/navbar.blade.php (add notification bell)
resources/views/components/post-card.blade.php (wire up invite button)
```

---

## Notification Payload Structure

All notifications follow this structure:

```json
{
  "id": "notif-uuid",
  "type": "post_reaction|post_invitation|post_conversion",
  "subtype": "im_down|invite_friends|suggested|auto_converted",
  "timestamp": "2025-11-23T19:30:00Z",
  "data": {
    "post_id": "uuid",
    "post_title": "string",
    "actor_id": "uuid",
    "actor_name": "string",
    "actor_avatar": "url",
    // ... type-specific data
  },
  "actions": [
    {"label": "View Post", "route": "/posts/{id}"},
    {"label": "Action", "route": "/path"}
  ]
}
```

---

## WebSocket Channel Structure

**Channel Name**: `user.{userId}`
**Event Name**: `notification`
**Authorization**: User can only join their own channel

**Example**:
```javascript
Echo.channel('user.123e4567-e89b-12d3-a456-426614174000')
    .listen('.notification', (notification) => {
        console.log(notification);
    });
```

---

## Post-to-Event Conversion Flow

1. User clicks "I'm Down" on post
2. `PostService::reactToPost()` creates reaction
3. `PostReacted` event fires
4. `CheckPostConversion` listener dispatches job
5. Job checks reaction count:
   - **5+ reactions**: Fire `PostConversionSuggested` event
   - **10+ reactions**: Call `ActivityConversionService::createFromPost()`
6. Notifications sent to post creator

---

## Environment Setup Required

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
```

**Alternative**: Use Soketi (open-source Pusher alternative)

---

## Success Criteria

✅ "I'm Down" button works and sends real-time notification
✅ "Invite Friends" button opens modal and sends invitations
✅ Invited friends receive real-time notifications
✅ Post creator sees all reactions in real-time
✅ Posts with 5+ reactions show conversion suggestion
✅ Posts with 10+ reactions auto-convert to events
✅ All notifications persist in database
✅ Notification bell shows unread count
✅ Single WebSocket channel per user
✅ All tests passing

---

## Ready to Start?

**Agent B**: Begin with Task 1 (database schema)
**Agent A**: Review plan and wait for Agent B to complete Tasks 1-5
**Agent C**: Prepare test plans

**Questions?** Review the full plan in the 3-part documentation files.

