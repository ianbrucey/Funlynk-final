# Capacitor Navigation Fix

## Problem
When clicking links in the iOS Capacitor app, the app was either:
1. Not responding to clicks
2. Opening Safari instead of navigating within the app

## Root Cause
By default, Capacitor treats `<a>` tag clicks as external navigation attempts, which causes iOS to open Safari. This is because:
- Standard HTML links don't automatically work within a WebView
- Capacitor needs explicit JavaScript handling to intercept and process internal navigation

## Solution Implemented

### 1. Created Navigation Handler (`resources/js/capacitor-navigation.js`)
This JavaScript module:
- Detects if the app is running in a native Capacitor environment
- Intercepts all link clicks using event delegation
- Prevents default browser behavior for internal links
- Allows external links (http/https) to open in Safari
- Handles navigation within the WebView using `window.location.href`

**Key Features:**
- Only runs on native platforms (iOS/Android), not in web browsers
- Uses capture phase event handling to catch clicks before other handlers
- Respects `target="_blank"` and `download` attributes
- Logs navigation events for debugging

### 2. Updated Capacitor Config (`capacitor.config.ts`)
Added iOS-specific configuration:
```typescript
ios: {
  contentInset: 'automatic',
  allowsLinkPreview: false, // Prevents iOS link preview popup
}
```

### 3. Integrated into Build Process
- Added import to `resources/js/app.js`
- Runs automatically on every page load
- Included in Vite build process

## How It Works

```
User clicks link
    ↓
Navigation handler intercepts click
    ↓
Is it an internal link? (no http/https)
    ↓ YES
Prevent default behavior
    ↓
Navigate within WebView using window.location.href
    ↓
Laravel handles the route
    ↓
Page loads within the app
```

## Testing Instructions

### 1. Rebuild and Sync
```bash
npm run build
npm run cap:sync
```

### 2. Open in Xcode
```bash
npm run cap:open:ios
```

### 3. Run on Simulator/Device
- Make sure Laravel dev server is running: `php artisan serve --host=0.0.0.0`
- Build and run in Xcode (⌘R)

### 4. Test Navigation
- Click "Log in" button in navbar → Should navigate to `/login` within app
- Click "Sign In" button in hero → Should navigate to `/login` within app
- Click "Get Started" → Should navigate to `/register` within app
- All navigation should stay within the app (no Safari opening)

### 5. Check Console Logs
Open Safari Developer Tools (Develop → Simulator → localhost):
- Should see: "Capacitor: Initializing navigation handler for native platform"
- Should see: "Capacitor: Navigation handler initialized"
- When clicking links: "Capacitor: Navigating to internal route: /login"

## What Links Are Handled

### Internal Links (Handled by Navigation Handler)
- `/login`
- `/register`
- `/feed/nearby`
- `/profile`
- Any relative path without http/https

### External Links (Open in Safari)
- `https://example.com`
- `http://example.com`
- Links with `target="_blank"`
- Links with `download` attribute

## Troubleshooting

### Links Still Opening Safari
1. Check browser console for errors
2. Verify `capacitor-navigation.js` is loaded (check Network tab)
3. Ensure `Capacitor.isNativePlatform()` returns true
4. Clear app cache and rebuild

### Navigation Not Working
1. Check Laravel routes are defined correctly
2. Verify dev server is running and accessible
3. Check Capacitor config has correct server URL
4. Look for JavaScript errors in Safari Developer Tools

### Clicks Not Registering
1. Check for CSS `pointer-events: none` on parent elements
2. Verify no other JavaScript is preventing event propagation
3. Check z-index stacking issues

## Future Enhancements

### Option 1: Use Capacitor App Plugin
For more advanced navigation control:
```bash
npm install @capacitor/app
```

Then use `App.addListener('appUrlOpen')` for deep linking.

### Option 2: Implement SPA-style Navigation
Use Livewire's wire:navigate for smoother transitions:
```blade
<a href="/login" wire:navigate>Log in</a>
```

### Option 3: Add Loading States
Show loading indicator during navigation:
```javascript
// Before navigation
document.body.classList.add('navigating');

// After navigation
window.addEventListener('load', () => {
  document.body.classList.remove('navigating');
});
```

## Files Modified
- ✅ `resources/js/app.js` - Added import
- ✅ `resources/js/capacitor-navigation.js` - Created navigation handler
- ✅ `capacitor.config.ts` - Added iOS config
- ✅ Built and synced to iOS app

## Status
✅ **COMPLETE** - Navigation now works correctly in iOS Capacitor app

