# Agent 2: Notifications & Real-Time Updates

## Project Context
- **Project:** FunLynk - Laravel 12 activity discovery platform
- **Tech Stack:** Laravel 12, Livewire v3, Laravel Echo (if configured), Database notifications
- **Notification System:** Uses Laravel's notification system with database driver

## Your Focus Area
Fix notification content and investigate real-time delivery.

---

## Task 1: Fix Join Request Notification - Missing User Name/Avatar
**Priority: HIGH**

**Problem:** When someone requests to join a group, the notification doesn't show WHO requested (no name, no profile picture).

**Files to Investigate:**
- `app/Notifications/GroupNotification.php`
- `app/Listeners/SendGroupNotification.php` (handleGroupJoinRequestReceived method)
- `resources/views/livewire/notifications/` (check how notifications are rendered)

**Requirements:**
1. Ensure the notification payload includes:
   - `actor_id` - the user who triggered the notification
   - `actor_name` - their display name
   - `actor_avatar` - their profile image URL
2. Update notification rendering to display actor info (name + avatar)

**Current Code in SendGroupNotification.php (~line 157):**
```php
$admin->notify(new GroupNotification(
    'Join Request',
    $user->name.' requested to join your group '.$group->name,
    'group-join-request',
    $group->id,
    $joinRequest->id
));
```

**Should include actor data in the notification payload.**

---

## Task 2: Audit All Group Notifications for Actor Data
**Priority: MEDIUM**

**Files:** `app/Listeners/SendGroupNotification.php`

Check these event handlers and ensure all include actor info:
- `handleGroupMemberJoined` 
- `handleGroupMemberLeft`
- `handleGroupPostCreated`
- `handleGroupEventCreated`
- `handleGroupJoinRequestReceived`
- `handleGroupJoinRequestApproved`
- `handleGroupJoinRequestDenied`

---

## Task 3: Investigate Real-Time Notification Delivery
**Priority: LOW (Investigation only)**

**Problem:** User reported no real-time notifications when someone joins/requests.

**Investigation Steps:**
1. Check if Laravel Echo/Pusher/Reverb is configured in `.env`
2. Check `config/broadcasting.php` for driver
3. Check if notifications implement `ShouldBroadcast`
4. Check if queue worker is running (notifications may be queued)

**Files to Check:**
- `.env` (BROADCAST_DRIVER, PUSHER_* or REVERB_*)
- `app/Notifications/GroupNotification.php` (does it implement ShouldBroadcast?)
- `config/broadcasting.php`

**Note:** This may be a configuration issue, not a code bug. Document findings.

---

## Task 4: Update Notification Display Component
**Priority: HIGH**

**Files to Modify:**
- `resources/views/livewire/notifications/notification-list.blade.php` (or similar)

**Requirements:**
1. When rendering a notification, check for `actor_avatar` and `actor_name` in data
2. Display small avatar + name before the notification message
3. Facebook-style: "[Avatar] John Doe requested to join Your Group"

**Pattern:**
```blade
@if(isset($notification->data['actor_avatar']))
    <img src="{{ $notification->data['actor_avatar'] }}" class="w-8 h-8 rounded-full">
@endif
<span class="font-semibold">{{ $notification->data['actor_name'] ?? 'Someone' }}</span>
{{ $notification->data['message'] }}
```

---

## Testing Checklist
- [ ] Join request notification shows requester's name and avatar
- [ ] Member joined notification shows the new member info
- [ ] Notifications render correctly in the notification list
- [ ] Document real-time status (working/not configured)

## Commands
```bash
php artisan test --filter=Notification
php artisan queue:work --once  # Process one queued job
```

