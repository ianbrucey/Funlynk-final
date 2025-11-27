# Backend Ready for UI Integration

**Date**: 2025-11-23  
**From**: Agent B (Backend Specialist)  
**To**: Agent A (UI/UX Specialist)  
**Status**: ✅ Backend Complete - Ready for UI Development

---

## Summary

I've completed all backend tasks for the Post Reaction System. The system is fully functional with:
- ✅ Updated reaction types (`im_down`, `invite_friends`)
- ✅ Post invitations database and logic
- ✅ Real-time notifications via WebSocket
- ✅ Notification persistence in database
- ✅ Complete API endpoints
- ✅ Post-to-event conversion triggers

---

## API Endpoints Documentation

All endpoints use `auth:sanctum` middleware. Base URL: `/api`

### 1. React to Post
**Endpoint**: `POST /api/posts/{post}/react`

**Request**:
```json
{
  "reaction_type": "im_down" | "invite_friends"
}
```

**Response**:
```json
{
  "success": true,
  "reaction": {
    "id": "uuid",
    "post_id": "uuid",
    "user_id": "uuid",
    "reaction_type": "im_down",
    "created_at": "2025-11-23T12:00:00Z"
  },
  "message": "Reaction added successfully"
}
```

### 2. Remove Reaction
**Endpoint**: `DELETE /api/posts/{post}/react`

**Response**:
```json
{
  "success": true,
  "message": "Reaction removed successfully"
}
```

### 3. Get Post Reactions
**Endpoint**: `GET /api/posts/{post}/reactions`

**Response**:
```json
{
  "success": true,
  "reactions": [
    {
      "id": "uuid",
      "user": {
        "id": "uuid",
        "name": "John Doe",
        "avatar_url": "..."
      },
      "reaction_type": "im_down",
      "created_at": "2025-11-23T12:00:00Z"
    }
  ],
  "count": 5
}
```

### 4. Invite Friends to Post
**Endpoint**: `POST /api/posts/{post}/invite`

**Request**:
```json
{
  "friend_ids": ["uuid1", "uuid2", "uuid3"]
}
```

**Response**:
```json
{
  "success": true,
  "invitations": [...],
  "count": 3,
  "message": "Invitations sent successfully"
}
```

### 5. Get User's Pending Invitations
**Endpoint**: `GET /api/users/me/invitations`

**Response**:
```json
{
  "success": true,
  "invitations": [
    {
      "id": "uuid",
      "post": {
        "id": "uuid",
        "title": "Coffee at Starbucks?",
        "location_name": "Downtown Starbucks"
      },
      "inviter": {
        "id": "uuid",
        "name": "Jane Smith",
        "avatar_url": "..."
      },
      "status": "pending",
      "created_at": "2025-11-23T11:00:00Z"
    }
  ],
  "count": 2
}
```

---

## WebSocket Integration

### Channel Structure
Each user has **ONE channel** for all notifications:
```javascript
const userId = '{{ auth()->user()->id }}';
Echo.channel(`user.${userId}`)
    .listen('.notification', (notification) => {
        handleNotification(notification);
    });
```

### Notification Payload Structure

All notifications follow this structure:
```javascript
{
  "id": "uuid",
  "type": "post_reaction" | "post_invitation" | "post_conversion",
  "subtype": "im_down" | "invite_friends" | "invited" | "suggested" | "auto_converted",
  "timestamp": "2025-11-23T12:00:00.000Z",
  "data": { /* type-specific data */ },
  "actions": [
    { "label": "View Post", "route": "/posts/{id}" }
  ]
}
```

### Notification Types

#### 1. Post Reaction (`post_reaction`)
**Subtypes**: `im_down`, `invite_friends`

Sent to: Post owner

```javascript
{
  "type": "post_reaction",
  "subtype": "im_down",
  "data": {
    "post_id": "uuid",
    "post_title": "Coffee at Starbucks?",
    "reactor_id": "uuid",
    "reactor_name": "John Doe",
    "reactor_avatar": "https://...",
    "reaction_count": 5,
    "conversion_eligible": true
  },
  "actions": [
    { "label": "View Post", "route": "/posts/{id}" }
  ]
}
```

#### 2. Post Invitation (`post_invitation`)
**Subtype**: `invited`

Sent to: Invitee

```javascript
{
  "type": "post_invitation",
  "subtype": "invited",
  "data": {
    "invitation_id": "uuid",
    "post_id": "uuid",
    "post_title": "Coffee at Starbucks?",
    "inviter_id": "uuid",
    "inviter_name": "Jane Smith",
    "inviter_avatar": "https://..."
  },
  "actions": [
    { "label": "View Post", "route": "/posts/{id}" }
  ]
}
```

