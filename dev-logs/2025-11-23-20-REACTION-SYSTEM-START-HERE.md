# Post Reaction System - START HERE
**Date**: 2025-11-23 20:00  
**Status**: Ready for Implementation  
**Total Estimated Time**: 20-26 hours (4-5 days)

## What We're Building

Complete post reaction system with real-time notifications:
- **"I'm Down"**: Signal intent to participate (triggers post-to-event conversion)
- **"Invite Friends"**: Share post with selected friends (renamed from "Join Me")
- **Real-Time Notifications**: Single WebSocket channel per user (`user.{userId}`)
- **Notification Bell**: Show unread count and recent notifications
- **Friend Selector Modal**: Invite friends to posts

## Architecture Decision

**Single Channel Per User**: Each user subscribes to ONE channel (`user.{userId}`) that receives ALL notification types. Client-side routing handles different notification types.

**Benefits**:
- One WebSocket connection per user
- Easy to scale
- Simple to manage
- Extensible for future notification types

---

## Agent Instructions

### 📋 Agent B (Backend Specialist)
**File**: `dev-logs/2025-11-23-20-agent-b-reaction-system.md`  
**Time**: 12-15 hours  
**Start**: Immediately

**Tasks**:
1. Update reaction types (30 min)
2. Create post invitations system (2 hours)
3. Real-time notification system (3 hours)
4. Notification persistence (1 hour)
5. API endpoints (2 hours)
6. Post-to-event conversion (2 hours)

**Key Deliverables**:
- ✅ Reaction types: `im_down`, `invite_friends`
- ✅ `post_invitations` table and model
- ✅ WebSocket channel: `user.{userId}`
- ✅ Events: `PostReacted`, `PostInvitationSent`
- ✅ API endpoints for reactions and invitations
- ✅ Conversion triggers at 5+ and 10+ reactions

---

### 🎨 Agent A (UI/UX Specialist)
**File**: `dev-logs/2025-11-23-20-agent-a-reaction-system.md`  
**Time**: 5-7 hours  
**Start**: After Agent B completes Tasks 1-5

**Tasks**:
1. Notification bell component (2 hours)
2. Notifications page (2 hours)
3. Friend selector modal (2 hours)
4. WebSocket integration (1 hour)

**Key Deliverables**:
- ✅ Notification bell in navbar with unread count
- ✅ Dropdown with recent notifications
- ✅ Full notifications page
- ✅ Friend selector modal
- ✅ Real-time toast notifications
- ✅ JavaScript WebSocket integration

---

### 🧪 Agent C (Integration Specialist)
**File**: `dev-logs/2025-11-23-20-agent-c-reaction-system.md`  
**Time**: 3-4 hours  
**Start**: After Agent A & B complete all tasks

**Tasks**:
1. Backend integration testing (1 hour)
2. Write feature tests (2 hours)
3. API endpoint testing (1 hour)
4. UI testing (30 min)
5. Documentation (30 min)

**Key Deliverables**:
- ✅ Comprehensive test suite
- ✅ API endpoint verification
- ✅ UI/UX testing report
- ✅ Documentation updates
- ✅ Integration checklist

---

## Implementation Timeline

### Day 1 (Agent B)
- ✅ Update reaction types
- ✅ Create post invitations system
- ✅ Set up WebSocket channels
- ✅ Implement real-time broadcasting

### Day 2 (Agent B)
- ✅ Notification persistence
- ✅ API endpoints
- ✅ Post-to-event conversion logic

### Day 3 (Agent A starts)
- ✅ Agent B: Finalize and test backend
- ✅ Agent A: Notification bell component
- ✅ Agent A: Notifications page

### Day 4 (Agent A continues)
- ✅ Agent A: Friend selector modal
- ✅ Agent A: WebSocket integration
- ✅ Agent C: Start testing

### Day 5 (Agent C)
- ✅ Agent C: Complete all testing
- ✅ Agent C: Documentation
- ✅ All: Final integration testing

---

## Environment Setup

**Required**:
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

### Backend (Agent B)
✅ Reaction types updated to `im_down` and `invite_friends`
✅ Post invitations table and model created
✅ Real-time notifications broadcast to `user.{userId}` channel
✅ Notifications persist in database
✅ API endpoints work correctly
✅ Conversion triggers at 5+ and 10+ reactions
✅ All backend tests passing

### Frontend (Agent A)
✅ Notification bell shows unread count
✅ Dropdown shows recent notifications
✅ Notifications page displays all notifications
✅ Friend selector modal works
✅ Real-time notifications appear as toasts
✅ WebSocket connection stable
✅ Galaxy theme consistent throughout

### Integration (Agent C)
✅ All feature tests passing
✅ API endpoints verified
✅ Real-time notifications working end-to-end
✅ UI components functional
✅ Documentation complete
✅ No console errors
✅ No breaking changes

---

## Communication Protocol

### Agent B → Agent A
When Tasks 1-5 complete, notify Agent A:
- "Backend ready for UI integration"
- Provide API endpoint documentation
- Share WebSocket channel structure

### Agent A → Agent C
When all UI tasks complete, notify Agent C:
- "UI ready for testing"
- Provide list of components to test
- Share any known issues

### Agent C → All
When testing complete, notify all:
- Share test results
- Report any bugs found
- Confirm ready for production

---

## Quick Reference

### Notification Payload Structure
```json
{
  "id": "uuid",
  "type": "post_reaction|post_invitation|post_conversion",
  "subtype": "im_down|invite_friends|suggested|auto_converted",
  "timestamp": "ISO8601",
  "data": { /* type-specific data */ },
  "actions": [{ "label": "string", "route": "string" }]
}
```

### WebSocket Channel
```javascript
Echo.channel(`user.${userId}`)
    .listen('.notification', (notification) => {
        // Handle notification
    });
```

### React to Post
```php
$service = app(PostService::class);
$reaction = $service->reactToPost($postId, 'im_down', $user);
```

### Invite Friends
```php
$invitations = $service->inviteFriendsToPost($postId, $friendIds, $inviter);
```

---

## Questions?

- **Agent B**: Start with your instruction file
- **Agent A**: Wait for Agent B's signal, then start
- **Agent C**: Wait for Agent A's signal, then start

**Let's build this! 🚀**

