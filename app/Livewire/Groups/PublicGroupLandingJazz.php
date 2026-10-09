<?php

namespace App\Livewire\Groups;

use Livewire\Component;

class PublicGroupLandingJazz extends Component
{
    // Mock Data for the Jazz UI
    public $groupName = 'Underground Jazz Jam';

    public $memberCount = 142;

    public $nextSession = 'Thursday, Oct 30 @ 8:00 PM';

    public $location = 'The Blue Note · Basement';

    public function render()
    {
        return view('livewire.groups.public-group-landing-jazz')
            ->layout('layouts.app');
    }
}
