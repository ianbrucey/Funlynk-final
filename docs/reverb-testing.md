# Reverb WebSocket Testing Guide

## Overview

This guide explains how to test your Reverb WebSocket configuration using the `reverb:test-notification` command.

## Prerequisites

1. **Reverb server must be running:**
   ```bash
   php artisan reverb:start
   ```

2. **User must be logged in** and viewing a page in the browser

3. **Environment variables configured:**
   ```env
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=funlynk
   REVERB_APP_KEY=funlynk_reverb_key_2025
   REVERB_APP_SECRET=funlynk_reverb_secret_2025
   REVERB_HOST=178.156.193.56
   REVERB_PORT=8080
   REVERB_SCHEME=http
   
   VITE_REVERB_APP_KEY=funlynk_reverb_key_2025
   VITE_REVERB_HOST=178.156.193.56
   VITE_REVERB_PORT=8080
   VITE_REVERB_SCHEME=ws
   ```

## Usage

### Basic Usage (First User)

```bash
php artisan reverb:test-notification
```

This will send a test notification to the first user in the database.

### Specify User by ID

```bash
php artisan reverb:test-notification 1
```

### Specify User by Email

```bash
php artisan reverb:test-notification user@example.com
```

### Custom Message

```bash
php artisan reverb:test-notification 1 --message="Hello from Reverb! 🎉"
```

### Combined

```bash
php artisan reverb:test-notification user@example.com --message="WebSocket test successful! ✅"
```

## Expected Behavior

When the command runs successfully:

1. ✅ Command outputs: "Test notification broadcasted successfully!"
2. 🚀 A toast notification appears in the **top-right corner** of the browser
3. 📱 Toast shows:
   - **Icon:** 🚀
   - **Title:** "System Test"
   - **Message:** Your custom message or default
4. ⏱️ Toast auto-dismisses after 5 seconds

## Troubleshooting

### No Toast Appears

**Check Browser Console:**
```javascript
// Should see:
[Notifications] Subscribing to channel: user.1
[Notifications] Received: {type: 'test', ...}
```

**Common Issues:**

1. **Reverb not running:**
   ```bash
   php artisan reverb:start
   ```

2. **User not logged in:**
   - Log in to the application in your browser
   - Ensure you're on a page that loads `notifications.js`

3. **WebSocket connection failed:**
   - Check browser console for connection errors
   - Verify `VITE_REVERB_*` variables match `REVERB_*` variables
   - Ensure Reverb port (8080) is accessible

4. **Wrong user ID:**
   - Verify you're logged in as the user you're sending to
   - Check user ID in browser: `<meta name="user-id" content="1">`

5. **Assets not compiled:**
   ```bash
   npm run dev
   # or
   npm run build
   ```

### WebSocket Connection Errors

**Error: "WebSocket connection failed"**

Check:
- Reverb server is running: `php artisan reverb:start`
- Port 8080 is open and accessible
- `VITE_REVERB_HOST` matches your server IP/domain

**Error: "401 Unauthorized"**

Check:
- `VITE_REVERB_APP_KEY` matches `REVERB_APP_KEY`
- Keys are correctly set in `.env`

### Command Errors

**Error: "User not found"**

```bash
# List users
php artisan tinker
>>> User::all(['id', 'email']);
```

**Error: "No users found in database"**

Create a user first:
```bash
php artisan tinker
>>> User::factory()->create(['email' => 'test@example.com']);
```

## Testing Workflow

1. **Start Reverb:**
   ```bash
   php artisan reverb:start
   ```

2. **Start Vite (if developing):**
   ```bash
   npm run dev
   ```

3. **Log in to the app** in your browser

4. **Run test command:**
   ```bash
   php artisan reverb:test-notification
   ```

5. **Verify toast appears** in browser

## Production Testing

For production (Laravel Cloud):

```bash
# SSH into server or use Laravel Cloud CLI
php artisan reverb:test-notification user@example.com --message="Production test"
```

Ensure:
- Reverb is running as a service
- Production `.env` has correct `VITE_REVERB_*` variables
- Assets are built: `npm run build`

## Integration with Existing Notifications

This test uses the same infrastructure as real notifications:

- **Channel:** `user.{userId}`
- **Event:** `.notification`
- **Handler:** `resources/js/notifications.js`

All real notifications (post reactions, invitations, etc.) will use the same toast system.

## Next Steps

Once Reverb is working:

1. Test real notifications (post reactions, invitations)
2. Monitor Reverb logs for errors
3. Set up Reverb as a systemd service for production
4. Configure SSL/TLS for secure WebSocket connections (wss://)

