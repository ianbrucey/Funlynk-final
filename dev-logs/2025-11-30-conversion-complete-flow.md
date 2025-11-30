# Complete Post-to-Event Conversion Flow

**Date**: 2025-11-30
**Status**: ✅ FULLY IMPLEMENTED

## What Happens When You Convert a Post to Event

### Step 1: User Clicks "Convert to Event" Button
- **Location**: Post detail page (`/posts/{uuid}`)
- **Condition**: Button only shows if:
  - Post has 1+ reactions (soft threshold)
  - Post status is 'active'
  - User is the post owner

### Step 2: Smart Event Creation
- **Method**: `PostDetail::convertToEvent()`
- **Smart Defaults**:
  - Title: Uses post title
  - Description: Uses post description (or title if empty)
  - Location: Uses post location and coordinates
  - Start Time: Uses post's approximate_time (or tomorrow 6 PM)
    - If past time, automatically moves to tomorrow at same hour
  - End Time: Start time + 2 hours
  - Max Attendees: reaction_count × 1.5 (minimum 10)
  - Price: Free ($0)

### Step 3: Database Transaction
- **Service**: `ActivityConversionService::createFromPost()`
- **Creates**:
  1. New `Activity` record with all event data
  2. `PostConversion` record linking post to event
  3. Updates post status to 'converted'

### Step 4: Event Dispatch
- **Event**: `PostConvertedToEvent` is dispatched
- **Listeners**:
  1. **MigratePostInvitations**: Notifies people invited to the post
  2. **NotifyInterestedUsers**: Notifies people who reacted "I'm down"

### Step 5: Notifications Sent
- **To Interested Users** (who reacted "I'm down"):
  - Notification: "🎉 Post Became an Event!"
  - Message: "[Host] created an event based on the post you were interested in."
  - Data: Event details (location, price, start time, link)
  - **NO automatic RSVP** - Users must manually RSVP

- **To Invited Users** (who were invited to the post):
  - Notification: Post invitation migrated to event
  - Status: Changed from 'pending' to 'migrated'

### Step 6: User Redirect
- **Redirect**: To event detail page (`/activities/{uuid}`)
- **Success Message**: "🎉 Post converted to event successfully!"

---

## Notification Display

### In Notification Bell

**Interested Users See**:
```
🎉 Post Became an Event!
[Host Name] created an event based on the post you were interested in.
📍 [Location] · 🎉 Free · [Time]
```

**Click** → Goes to event detail page

---

## Key Features

✅ **Smart Defaults** - Event data pre-filled from post
✅ **No Manual RSVPs** - Users choose to RSVP manually
✅ **Respects User Agency** - Notifications, not forced participation
✅ **Invited Users Migrated** - Post invitations become event invitations
✅ **Rich Notifications** - Includes event details in notification
✅ **Error Handling** - User-friendly error messages
✅ **Loading States** - Visual feedback during conversion
✅ **Conditional Display** - Button only shows when eligible

---

## Testing

### Manual Test Steps

1. Login as post owner
2. Create a post with location and time
3. Have other users react "I'm down" (2+ reactions)
4. Go to post detail page
5. See "Convert to Event" button
6. Click button
7. See loading state
8. Redirect to event page
9. Login as interested user
10. See notification in bell
11. Click notification
12. Go to event detail page
13. Can RSVP manually

### Automated Tests

```bash
php artisan test tests/Feature/Listeners/NotifyInterestedUsersTest.php
```

**Results**: ✅ 2 passed (10 assertions)

---

## Files Involved

### Core Conversion
- `app/Services/ActivityConversionService.php` - Creates event
- `app/Services/PostService.php` - Validates and orchestrates
- `app/Livewire/Posts/PostDetail.php` - UI component

### Notifications
- `app/Listeners/NotifyInterestedUsers.php` - Sends notifications
- `app/Listeners/MigratePostInvitations.php` - Migrates invitations
- `resources/views/livewire/notifications/notification-bell.blade.php` - Display

### UI
- `resources/views/livewire/posts/post-detail.blade.php` - Convert button
- `resources/css/galaxy-theme.css` - Pulse animation

---

## Summary

**When you convert a post to an event:**
- ✅ Event is created with smart defaults
- ✅ All interested users get notifications
- ✅ Invited users are migrated
- ✅ NO automatic RSVPs (respects user choice)
- ✅ Users can manually RSVP from event page

