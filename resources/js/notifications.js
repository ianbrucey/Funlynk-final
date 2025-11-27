/**
 * Real-Time Notifications via WebSocket
 * 
 * This file sets up the WebSocket connection for real-time notifications.
 * Each user subscribes to a single channel: user.{userId}
 * All notification types flow through this channel.
 * 
 * TODO: Agent B will configure Pusher/Soketi and create the broadcast channels
 */

// Get user ID from meta tag
const userId = document.querySelector('meta[name="user-id"]')?.content;

if (userId && typeof window.Echo !== 'undefined') {
    console.log(`[Notifications] Subscribing to channel: user.${userId}`);
    
    // Subscribe to user's notification channel
    window.Echo.channel(`user.${userId}`)
        .listen('.notification', (notification) => {
            console.log('[Notifications] Received:', notification);
            
            // Update notification bell count
            if (window.Livewire) {
                window.Livewire.dispatch('notificationReceived');
            }
            
            // Show toast notification
            showToast(notification);
        });
} else {
    if (!userId) {
        console.warn('[Notifications] User ID not found. Add <meta name="user-id" content="{{ auth()->id() }}"> to layout.');
    }
    if (typeof window.Echo === 'undefined') {
        console.warn('[Notifications] Laravel Echo not initialized. Agent B will configure this.');
    }
}

/**
 * Show toast notification
 */
function showToast(notification) {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 glass-card p-4 rounded-xl border border-white/10 z-50 animate-slide-in max-w-sm shadow-2xl';
    
    // Get notification icon
    const icon = getNotificationIcon(notification.type);
    
    // Get notification message
    const message = getNotificationMessage(notification);
    
    // Build toast HTML
    toast.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="text-2xl">${icon}</div>
            <div class="flex-1">
                <p class="text-white font-semibold text-sm">${notification.data.reactor_name || notification.data.inviter_name || 'Someone'}</p>
                <p class="text-gray-400 text-xs mt-1">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(toast);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

/**
 * Get icon for notification type
 */
function getNotificationIcon(type) {
    const icons = {
        'post_reaction': '👍',
        'post_invitation': '📨',
        'post_conversion': '🎉',
        'activity_rsvp': '✅',
        'comment': '💬',
        'follow': '👥',
    };
    return icons[type] || '🔔';
}

/**
 * Get message for notification
 */
function getNotificationMessage(notification) {
    const { type, subtype, data } = notification;
    
    if (type === 'post_reaction') {
        if (subtype === 'im_down') {
            return `is down for "${data.post_title}"`;
        }
    } else if (type === 'post_invitation') {
        return `invited you to "${data.post_title}"`;
    } else if (type === 'post_conversion') {
        if (subtype === 'suggested') {
            return `Your post "${data.post_title}" can be converted to an event!`;
        } else if (subtype === 'auto_converted') {
            return `Your post "${data.post_title}" was converted to an event!`;
        }
    }
    
    return 'New notification';
}

/**
 * Add slide-in animation
 */
if (!document.querySelector('#notification-animations')) {
    const style = document.createElement('style');
    style.id = 'notification-animations';
    style.textContent = `
        @keyframes slide-in {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }
    `;
    document.head.appendChild(style);
}

console.log('[Notifications] Module loaded. Waiting for Agent B to configure broadcasting...');

