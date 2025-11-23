<?php

namespace App\Livewire\Profile;

use App\Models\User;
use Livewire\Component;

class ShowProfile extends Component
{
    public User $user;

    public function mount($username = null)
    {
        // If no username provided, show current user's profile
        if ($username) {
            $this->user = User::where('username', $username)->firstOrFail();
        } else {
            $this->user = auth()->user();
        }
    }

    public function render()
    {
        return view('livewire.profile.show-profile')
            ->layout('layouts.app', ['title' => $this->user->display_name ?? $this->user->name]);
    }
}
