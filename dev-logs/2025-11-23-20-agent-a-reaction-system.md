# Agent A: Post Reaction System UI
**Date**: 2025-11-23 20:00  
**Epic**: E04 Discovery Engine + E05 Social Interaction  
**Estimated Time**: 5-7 hours  
**Prerequisites**: Agent B must complete Tasks 1-5 first

## Context

Build the UI for post reactions with real-time notifications:
- **Notification Bell**: Show unread count, dropdown with recent notifications
- **Friend Selector Modal**: Invite friends to posts
- **WebSocket Integration**: Real-time notification updates
- **Single Channel**: Subscribe to `user.{userId}` for ALL notifications

## Your Tasks

### Task 1: Notification Bell Component (2 hours)

**Create Component**:
```bash
php artisan make:livewire Notifications/NotificationBell --no-interaction
```

**File**: `app/Livewire/Notifications/NotificationBell.php`
```php
class NotificationBell extends Component
{
    public int $unreadCount = 0;
    public Collection $recentNotifications;
    
    public function mount(): void
    {
        $this->loadNotifications();
    }
    
    public function loadNotifications(): void
    {
        $this->recentNotifications = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        $this->unreadCount = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();
    }
    
    public function markAsRead(string $notificationId): void
    {
        Notification::where('id', $notificationId)->update(['read_at' => now()]);
        $this->loadNotifications();
    }
    
    public function markAllAsRead(): void
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        $this->loadNotifications();
    }
    
    public function render()
    {
        return view('livewire.notifications.notification-bell');
    }
}
```

**View**: `resources/views/livewire/notifications/notification-bell.blade.php`
```blade
<div class="relative" x-data="{ open: false }">
    <!-- Bell Icon -->
    <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-white transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 w-5 h-5 bg-pink-500 rounded-full text-xs text-white flex items-center justify-center">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>
    
    <!-- Dropdown -->
    <div x-show="open" 
         @click.away="open = false"
         x-transition
         class="absolute right-0 mt-2 w-80 glass-card border border-white/10 rounded-xl overflow-hidden z-50">
        
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <h3 class="text-white font-semibold">Notifications</h3>
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-pink-400 hover:text-pink-300">
                    Mark all read
                </button>
            @endif
        </div>
        
        <div class="max-h-96 overflow-y-auto">
            @forelse($recentNotifications as $notification)
                <div wire:click="markAsRead('{{ $notification->id }}')" 
                     class="p-4 hover:bg-white/5 cursor-pointer border-b border-white/5 transition">
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 bg-pink-500 rounded-full mt-2"></div>
                        <div class="flex-1">
                            <p class="text-white text-sm font-medium">{{ $notification->title }}</p>
                            <p class="text-gray-400 text-xs mt-1">{{ $notification->message }}</p>
                            <p class="text-gray-500 text-xs mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <p class="text-sm">No notifications</p>
                </div>
            @endforelse
        </div>
        
        <div class="p-3 border-t border-white/10 text-center">
            <a href="{{ route('notifications.index') }}" class="text-sm text-pink-400 hover:text-pink-300">
                View all notifications
            </a>
        </div>
    </div>
</div>
```

**Add to Navbar** (`resources/views/components/navbar.blade.php`):
```blade
<!-- Add before profile icon -->
<livewire:notifications.notification-bell />
```

---

### Task 2: Notifications Page (2 hours)

**Create Component**:
```bash
php artisan make:livewire Notifications/Index --no-interaction
```

**Add Route** to `routes/web.php`:
```php
Route::get('/notifications', \App\Livewire\Notifications\Index::class)->name('notifications.index');
```

**Implement pagination, filtering, mark as read/unread**.

---

### Task 3: Friend Selector Modal (2 hours)

**Create Component**:
```bash
php artisan make:livewire Posts/InviteFriendsModal --no-interaction
```

