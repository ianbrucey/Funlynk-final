<?php

namespace App\Livewire\Onboarding;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use MatanYadaev\EloquentSpatial\Objects\Point;

class OnboardingWizard extends Component
{
    public int $currentStep = 1;

    #[Validate('required|string|max:255')]
    public $location_name = '';

    #[Validate('required|numeric|between:-90,90')]
    public $latitude = null;

    #[Validate('required|numeric|between:-180,180')]
    public $longitude = null;

    #[Validate('nullable|array|max:10')]
    public $interests = [];

    #[Validate('nullable|string|max:50')]
    public $newInterest = '';

    public function mount()
    {
        // If already completed onboarding, redirect to dashboard
        if (Auth::user()->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        // Pre-fill if user already has some data
        $user = Auth::user();
        if ($user->location_name) {
            $this->location_name = $user->location_name;
        }
        if ($user->location_coordinates) {
            $this->latitude = $user->location_coordinates->latitude;
            $this->longitude = $user->location_coordinates->longitude;
        }
        if ($user->interests) {
            $this->interests = $user->interests;
        }
    }

    public function setLocationData($name, $lat, $lng)
    {
        $this->location_name = $name;
        $this->latitude = $lat ? (float) $lat : null;
        $this->longitude = $lng ? (float) $lng : null;
        $this->currentStep = 2;
    }

    public function nextStep()
    {
        // Validate step 1 before proceeding
        $this->validate([
            'location_name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $this->currentStep = 2;
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function addInterest()
    {
        if (empty($this->newInterest)) {
            return;
        }

        if (count($this->interests) >= 10) {
            $this->addError('interests', 'Maximum 10 interests allowed.');
            return;
        }

        $interest = trim($this->newInterest);
        if (!in_array($interest, $this->interests)) {
            $this->interests[] = $interest;
        }

        $this->reset('newInterest');
    }

    public function removeInterest($index)
    {
        unset($this->interests[$index]);
        $this->interests = array_values($this->interests);
    }

    public function complete()
    {
        // Validate all fields
        $this->validate([
            'location_name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'interests' => 'nullable|array|max:10',
        ]);

        $user = Auth::user();

        // Update user with onboarding data
        $user->update([
            'location_name' => $this->location_name,
            'location_coordinates' => new Point($this->latitude, $this->longitude),
            'interests' => $this->interests,
        ]);

        // Mark onboarding as complete
        $user->markOnboardingComplete();

        session()->flash('message', 'Welcome to FunLynk! Your profile is all set.');

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.onboarding.onboarding-wizard')
            ->layout('layouts.auth', [
                'title' => 'Complete Your Profile',
            ]);
    }
}
