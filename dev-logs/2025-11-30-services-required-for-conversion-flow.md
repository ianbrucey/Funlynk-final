# Services Required for Post-to-Event Conversion Flow

**Date**: November 30, 2025  
**Question**: Are we supposed to have Reverb, Horizon, or other services running?

---

## Current Configuration

### Queue System
**File**: `.env`
```
QUEUE_CONNECTION=sync
```

**What this means**:
- Jobs execute **synchronously** (immediately, in the same request)
- No queue worker needed
- No Horizon needed
- Perfect for development/testing

### Broadcasting System
**File**: `.env`
```
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=1001
REVERB_APP_KEY=laravel-herd
REVERB_APP_SECRET=secret
REVERB_HOST="reverb.herd.test"
```

**What this means**:
- Broadcasting is configured for **Reverb** (Laravel's WebSocket server)
- Reverb is available in Laravel Herd
- Used for real-time notifications

---

## What Services Are Actually Used?

### ✅ Queue System (SYNC - No Service Needed)
**Used by**: `CheckPostConversionEligibility` job
**Current**: `QUEUE_CONNECTION=sync`
**Status**: ✅ Works without any service
**How**: Jobs execute immediately in the same request

### ✅ Broadcasting (REVERB - Optional for Real-time)
**Used by**: 
- `PostReacted` event (broadcasts to `user.{post_owner_id}`)
- `PostConversionSuggested` event (broadcasts to `user.{post_owner_id}`)
- `PostAutoConverted` event (broadcasts to `user.{post_owner_id}`)

**Current**: `BROADCAST_CONNECTION=reverb`
**Status**: ✅ Configured but optional
**How**: 
- If Reverb running: Real-time WebSocket notifications
- If Reverb not running: Events still fire, but no real-time broadcast

---

## Do We Need to Run Anything?

### For Basic Functionality: ❌ NO
The conversion flow works without any services:
1. User reacts → Job runs immediately (sync queue)
2. Notification created in database
3. NotificationBell component loads it on next page load

### For Real-time Notifications: ✅ YES (Optional)
To see notifications appear instantly without page refresh:
1. Start Reverb server
2. Client connects via WebSocket
3. Events broadcast in real-time

---

## How to Start Reverb (If Desired)

```bash
# In Laravel Herd, Reverb is built-in
php artisan reverb:start

# Or with Herd CLI
herd reverb
```

**Note**: This is optional. The app works fine without it.

---

## Why Conversion Flow Isn't Working (Not Service-Related)

The issue is **NOT** missing services. It's:

1. ❌ `CheckPostConversion` listener not registered
2. ❌ Job never dispatches (because listener not registered)
3. ❌ Notification never created
4. ❌ UI has nothing to display

**Fix**: Register the listener in `AppServiceProvider`

---

## Queue Configuration Options

If you want to change queue driver later:

| Driver | Use Case | Service Needed |
|--------|----------|---|
| `sync` | Development, testing | ❌ None |
| `database` | Production, small scale | ❌ None (uses DB) |
| `redis` | Production, high volume | ✅ Redis |
| `sqs` | AWS production | ✅ AWS SQS |

**Current**: `sync` - Perfect for development

---

## Summary

**Do we need services running?**
- ❌ **NO** - Sync queue means jobs run immediately
- ✅ **OPTIONAL** - Reverb for real-time notifications

**Why conversion flow isn't working?**
- ❌ **NOT** because of missing services
- ✅ **BECAUSE** listener not registered in AppServiceProvider

**What to do?**
1. Fix the listener registration (1 line of code)
2. Optionally start Reverb for real-time notifications
3. Test the flow