**File**: `app/Livewire/Posts/InviteFriendsModal.php`
```php
class InviteFriendsModal extends Component
{
    public ?string $postId = null;
    public bool $show = false;
    public string $search = '';
    public array $selectedFriends = [];
    public Collection $friends;
    
    protected $listeners = ['openInviteModal'];
    
    public function openInviteModal(string $postId): void
    {
        $this->postId = $postId;
        $this->show = true;
        $this->loadFriends();
    }
    
    public function loadFriends(): void
    {
        $this->friends = auth()->user()
            ->following()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->get();
    }
    
    public function toggleFriend(string $friendId): void
    {
        if (in_array($friendId, $this->selectedFriends)) {
            $this->selectedFriends = array_diff($this->selectedFriends, [$friendId]);
        } else {
            $this->selectedFriends[] = $friendId;
        }
    }
    
    public function inviteFriends(): void
    {
        if (empty($this->selectedFriends)) {
            return;
        }
        
        app(PostService::class)->inviteFriendsToPost($this->postId, $this->selectedFriends);
        
        session()->flash('success', count($this->selectedFriends) . ' friends invited!');
        $this->reset(['show', 'selectedFriends', 'search']);
    }
    
    public function render()
    {
        return view('livewire.posts.invite-friends-modal');
    }
}
```

**Update Post Card** (`resources/views/components/post-card.blade.php`):
```blade
<!-- Add to action buttons -->
<button 
    wire:click="$dispatch('openInviteModal', { postId: '{{ $post->id }}' })"
    class="px-4 py-2 bg-purple-500/20 text-purple-400 rounded-lg hover:bg-purple-500/30 transition">
    Invite Friends
</button>
```

---

### Task 4: WebSocket Integration (1 hour)

**Create JavaScript File**: `resources/js/notifications.js`
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true
});

// Subscribe to user's notification channel
const userId = document.querySelector('meta[name="user-id"]')?.content;

if (userId) {
    window.Echo.channel(`user.${userId}`)
        .listen('.notification', (notification) => {
            console.log('Notification received:', notification);
            
            // Update notification bell count
            Livewire.dispatch('notificationReceived');
            
            // Show toast notification
            showToast(notification);
        });
}

function showToast(notification) {
    // Simple toast implementation
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 glass-card p-4 rounded-xl border border-white/10 z-50 animate-slide-in';
    toast.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="text-2xl">${getNotificationIcon(notification.type)}</div>
            <div>
                <p class="text-white font-semibold">${notification.data.reactor_name || notification.data.inviter_name}</p>
                <p class="text-gray-400 text-sm">${getNotificationMessage(notification)}</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

function getNotificationIcon(type) {
    const icons = {
        'post_reaction': '👍',
        'post_invitation': '📨',
        'post_conversion': '🎉'
    };
    return icons[type] || '🔔';
}

function getNotificationMessage(notification) {
    if (notification.type === 'post_reaction') {
        return `is down for "${notification.data.post_title}"`;
    } else if (notification.type === 'post_invitation') {
        return `invited you to "${notification.data.post_title}"`;
    }
    return 'New notification';
}
```

**Import in** `resources/js/app.js`:
```javascript
import './notifications';
```

**Add User ID Meta Tag** to layout:
```blade
<meta name="user-id" content="{{ auth()->id() }}">
```

**Update NotificationBell** to listen for new notifications:
```php
protected $listeners = ['notificationReceived' => 'loadNotifications'];
```

---

## Testing

Manually test:
- Click "I'm Down" → notification appears in bell
- Click "Invite Friends" → modal opens, friends can be selected
- Real-time notifications appear as toasts
- Notification bell count updates in real-time

---

## Success Criteria

✅ Notification bell shows unread count
✅ Dropdown shows recent notifications
✅ Notifications page shows all notifications
✅ Friend selector modal works
✅ Real-time notifications appear as toasts
✅ WebSocket connection stable
✅ Galaxy theme consistent throughout

---

**Wait for Agent B to complete Tasks 1-5 before starting. Ask questions if anything is unclear.**

