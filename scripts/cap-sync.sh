#!/bin/bash

# Sync Capacitor and remove Laravel storage symlinks
# iOS doesn't allow symlinks in app bundles

echo "🔄 Syncing Capacitor..."
npx cap sync

echo "🗑️  Removing storage symlinks..."
# Remove symlinks (use -rf to handle both files and symlinks)
rm -rf ios/App/App/public/storage
rm -rf android/app/src/main/assets/public/storage

# Verify removal
if [ -L "ios/App/App/public/storage" ]; then
    echo "⚠️  Warning: iOS storage symlink still exists!"
    exit 1
fi

if [ -L "android/app/src/main/assets/public/storage" ]; then
    echo "⚠️  Warning: Android storage symlink still exists!"
    exit 1
fi

echo "✅ Capacitor sync complete!"

