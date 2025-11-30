<?php

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationList extends Component
{
    use WithPagination;

    public function markAsRead(string $notificationId): void
    {
        $notification = Notification::find($notificationId);

        if ($notification && $notification->user_id === auth()->id()) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllAsRead(): void
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->resetPage();
    }

    public function handleNotificationClick(string $notificationId, string $url = ''): void
    {
        $notification = Notification::find($notificationId);

        if ($notification && $notification->user_id === auth()->id()) {
            $notification->update(['read_at' => now()]);

            // Navigate based on notification type
            if ($notification->type === 'post_reaction' || $notification->type === 'post_invitation') {
                if (! empty($url)) {
                    $this->redirect($url);
                } elseif (isset($notification->data['post_id'])) {
                    $this->redirect(route('posts.show', $notification->data['post_id']));
                }
            } elseif ($notification->type === 'post_conversion_prompt') {
                if (isset($notification->data['post_id'])) {
                    $this->redirect(route('posts.show', $notification->data['post_id']));
                }
            }
        }
    }

    public function render()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $unreadCount = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return view('livewire.notifications.notification-list', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ])->layout('layouts.app', ['title' => 'Notifications']);
    }
}
