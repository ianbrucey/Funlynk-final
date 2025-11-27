# Onboarding Location Input Fix

**Date**: 2025-11-23 21:30  
**Issue**: Unable to enter location in onboarding wizard, JavaScript console errors about "filamentSchemaComponent"  
**Status**: ✅ FIXED

---

## Problem Analysis

### Issue 1: Livewire Scripts Not Loading
**File**: `resources/views/layouts/auth.blade.php`  
**Problem**: Lines 256-257 had `@livewireScripts` and `@filamentScripts` commented out

```blade
<!-- @livewireScripts -->
<!-- @filamentScripts -->
```

This caused:
- Livewire JavaScript not available
- `Livewire.find()` method undefined
- Google Places autocomplete couldn't communicate with Livewire component

### Issue 2: Incorrect Livewire Component Access
**File**: `resources/views/livewire/onboarding/onboarding-wizard.blade.php`  
**Problem**: Using `@this.call()` instead of proper Livewire component instance

**Old Code** (line 199):
```javascript
@this.call('setLocationData', name, lat, lng);
```

**Issue**: `@this` is a Blade directive that only works in certain contexts. When Google Places API tries to call this from its event listener, the context is lost.

---

## Solution Implemented

### Fix 1: Enable Livewire Scripts
**File**: `resources/views/layouts/auth.blade.php` (lines 256-257)

**Changed**:
```blade
@livewireScripts
@filamentScripts
```

**Result**: Livewire JavaScript now loads properly on auth pages (login, register, onboarding)

---

### Fix 2: Use Proper Livewire Component Access
**File**: `resources/views/livewire/onboarding/onboarding-wizard.blade.php` (lines 164-254)

**Pattern Used** (from `edit-profile.blade.php`):
```javascript
const input = document.getElementById('onboarding-location-input');
const component = Livewire.find(input.closest('[wire\\:id]').getAttribute('wire:id'));
component.call('setLocationData', name, lat, lng);
```

**How It Works**:
1. Get the input element
2. Find the closest parent with `wire:id` attribute (the Livewire component wrapper)
3. Use `Livewire.find()` to get the component instance
4. Call the method on the component instance

**Applied To**:
- Google Places autocomplete listener (line 195-204)
- Current location button handler (line 207-245)

---

## Changes Made

### 1. `resources/views/layouts/auth.blade.php`
```diff
- <!-- @livewireScripts -->
- <!-- @filamentScripts -->
+ @livewireScripts
+ @filamentScripts
```

### 2. `resources/views/livewire/onboarding/onboarding-wizard.blade.php`

**Autocomplete Listener** (lines 190-204):
```javascript
autocomplete.addListener('place_changed', () => {
    const place = autocomplete.getPlace();
    if (!place.geometry) return;

    const lat = place.geometry.location.lat();
    const lng = place.geometry.location.lng();
    const name = place.formatted_address || place.name;

    // Get Livewire component instance
    const component = Livewire.find(input.closest('[wire\\:id]').getAttribute('wire:id'));
    if (component) {
        input.value = name;
        component.call('setLocationData', name, lat, lng);
    }
});
```

**Current Location Handler** (lines 207-245):
```javascript
window.getCurrentLocationOnboarding = function() {
    // ... geolocation code ...
    
    const input = document.getElementById('onboarding-location-input');
    const component = Livewire.find(input.closest('[wire\\:id]').getAttribute('wire:id'));

    if (!component) {
        alert('Unable to connect to the form. Please refresh the page.');
        return;
    }

    // ... geocoding code ...
    component.call('setLocationData', results[0].formatted_address, lat, lng);
};
```

---

## Testing Checklist

- [ ] Visit `/onboarding` page
- [ ] Check browser console for errors (should be none)
- [ ] Type in location input → Google Places suggestions appear
- [ ] Select a location → location name, lat, lng set in Livewire
- [ ] Click "Use current location" button → browser asks for permission
- [ ] Grant permission → location detected and set
- [ ] Click "Continue" → proceeds to step 2 (interests)
- [ ] Complete onboarding → redirects to dashboard

---

## Why This Fix Works

### Before:
1. `@this` was undefined in Google Places callback context
2. Livewire scripts weren't loaded, so `Livewire.find()` didn't exist
3. Location data couldn't be sent to Livewire component

### After:
1. Livewire scripts load properly
2. `Livewire.find()` method available
3. Component instance retrieved correctly
4. Method calls work as expected
5. Location data flows from JavaScript → Livewire → PHP

---

## Related Files

**Working Reference**: `resources/views/livewire/profile/edit-profile.blade.php`  
- Uses same pattern for location autocomplete
- Proven to work in production

**Google Places API Key**: Configured in `.env` and `config/services.php`

---

## Notes

- The `wire:ignore` directive on the input wrapper (line 42) is still necessary to prevent Livewire from interfering with Google Places autocomplete
- The `@filamentStyles` in the layout header is fine - it only loads CSS, not JavaScript
- The "filamentSchemaComponent" error was caused by Filament JavaScript trying to initialize without Livewire being available

---

**Status**: ✅ Fixed and ready for testing

