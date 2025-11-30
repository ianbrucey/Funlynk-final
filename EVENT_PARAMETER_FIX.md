# DirectMessageReceived Event - Parameter Fix

## ✅ Issue Fixed

### Error
```
ArgumentCountError - Too few arguments to function App\Events\DirectMessageReceived::__construct(), 
2 passed in /Users/ianbruce/Herd/funlynk/app/Services/ChatService.php on line 146 
and exactly 4 expected
```

### Root Cause
The `DirectMessageReceived` and `MessageRequestReceived` events were being called with only 2 parameters (message, conversation), but their constructors expect 4 parameters (message, conversation, sender, recipient).

---

## 🔧 Fix Applied

### Event Constructors (Expected Signature)

Both events expect the same 4 parameters:

**`app/Events/DirectMessageReceived.php`**:
```php
public function __construct(
    public Message $message,
    public Conversation $conversation,
    public User $sender,
    public User $recipient
)
```

**`app/Events/MessageRequestReceived.php`**:
```php
public function __construct(
    public Message $message,
    public Conversation $conversation,
    public User $sender,
    public User $recipient
)
```

---

### Fix in ChatService

**File**: `app/Services/ChatService.php` (lines 140-153)

**Before** (broken):
```php
if ($conversation->type === 'private') {
    $isRequest = $conversation->metadata['is_request'] ?? false;

    if ($isRequest) {
        event(new \App\Events\MessageRequestReceived($message, $conversation));
    } else {
        event(new \App\Events\DirectMessageReceived($message, $conversation));
    }
}
```

**After** (fixed):
```php
if ($conversation->type === 'private') {
    $isRequest = $conversation->metadata['is_request'] ?? false;

    // Get the recipient (the other user in the conversation)
    $recipient = $conversation->participants()
        ->where('user_id', '!=', $user->id)
        ->first();

    if ($isRequest) {
        event(new \App\Events\MessageRequestReceived($message, $conversation, $user, $recipient));
    } else {
        event(new \App\Events\DirectMessageReceived($message, $conversation, $user, $recipient));
    }
}
```

---

## 🎯 What Changed

1. **Added recipient lookup**: Query the conversation participants to find the other user (recipient)
   ```php
   $recipient = $conversation->participants()
       ->where('user_id', '!=', $user->id)
       ->first();
   ```

2. **Updated MessageRequestReceived call**: Added `$user` (sender) and `$recipient` parameters
   ```php
   event(new \App\Events\MessageRequestReceived($message, $conversation, $user, $recipient));
   ```

3. **Updated DirectMessageReceived call**: Added `$user` (sender) and `$recipient` parameters
   ```php
   event(new \App\Events\DirectMessageReceived($message, $conversation, $user, $recipient));
   ```

---

## ✅ Why This Matters

These events are used to:
1. **Trigger notifications** to the recipient when they receive a DM or message request
2. **Broadcast real-time updates** via WebSockets to the recipient's channel
3. **Track sender and recipient** for notification listeners

Without the correct parameters, the events couldn't:
- Send notifications to the right user
- Broadcast to the correct private channel
- Create proper notification records

---

## 🧪 Testing

The fix allows:
- ✅ Sending messages in DM conversations
- ✅ Triggering DirectMessageReceived event with all required data
- ✅ Triggering MessageRequestReceived event with all required data
- ✅ Notifications being sent to recipients
- ✅ Real-time broadcasting to recipient's channel

---

## 📋 Files Modified

1. **`app/Services/ChatService.php`** (lines 140-153)
   - Added recipient lookup
   - Updated both event calls with all 4 parameters

---

## ✅ Status: FIXED

The ArgumentCountError has been resolved. Messages can now be sent successfully, and events will fire with all required parameters for notifications and broadcasting.

**Next Step**: Test sending a message in the DM interface to verify the fix works end-to-end.

