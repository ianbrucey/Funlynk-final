<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use Livewire\Component;

class GroupCard extends Component
{
    public Group $group;

    public function mount(Group $group): void
    {
        $this->group = $group;
    }

    public function render()
    {
        return view('livewire.groups.group-card');
    }
}
