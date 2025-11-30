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

    public function convertPost(string $postId): void
    {
        $this->dispatch('open-conversion-modal', postId: $postId);
    }

    public function dismissPrompt(string $postId, string $notificationId): void
    {
        // Call service to dismiss (will be implemented by Agent A)
        // app(\App\Services\PostService::class)->dismissConversionPrompt($postId);

        // Mark notification as read
        Notification::find($notificationId)?->update(['read_at' => now()]);

        $this->loadNotifications();

        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Conversion prompt dismissed',
        ]);
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
                $this->dispatch('open-conversion-modal', postId: $notification->data['post_id']);
            }
        }
    }

    public function render()
    {
        return view('livewire.notifications.notification-bell');
    }
}
