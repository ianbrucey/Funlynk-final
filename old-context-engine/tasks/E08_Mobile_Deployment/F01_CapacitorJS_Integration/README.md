# F01 CapacitorJS Integration

## Feature Overview

This feature adds **CapacitorJS** to FunLynk, enabling deployment as native iOS and Android mobile applications while keeping the existing Laravel + Livewire architecture intact. CapacitorJS wraps the web application in a native WebView container and provides JavaScript bridges to access native device features like geolocation, camera, push notifications, and more.

**Key Benefit**: No rewrite needed. The current server-side rendered Laravel + Livewire app works as-is, with native features added through Capacitor plugins.

## Feature Scope

### In Scope
- **CapacitorJS Installation**: Core Capacitor setup with iOS and Android platforms
- **Geolocation Plugin**: Native GPS for nearby posts/events (replaces browser geolocation)
- **Push Notifications Plugin**: Native push notifications via FCM/APNs
- **Camera Plugin**: Native camera access for post/event images
- **Splash Screen**: Native splash screen with FunLynk branding
- **Status Bar**: Native status bar styling (matches galaxy theme)
- **App Icon**: Generate iOS and Android app icons
- **Development Workflow**: Hot reload, debugging, build process

### Out of Scope
- **Offline Support**: PWA/Service Workers (future enhancement)
- **Background Geolocation**: Continuous location tracking (future enhancement)
- **Calendar Integration**: Add events to device calendar (future enhancement)
- **Share Sheet**: Native sharing (future enhancement)
- **Biometric Auth**: Face ID/Touch ID (future enhancement)

## Tasks Breakdown

### T01: Install CapacitorJS Core
**Estimated Time**: 1-2 hours
**Dependencies**: Node.js, npm
**Commands**:
```bash
# Install Capacitor
npm install @capacitor/core @capacitor/cli

# Initialize Capacitor
npx cap init "FunLynk" "com.funlynk.app" --web-dir=public

# Add iOS and Android platforms
npx cap add ios
npx cap add android
```

**Description**: Install CapacitorJS core and initialize the project. This creates the native iOS and Android project folders with the necessary configuration files. The `--web-dir=public` flag tells Capacitor where Laravel's compiled assets are located.

**Deliverables**:
- `capacitor.config.ts` - Capacitor configuration file
- `ios/` - Native iOS project (Xcode)
- `android/` - Native Android project (Android Studio)
- Updated `package.json` with Capacitor dependencies

---

### T02: Configure Capacitor for Laravel
**Estimated Time**: 1 hour
**Dependencies**: T01
**Files to Edit**:
- `capacitor.config.ts`
- `vite.config.js`

**Description**: Configure Capacitor to work with Laravel's asset structure and development server. Set up the server URL for development (localhost:8000) and production URL (funlynk.com).

**Deliverables**:
- Configured `capacitor.config.ts` with server URLs
- Updated Vite config for mobile builds
- Development and production build scripts

---

### T03: Install Geolocation Plugin
**Estimated Time**: 2-3 hours
**Dependencies**: T02
**Commands**:
```bash
npm install @capacitor/geolocation
npx cap sync
```

**Description**: Install and configure the Geolocation plugin to replace browser-based geolocation with native GPS. Update the location detection logic in the Nearby Feed and For You Feed to use Capacitor's Geolocation API.

**Deliverables**:
- Geolocation plugin installed
- Updated location detection in feeds
- iOS location permissions configured (Info.plist)
- Android location permissions configured (AndroidManifest.xml)

---

### T04: Install Push Notifications Plugin
**Estimated Time**: 3-4 hours
**Dependencies**: T02, Laravel Reverb setup
**Commands**:
```bash
npm install @capacitor/push-notifications
npx cap sync
```

**Description**: Install and configure push notifications for iOS (APNs) and Android (FCM). Integrate with Laravel's notification system to send push notifications for comments, RSVPs, reactions, etc.

**Deliverables**:
- Push Notifications plugin installed
- FCM configuration for Android
- APNs configuration for iOS
- Device token registration endpoint
- Laravel notification channel for push notifications

---

### T05: Install Camera Plugin
**Estimated Time**: 2 hours
**Dependencies**: T02
**Commands**:
```bash
npm install @capacitor/camera
npx cap sync
```

**Description**: Install the Camera plugin to enable native camera access for capturing post and event images. Replace file input with native camera picker.

**Deliverables**:
- Camera plugin installed
- Updated image upload components
- iOS camera permissions configured
- Android camera permissions configured

---

### T06: Configure Splash Screen & App Icon
**Estimated Time**: 2-3 hours
**Dependencies**: T02
**Commands**:
```bash
npm install @capacitor/splash-screen
npm install @capacitor/assets --save-dev
npx cap sync
```

**Description**: Create and configure splash screen and app icons for iOS and Android. Use FunLynk's galaxy theme branding.

**Deliverables**:
- Splash screen images (iOS and Android)
- App icons (all required sizes)
- Splash screen configuration
- Launch screen styling

---

### T07: Testing & Build Process
**Estimated Time**: 2-3 hours
**Dependencies**: T01-T06
**Commands**:
```bash
# Development
php artisan serve
npx cap run ios
npx cap run android

# Production build
npm run build
npx cap copy
npx cap open ios
npx cap open android
```

**Description**: Set up development and production build workflows. Test on iOS simulator, Android emulator, and physical devices.

**Deliverables**:
- Development workflow documentation
- Build scripts for production
- Testing checklist
- Troubleshooting guide

---

**Estimated Total Time**: 13-18 hours

**Implementation Order**: T01 → T02 → T03 → T04 → T05 → T06 → T07

**Testing Priority**: High - Must test on both iOS and Android devices

