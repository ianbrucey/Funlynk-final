# Refactor: Conversion Thresholds to Constants

**Date**: November 30, 2025  
**Status**: ✅ COMPLETE  
**Purpose**: Centralize conversion threshold values to avoid hardcoding the same numbers in multiple places

---

## Problem

Multiple files had hardcoded threshold values (2, 5, 10, 1) scattered throughout:
- `Post::canConvert()` - hardcoded 2
- `Post::shouldAutoConvert()` - hardcoded 2
- `Post::isEligibleForConversion()` - hardcoded 5
- `Post::scopeEligibleForConversion()` - hardcoded 1
- `PostService::checkConversionEligibility()` - hardcoded 2, 1
- `ConversionEligibilityService::getThresholdLevel()` - hardcoded 10, 5
- `ConversionEligibilityService::getNoPromptReason()` - hardcoded 5
- `PostAutoConverted::broadcastWith()` - referenced `threshold_10`

**Impact**: Changing thresholds required updates in 8+ places, increasing risk of inconsistency

---

## Solution

Created two constants in `Post` model:

```php
public const CONVERSION_SOFT_THRESHOLD = 2;    // Test: 2 (Production: 5)
public const CONVERSION_STRONG_THRESHOLD = 1;  // Test: 1 (Production: 10)
```

---

## Files Modified

### 1. `app/Models/Post.php`
- Added constants at class level
- Updated `canConvert()` to use `CONVERSION_SOFT_THRESHOLD`
- Updated `shouldAutoConvert()` to use `CONVERSION_STRONG_THRESHOLD`
- Updated `isEligibleForConversion()` to use `CONVERSION_SOFT_THRESHOLD`
- Updated `scopeEligibleForConversion()` to use `CONVERSION_SOFT_THRESHOLD`

### 2. `app/Services/PostService.php`
- Updated `checkConversionEligibility()` to use constants
- Changed return keys from `threshold_5`/`threshold_10` to `threshold_soft`/`threshold_strong`

### 3. `app/Services/ConversionEligibilityService.php`
- Updated `getThresholdLevel()` to use `CONVERSION_STRONG_THRESHOLD`
- Updated `getNoPromptReason()` to use `CONVERSION_SOFT_THRESHOLD`

### 4. `app/Jobs/CheckPostConversionEligibility.php`
- Updated comments to reference thresholds by name instead of hardcoded numbers

### 5. `app/Events/PostAutoConverted.php`
- Fixed `broadcastWith()` to use `threshold_strong` instead of `threshold_10`

---

## Benefits

✅ **Single Source of Truth**: Change thresholds in one place  
✅ **Type Safety**: Constants are checked at compile time  
✅ **Readability**: Code is self-documenting  
✅ **Maintainability**: Easier to switch between test/production values  
✅ **Consistency**: All parts of system use same thresholds

---

## Test Results

```
CONSTANTS:
- CONVERSION_SOFT_THRESHOLD: 2
- CONVERSION_STRONG_THRESHOLD: 1

ADDING REACTION:
- Action: added
- reaction_count: 1
- canConvert: NO (requires 2)

NOTIFICATIONS:
✅ 2x post_conversion_prompt (strong threshold)
✅ 1x post_reaction
```

---

## Production Migration

To switch to production thresholds, change one line in `app/Models/Post.php`:

```php
// Change from:
public const CONVERSION_SOFT_THRESHOLD = 2;
public const CONVERSION_STRONG_THRESHOLD = 1;

// To:
public const CONVERSION_SOFT_THRESHOLD = 5;
public const CONVERSION_STRONG_THRESHOLD = 10;
```

All 8+ files will automatically use the new values! 🎉

