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

    public function render()
    {
        return view('livewire.notifications.notification-bell');
    }
}
