<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UserDashboard extends Component
{
    public User $user;

    public Collection $myGroups;

    public Collection $recentNotifications;

    public Collection $interestedPosts;

    public Collection $upcomingEvents;

    public function mount()
    {
        $this->user = Auth::user();
        // TODO: Replace with actual data fetching from backend services
        $this->myGroups = collect();
        $this->recentNotifications = collect();
        $this->interestedPosts = collect();
        $this->upcomingEvents = collect();
    }

    public function render()
    {
        return view('livewire.dashboard.user-dashboard')
            ->layout('layouts.app');
    }
}
