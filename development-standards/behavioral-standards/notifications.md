# Notification System Standards

**Standard:** All user feedback follows these exact patterns.

## When to Show Notifications

### ✅ Always Show Notifications For:

1. **Successful actions** - Create, update, delete operations
2. **Failed actions** - Validation errors, server errors, permission errors
3. **Background job completion** - When async operations finish
4. **Important system events** - Account changes, payment confirmations

### ❌ Never Show Notifications For:

1. **Navigation** - Moving between pages
2. **Read operations** - Viewing content, loading data
3. **Real-time updates** - New messages, reactions (use inline updates)
4. **Automatic actions** - Auto-save, background sync

## Notification Types

### Success Notification

**Use for:** Successful create/update/delete operations

```php
session()->flash('success', 'Post created successfully!');
return redirect()->route('feed.nearby');
```

**Display:**
```blade
@if(session('success'))
    <div class="fixed top-4 right-4 z-50 animate-slide-in">
        <div class="p-4 bg-green-500/20 border border-green-500/50 rounded-xl 
                    backdrop-blur-xl shadow-lg max-w-md">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="text-white font-medium">{{ session('success') }}</p>
            </div>
        </div>
    </div>
@endif
```

### Error Notification

**Use for:** Failed operations, validation errors, permission errors

```php
session()->flash('error', 'Unable to create post. Please try again.');
return redirect()->back();
```

**Display:**
```blade
@if(session('error'))
    <div class="fixed top-4 right-4 z-50 animate-slide-in">
        <div class="p-4 bg-red-500/20 border border-red-500/50 rounded-xl 
                    backdrop-blur-xl shadow-lg max-w-md">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <p class="text-white font-medium">{{ session('error') }}</p>
            </div>
        </div>
    </div>
@endif
```

### Info Notification

**Use for:** Informational messages, tips, non-critical updates

```php
session()->flash('info', 'Your post will expire in 24 hours.');
```

**Display:**
```blade
@if(session('info'))
    <div class="fixed top-4 right-4 z-50 animate-slide-in">
        <div class="p-4 bg-cyan-500/20 border border-cyan-500/50 rounded-xl 
                    backdrop-blur-xl shadow-lg max-w-md">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-cyan-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <p class="text-white font-medium">{{ session('info') }}</p>
            </div>
        </div>
    </div>
@endif
```

### Warning Notification

**Use for:** Warnings, cautions, actions that need attention

```php
session()->flash('warning', 'This post has low engagement and may not convert to an event.');
```

**Display:**
```blade
@if(session('warning'))
    <div class="fixed top-4 right-4 z-50 animate-slide-in">
        <div class="p-4 bg-yellow-500/20 border border-yellow-500/50 rounded-xl 
                    backdrop-blur-xl shadow-lg max-w-md">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <p class="text-white font-medium">{{ session('warning') }}</p>
            </div>
        </div>
    </div>
@endif
```

## Notification Placement

**Standard:** Top-right corner, fixed position

```blade
<div class="fixed top-4 right-4 z-50 animate-slide-in">
    <!-- Notification content -->
</div>
```

**Mobile:** Same position, but with responsive width:

```blade
<div class="fixed top-4 right-4 left-4 sm:left-auto sm:max-w-md z-50 animate-slide-in">
    <!-- Notification content -->
</div>
```

## Auto-Dismiss Behavior

**Standard:** Notifications auto-dismiss after 5 seconds

Add to `app.css`:
```css
@keyframes slide-in {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slide-out {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

.animate-slide-in {
    animation: slide-in 0.3s ease-out;
}

.animate-slide-out {
    animation: slide-out 0.3s ease-in;
}
```

Add to layout:
```blade
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const notifications = document.querySelectorAll('.fixed.top-4.right-4');
        notifications.forEach(notification => {
            setTimeout(() => {
                notification.classList.add('animate-slide-out');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        });
    });
</script>
```

## Message Guidelines

### ✅ Good Messages

- **Specific:** "Post created successfully!" (not "Success!")
- **Action-oriented:** "Profile updated" (not "Changes saved")
- **User-focused:** "Your post is now live" (not "Post published")
- **Concise:** One sentence maximum

### ❌ Bad Messages

- **Generic:** "Success!" or "Error occurred"
- **Technical:** "Database insert failed" or "500 error"
- **Verbose:** "Your post has been successfully created and is now visible to all users in your area"
- **Passive:** "Changes were saved" (use "Profile updated")

## Implementation Pattern

### In Livewire Components

```php
public function save()
{
    try {
        $validated = $this->validate();
        
        app(PostService::class)->create($validated);
        
        session()->flash('success', 'Post created successfully!');
        return redirect()->route('feed.nearby');
        
    } catch (\Exception $e) {
        session()->flash('error', 'Unable to create post. Please try again.');
        Log::error('Post creation failed', ['error' => $e->getMessage()]);
    }
}
```

### In Controllers

```php
public function store(Request $request)
{
    $validated = $request->validate([...]);
    
    try {
        $post = app(PostService::class)->create($validated);
        
        return redirect()
            ->route('posts.show', $post)
            ->with('success', 'Post created successfully!');
            
    } catch (\Exception $e) {
        return redirect()
            ->back()
            ->with('error', 'Unable to create post. Please try again.')
            ->withInput();
    }
}
```

## Standard Notification Component

**Location:** `resources/views/components/notification.blade.php`

```blade
@if(session('success') || session('error') || session('info') || session('warning'))
    <div class="fixed top-4 right-4 left-4 sm:left-auto sm:max-w-md z-50 animate-slide-in" 
         x-data="{ show: true }" 
         x-show="show"
         x-init="setTimeout(() => { show = false; setTimeout(() => $el.remove(), 300) }, 5000)">
        
        @if(session('success'))
            <div class="p-4 bg-green-500/20 border border-green-500/50 rounded-xl backdrop-blur-xl shadow-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-white font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif
        
        <!-- Repeat for error, info, warning -->
        
    </div>
@endif
```

**Usage in layout:**
```blade
<x-galaxy-layout>
    <x-notification />
    
    {{ $slot }}
</x-galaxy-layout>
```

## Completion Checklist

When implementing notifications, verify:

- [ ] Notification type matches the action result (success/error/info/warning)
- [ ] Message is specific and user-focused
- [ ] Message is one sentence or less
- [ ] Notification appears in top-right corner
- [ ] Notification auto-dismisses after 5 seconds
- [ ] Notification has appropriate icon and color
- [ ] Mobile layout is responsive
- [ ] Errors are logged (not just shown to user)

