<?php

namespace App\Livewire\Discovery;

use App\Models\Activity;
use App\Models\Post;
use Livewire\Component;

class MapView extends Component
{
    public $userLat;
    public $userLng;
    public $radius = 10; // km
    public $contentType = 'all'; // all, posts, events

    public function mount()
    {
        // Get user's location from auth user
        // For now, use a default location (San Francisco)
        $this->userLat = auth()->user()->latitude ?? 37.7749;
        $this->userLng = auth()->user()->longitude ?? -122.4194;
    }

    public function getMapData()
    {
        $user = auth()->user();

        $data = app(\App\Services\FeedService::class)->getMapData(
            $user,
            radius: (int) $this->radius,
            contentType: (string) $this->contentType,
        );

        return $data['markers'];
    }

    public function render()
    {
        return view('livewire.discovery.map-view', [
            'markers' => $this->getMapData()
        ])->layout('layouts.app', ['title' => 'Map View']);
    }
}
