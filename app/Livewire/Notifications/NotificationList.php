<?php

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationList extends Component
{
    use WithPagination;

    /**
     * Auto-mark all notifications as read when page loads (Facebook-style behavior)
     */
    public function mount(): void
    {
        if (auth()->check()) {
            Notification::where('user_id', auth()->id())
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = Notification::find($notificationId);

        if ($notification && $notification->user_id === auth()->id()) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
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

    public $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Notification::where('user_id', auth()->id());

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $term = '%' . $this->search . '%';
                
                // Search user-facing text fields
                $q->where('data->actor_name', 'like', $term)
                  ->orWhere('data->post_title', 'like', $term)
                  ->orWhere('data->message', 'like', $term)
                  ->orWhere('data->reactor_name', 'like', $term)
                  ->orWhere('data->inviter_name', 'like', $term)
                  ->orWhere('data->post_location', 'like', $term)
                  ->orWhere('title', 'like', $term)
                  ->orWhere('message', 'like', $term);
            });
        }

        $notifications = $query->orderBy('created_at', 'desc')
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
