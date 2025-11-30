<?php

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Illuminate\Support\Collection;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public Collection $recentNotifications;

    protected $listeners = ['notificationReceived' => 'loadNotifications'];

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        // Show recent notifications (both read and unread) so users can see what they clicked on
        $this->recentNotifications = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Count only unread for the badge
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

    /**
     * Mark all unread notifications as read when dropdown opens (Facebook-style behavior)
     */
    public function markAllAsReadOnOpen(): void
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->loadNotifications();
    }

    public function handleNotificationClick(string $notificationId, string $url = ''): void
    {
        $notification = Notification::find($notificationId);

        if ($notification) {
            $notification->update(['read_at' => now()]);
            $this->loadNotifications();

            // Navigate based on notification type
            if ($notification->type === 'post_reaction' || $notification->type === 'post_invitation') {
                if (! empty($url)) {
                    $this->redirect($url);
                } elseif (isset($notification->data['post_id'])) {
                    $this->redirect(route('posts.show', $notification->data['post_id']));
                }
            } elseif ($notification->type === 'post_conversion_prompt') {
                // Redirect to post detail page where conversion button is available
                if (isset($notification->data['post_id'])) {
                    $this->redirect(route('posts.show', $notification->data['post_id']));
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.notifications.notification-bell');
    }
}