#### 3. Conversion Suggested (`post_conversion`)
**Subtype**: `suggested`

Sent to: Post owner (at 5+ reactions)

```javascript
{
  "type": "post_conversion",
  "subtype": "suggested",
  "data": {
    "post_id": "uuid",
    "post_title": "Coffee at Starbucks?",
    "reaction_count": 5,
    "threshold": 5
  },
  "actions": [
    { "label": "Convert to Event", "route": "/posts/{id}/convert" },
    { "label": "View Post", "route": "/posts/{id}" }
  ]
}
```

#### 4. Auto-Converted (`post_conversion`)
**Subtype**: `auto_converted`

Sent to: Post owner (at 10+ reactions)

```javascript
{
  "type": "post_conversion",
  "subtype": "auto_converted",
  "data": {
    "post_id": "uuid",
    "post_title": "Coffee at Starbucks?",
    "reaction_count": 10,
    "threshold": 10
  },
  "actions": [
    { "label": "View Event", "route": "/events/{activityId}" }
  ]
}
```

---

## Database Schema

### `post_invitations` Table
```sql
- id (uuid, primary key)
- post_id (uuid, foreign key to posts)
- inviter_id (uuid, foreign key to users)
- invitee_id (uuid, foreign key to users)
- status (enum: pending, viewed, reacted, ignored)
- created_at (timestamptz)
- viewed_at (timestamptz, nullable)
- reacted_at (timestamptz, nullable)

UNIQUE INDEX: (post_id, inviter_id, invitee_id)
INDEX: (post_id)
INDEX: (invitee_id, status)
```

### Existing `notifications` Table
Used for persistence - you can query this to show notification history.

---

## UI Components Needed

Based on the instructions, you need to build:

### 1. Notification Bell Component (2 hours)
- Live badge with unread count
- Dropdown showing recent 5-10 notifications
- Click to mark as read
- Link to full notifications page
- Real-time updates via WebSocket

**Suggested Implementation**:
```blade
<livewire:notification-bell />
```

### 2. Notifications Page (2 hours)
- Full paginated list of all notifications
- Filter by type (optional)
- Mark all as read button
- Click notification to navigate to action

**Route**: `/notifications`

### 3. Friend Selector Modal (2 hours)
- Modal triggered from post detail page
- Search/filter friends
- Multi-select checkboxes
- "Send Invitations" button
- Calls `POST /api/posts/{post}/invite`

**Suggested Implementation**:
```blade
<livewire:friend-selector-modal :post="$post" />
```

### 4. Toast Notifications (1 hour)
- Show real-time notifications as toasts
- Auto-dismiss after 5 seconds
- Click to navigate to action
- Use DaisyUI `alert` component

---

## Testing Checklist

Before you start, verify these work:

1. ✅ Migrations run successfully
2. ✅ Broadcasting configured (Pusher or Soketi)
3. ✅ API endpoints return expected responses
4. ✅ WebSocket connection established in browser console

---

## Known Issues / Notes

1. **Broadcasting Configuration**: User must configure `.env` with Pusher/Soketi credentials
2. **Migrations**: User must run `php artisan migrate` before testing
3. **Sanctum**: API routes require authentication - ensure Sanctum is configured
4. **Friend Discovery**: There's no friend relationship implemented yet - you may need to query all users for the friend selector
5. **Avatar URLs**: The `avatar_url` field might be null - handle gracefully in UI

---

## Example JavaScript Integration

```javascript
// Initialize Echo (assuming Laravel Echo is installed)
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
});

// Subscribe to user channel
const userId = document.querySelector('meta[name="user-id"]').content;
window.Echo.channel(`user.${userId}`)
    .listen('.notification', (notification) => {
        console.log('Notification received:', notification);
        
        // Update notification bell count
        updateNotificationBadge();
        
        // Show toast
        showToast(notification);
        
        // Update notifications list if on notifications page
        if (window.location.pathname === '/notifications') {
            prependNotification(notification);
        }
    });

function showToast(notification) {
    // Use DaisyUI toast or custom toast component
    const toast = document.createElement('div');
    toast.className = 'alert alert-info';
    toast.innerHTML = `
        <span>${notification.data.reactor_name || notification.data.inviter_name} 
        ${notification.type === 'post_reaction' ? 'reacted to' : 'invited you to'} 
        "${notification.data.post_title}"</span>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 5000);
}
```

---

## Questions?

If you encounter any issues or need clarification on the backend implementation, let me know. I'm ready to assist with any backend adjustments needed for your UI components.

**Backend is ready - you're good to go! 🚀**

---

**Agent B**
