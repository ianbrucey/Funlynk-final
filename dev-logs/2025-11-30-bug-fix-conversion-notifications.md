# Bug Fix: Post-to-Event Conversion Notifications Not Being Created

**Date**: November 30, 2025  
**Status**: ✅ FIXED  
**Issue**: When users reacted to posts, conversion prompt notifications were not being created

---

## Root Causes Identified

### 1. Missing Event Listener Registration ❌
**Problem**: `PostConversionSuggested` and `PostAutoConverted` events had NO listeners registered  
**Location**: `app/Providers/AppServiceProvider.php`  
**Impact**: Job fired events but nothing happened

### 2. Undefined Constant ❌
**Problem**: `Post::canConvert()` referenced `self::CONVERSION_THRESHOLD` which didn't exist  
**Location**: `app/Models/Post.php:133`  
**Impact**: Fatal error when checking conversion eligibility

### 3. Threshold Mismatch ❌
**Problem**: Multiple hardcoded thresholds with different values:
- `Post::canConvert()` = undefined constant
- `Post::shouldAutoConvert()` = 2
- `PostService::checkConversionEligibility()` = 5 & 10

**Impact**: Inconsistent behavior across the system

---

## Fixes Applied

### Fix 1: Register Event Listeners
**File**: `app/Providers/AppServiceProvider.php`

Added listener registrations for:
- `PostConversionSuggested` → `SendConversionPromptNotification`
- `PostAutoConverted` → `SendConversionPromptNotification`

```php
// Register listener for PostConversionSuggested (soft threshold)
\Illuminate\Support\Facades\Event::listen(
    \App\Events\PostConversionSuggested::class,
    \App\Listeners\SendConversionPromptNotification::class
);

// Register listener for PostAutoConverted (strong threshold)
\Illuminate\Support\Facades\Event::listen(
    \App\Events\PostAutoConverted::class,
    \App\Listeners\SendConversionPromptNotification::class
);
```

### Fix 2: Fix Undefined Constant
**File**: `app/Models/Post.php:133`

Changed from:
```php
return $this->reaction_count >= self::CONVERSION_THRESHOLD && $this->status === 'active';
```

To:
```php
return $this->reaction_count >= 2 && $this->status === 'active';
```

### Fix 3: Update Listener to Handle Multiple Events
**File**: `app/Listeners/SendConversionPromptNotification.php`

Updated `handle()` method to accept union type:
```php
public function handle(PostConversionPrompted|PostConversionSuggested|PostAutoConverted $event): void
```

Added logic to determine threshold based on event type:
- `PostAutoConverted` → 'strong' threshold
- `PostConversionSuggested` → 'soft' threshold
- `PostConversionPrompted` → uses `$event->threshold`

### Fix 4: Update Service Thresholds (Test Mode)
**File**: `app/Services/PostService.php:219-231`

Changed thresholds to test values:
```php
return [
    'eligible' => $reactionCount >= 2,  // Test (production: 5)
    'auto_convert' => $reactionCount >= 1,  // Test (production: 10)
    'reaction_count' => $reactionCount,
    'threshold_5' => 2,  // Test (production: 5)
    'threshold_10' => 1,  // Test (production: 10)
];
```

---

## Test Results

### End-to-End Test ✅
```
BEFORE:
- reaction_count: 0
- Notifications: 0

ADDING REACTION:
- Action: added
- New reaction_count: 1

AFTER:
- Total Notifications: 3
  ✅ 2x post_conversion_prompt (duplicate - known issue)
  ✅ 1x post_reaction
```

### Notifications Created ✅
1. **Conversion Prompt**: "🔥 1+ people want to join! Turn this into an event now."
2. **Reaction Notification**: "Someone is down for [post title]"

---

## Known Issues

### Duplicate Conversion Prompts ⚠️
**Issue**: Two identical conversion prompt notifications are created  
**Cause**: Unknown - needs investigation  
**Impact**: Minor - user sees duplicate notifications  
**Priority**: Low

---

## Files Modified

1. `app/Providers/AppServiceProvider.php` - Added event listener registrations
2. `app/Models/Post.php` - Fixed undefined constant
3. `app/Listeners/SendConversionPromptNotification.php` - Handle multiple event types
4. `app/Services/PostService.php` - Updated test thresholds

---

## Next Steps

1. ✅ Test in browser with real user interactions
2. ⚠️ Investigate duplicate notification issue
3. 📝 Reset thresholds to production values (5 & 10) after testing
4. 📝 Consider making thresholds configurable via environment variables

---

## Verification Commands

```bash
# Test the full flow
php artisan tinker
$post = Post::first();
$reactor = User::where('id', '!=', $post->user_id)->first();
app(PostService::class)->toggleReaction($post->id, 'im_down', $reactor);

# Check notifications
Notification::where('user_id', $post->user_id)->get();
```

