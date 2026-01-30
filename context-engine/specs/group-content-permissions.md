# Group Content Permissions System

## Overview
Configurable permissions for group content creation (posts, events) with simplified post form and pinned posts for announcements.

## Phase 1: Permission System

### Database Changes
Add to `groups` table:
```sql
post_permission ENUM('everyone', 'admins') DEFAULT 'everyone'
event_permission ENUM('everyone', 'admins') DEFAULT 'admins'
```

### Permission Logic
- `everyone`: All group members can create
- `admins`: Only members with role='admin' can create

### UI Changes
- Hide create buttons based on permissions
- Add settings in group admin panel

## Phase 2: Simplified Post Form

### Current Form (Remove)
- Title
- Description  
- Location Name (Optional)
- Expires At (datetime picker)
- Tags (multi-select)

### New Form (Simplified)
- Content (textarea, placeholder: "What's happening?")
- Location (optional, inline toggle)
- Expires: Dropdown [24 hours, 48 hours, 1 week]

### Rationale
Group posts are quick, ephemeral thoughts - not formal content.

## Phase 3: Pinned Posts (Announcements)

### Database Changes
Add to `posts` table:
```sql
is_pinned BOOLEAN DEFAULT FALSE
pinned_at TIMESTAMP NULL
pinned_by UUID NULL REFERENCES users(id)
```

### Behavior
- Only admins can pin/unpin posts
- Pinned posts appear at top of timeline
- Max 3 pinned posts per group
- Pinned posts don't expire (or have extended expiry)

## Implementation Order
1. Migration for group permissions
2. Update Group model with permission helpers
3. Update timeline UI to check permissions
4. Simplify CreateGroupPost form
5. Add permission settings to group admin
6. Migration for pinned posts
7. Pin/unpin UI in timeline

