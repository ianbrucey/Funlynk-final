<?php

namespace App\Livewire\Components;

use Livewire\Attributes\Modelable;
use Livewire\Component;

class LocationAutocomplete extends Component
{
    #[Modelable]
    public $locationName = '';

    #[Modelable]
    public $latitude = null;

    #[Modelable]
    public $longitude = null;

    public $inputId;

    public $placeholder = 'Search for a location...';

    public $label = 'Location';

    public $showCurrentLocationButton = true;

    public function mount()
    {
        $this->inputId = 'location-input-'.uniqid();
    }

    public function setLocation($name, $lat, $lng)
    {
        $this->locationName = $name;
        $this->latitude = $lat;
        $this->longitude = $lng;
    }

    public function render()
    {
        return view('livewire.components.location-autocomplete');
    }
}
