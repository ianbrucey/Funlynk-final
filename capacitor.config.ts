import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.funlynk.app',
  appName: 'FunLynk',
  webDir: 'public',
  server: {
    // For development with Laravel Artisan Serve:
    // Run: php artisan serve --host=0.0.0.0 --port=8000
    // Android emulator uses 10.0.2.2 to reach host machine
    // iOS simulator can use localhost
    // Comment out for production builds
    url: 'http://localhost:8000',
    cleartext: true,
  },
  ios: {
    // Exclude Laravel's storage symlink (iOS doesn't allow symlinks in app bundle)
    contentInset: 'automatic',
    // Allow navigation within the app
    allowsLinkPreview: false,
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      backgroundColor: '#0f172a', // Galaxy theme dark background
      showSpinner: false,
    },
  },
};

export default config;
