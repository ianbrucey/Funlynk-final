<?php

namespace App\Livewire\Concerns;

use App\Models\User;
use App\Services\FollowService;
use Illuminate\Support\Facades\Auth;

/**
 * Follow / unfollow a host from a Livewire component (spec 009).
 * First follow ever shows the intro pop-up; later follows show a toast.
 */
trait HandlesHostFollow
{
    abstract protected function afterHostFollowChange(string $hostId, bool $following): void;

    public function followHost(string $hostId): void
    {
        $viewer = Auth::user();

        if (! $viewer) {
            $this->redirect(route('login'));

            return;
        }

        $host = User::find($hostId);

        if (! $host || $host->id === $viewer->id) {
            return;
        }

        $name = $host->display_name ?? $host->username ?? 'this host';

        try {
            $service = app(FollowService::class);
            $created = $service->follow($viewer, $host);
            $showIntro = $created && $service->markIntroSeen($viewer);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('show-toast', message: "Couldn't follow {$name}. Try again.", type: 'error');

            return;
        }

        $this->afterHostFollowChange($hostId, true);

        if ($showIntro) {
            $this->dispatch('follow-intro', name: $name);
        } elseif ($created) {
            $this->dispatch('show-toast', message: "Following {$name}. You'll be notified of their events.", type: 'success');
        }
    }

    public function unfollowHost(string $hostId): void
    {
        $viewer = Auth::user();
        $host = User::find($hostId);

        if (! $viewer || ! $host) {
            return;
        }

        app(FollowService::class)->unfollow($viewer, $host);
        $this->afterHostFollowChange($hostId, false);
        $this->dispatch('show-toast', message: 'Unfollowed '.($host->display_name ?? $host->username).'.', type: 'success');
    }
}
