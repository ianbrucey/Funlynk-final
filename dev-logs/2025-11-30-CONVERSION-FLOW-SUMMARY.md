# Post-to-Event Conversion Flow - Executive Summary

**Date**: November 30, 2025  
**Issue**: Post owner doesn't see conversion prompt when post reaches threshold  
**Root Cause**: Missing event listener registration (1 line of code)

---

## What Should Happen

1. User B clicks "I'm Down" on User A's post
2. Post reaction count increases
3. When threshold reached (2 in test, 5 in production):
   - **Notification bell shows badge** with count
   - **Notification dropdown displays** conversion prompt
   - **"Convert to Event" button** appears
4. User A clicks button → conversion modal opens

---

## What Actually Happens

1. ✅ User B clicks "I'm Down"
2. ✅ Post reaction count increases
3. ✅ `PostReacted` event fires
4. ❌ **STOPS HERE** - Event listener not registered
5. ❌ Conversion check never runs
6. ❌ Notification never created
7. ❌ User A sees nothing

---

## The Problem (1 Line Missing)

**File**: `app/Providers/AppServiceProvider.php`

**Current** (lines 22-35):
```php
public function boot(): void
{
    // Only registers 2 listeners
    \Illuminate\Support\Facades\Event::listen(
        \App\Events\PostConversionPrompted::class,
        \App\Listeners\SendConversionPromptNotification::class
    );
    
    \Illuminate\Support\Facades\Event::listen(
        \App\Events\PostConvertedToEvent::class,
        [
            \App\Listeners\NotifyInterestedUsers::class,
            \App\Listeners\MigratePostInvitations::class,
        ]
    );
}
```

**Missing**: Listener for `PostReacted` event that triggers the job

---

## The Fix

Add this to `AppServiceProvider::boot()`:

```php
\Illuminate\Support\Facades\Event::listen(
    \App\Events\PostReacted::class,
    \App\Listeners\CheckPostConversion::class
);
```

This enables the chain:
- `PostReacted` → `CheckPostConversion` → `CheckPostConversionEligibility` job → 
- `ConversionEligibilityService` → `PostConversionPrompted` → 
- `SendConversionPromptNotification` → Notification created → 
- `NotificationBell` displays it

---

## Additional Issues

### Issue 2: Test Thresholds Not Reset
**File**: `app/Models/Post.php`
- Line 133: `canConvert()` uses `>= 1` (should be `>= 5`)
- Line 138: `shouldAutoConvert()` uses `>= 2` (should be `>= 10`)

### Issue 3: No Real-time Notification Refresh
**File**: `app/Listeners/SendConversionPromptNotification.php`
- Creates notification but doesn't trigger refresh
- Notification only appears after page reload
- **Fix**: Dispatch `notificationReceived` event after creating notification

---

## UI Components (All Working ✅)

- `app/Livewire/Notifications/NotificationBell.php` - Loads notifications
- `resources/views/livewire/notifications/notification-bell.blade.php` - Displays dropdown
- `resources/views/components/notifications/conversion-prompt-card.blade.php` - Shows prompt

These are ready to display notifications, they just never receive any!

---

## Files to Review

**Complete Analysis**: `dev-logs/2025-11-30-post-conversion-flow-analysis.md`  
**Detailed Flow**: `dev-logs/2025-11-30-conversion-flow-complete-analysis.md`

---

## Next Steps

1. Add listener registration to `AppServiceProvider`
2. Reset test thresholds to production values
3. Add real-time notification dispatch (optional but recommended)
4. Test with User A as post owner, User B reacting

