# Funlynk EAS Setup Guide

## Overview
This guide will help you complete the EAS (Expo Application Services) setup for the Funlynk mobile application.

## Prerequisites Completed ✅
- [x] EAS CLI installed globally
- [x] Expo project created with TypeScript template
- [x] EAS project ID configured: `92039283-d0ff-42b7-8985-40f508fa4d72`
- [x] `eas.json` configuration file created
- [x] `app.json` updated with Funlynk branding and permissions
- [x] Required Expo plugins installed (expo-router, expo-location, expo-image-picker)

## Next Steps (Manual Authentication Required)

### 1. Authenticate with EAS
Since EAS requires interactive authentication, you'll need to run this command manually:

```bash
cd funlynk-app
eas login
```

Enter your Expo account credentials when prompted.

### 2. Verify EAS Configuration
After authentication, verify the setup:

```bash
eas project:info
```

This should show:
- Project ID: `92039283-d0ff-42b7-8985-40f508fa4d72`
- Owner: `mrbruce24`
- Project Name: `Funlynk`

### 3. Configure Build Profiles
The `eas.json` file has been pre-configured with three build profiles:

- **development**: For development builds with Expo Dev Client
- **preview**: For internal testing and distribution
- **production**: For app store releases

### 4. Test Your First Build
Try creating a development build:

```bash
# For iOS (requires Apple Developer account)
eas build --platform ios --profile development

# For Android
eas build --platform android --profile development
```

### 5. Set Up Credentials
For iOS builds, you'll need to configure signing credentials:

```bash
eas credentials
```

Follow the prompts to:
- Generate or upload certificates
- Configure provisioning profiles
- Set up push notification keys

## Project Configuration Details

### App Configuration (`app.json`)
- **Name**: Funlynk
- **Bundle ID**: `com.funlynk.app` (iOS) / `com.funlynk.app` (Android)
- **Description**: Social activity discovery and connection platform
- **Primary Color**: `#6366f1` (Indigo)
- **Permissions**: Location, Camera, Photo Library, Contacts

### Build Configuration (`eas.json`)
- **CLI Version**: >= 13.2.0
- **Resource Classes**: Medium for all builds
- **Development**: Includes development client
- **Preview**: Internal distribution
- **Production**: App store ready

### Installed Plugins
- **expo-router**: File-based routing system
- **expo-location**: Location services for activity discovery
- **expo-image-picker**: Photo selection for profiles and activities

## Available Commands

### Development
```bash
# Start development server
npm start

# Run on iOS simulator
npm run ios

# Run on Android emulator
npm run android

# Run on web
npm run web
```

### Building
```bash
# Development builds
eas build --platform ios --profile development
eas build --platform android --profile development

# Preview builds
eas build --platform ios --profile preview
eas build --platform android --profile preview

# Production builds
eas build --platform ios --profile production
eas build --platform android --profile production
```

### Submission
```bash
# Submit to App Store
eas submit --platform ios

# Submit to Google Play
eas submit --platform android
```

## Troubleshooting

### Authentication Issues
If you encounter authentication issues:
```bash
eas logout
eas login
```

### Build Failures
Check build logs:
```bash
eas build:list
eas build:view [BUILD_ID]
```

### Credential Issues
Reset credentials:
```bash
eas credentials --clear-cache
eas credentials
```

## Next Development Steps

1. **Set up the project structure** following the comprehensive task breakdown
2. **Implement core features** starting with E01 Core Infrastructure
3. **Add navigation** using expo-router
4. **Integrate APIs** for backend services
5. **Add authentication** and user management
6. **Implement activity management** features
7. **Add social features** and real-time functionality

## Resources

- [EAS Documentation](https://docs.expo.dev/eas/)
- [Expo Router Documentation](https://docs.expo.dev/router/introduction/)
- [React Native Documentation](https://reactnative.dev/docs/getting-started)
- [Funlynk Architecture Documentation](../context-engine/)

## Support

For EAS-specific issues:
- [EAS Support](https://expo.dev/support)
- [Expo Discord](https://discord.gg/expo)
- [Expo Forums](https://forums.expo.dev/)

---

**Status**: Ready for manual authentication and first build
**Next Action**: Run `eas login` to authenticate and begin building
