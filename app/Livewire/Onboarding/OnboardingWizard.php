<?php

namespace App\Livewire\Onboarding;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use MatanYadaev\EloquentSpatial\Objects\Point;

class OnboardingWizard extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;

    #[Validate('nullable|image|max:2048')]
    public $profileImage = null;

    // Store the uploaded image path (persisted to DB)
    public ?string $uploadedImagePath = null;

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
            return redirect()->route('feed.nearby');
        }

        // Pre-fill if user already has some data
        $user = Auth::user();

        // Check if profile image already uploaded
        if ($user->profile_image_url) {
            $this->uploadedImagePath = $user->profile_image_url;
            // Start at step 2 if we have profile image
            $this->currentStep = 2;
        }

        if ($user->location_name) {
            $this->location_name = $user->location_name;
            // If we have location too, start at step 3
            if ($user->location_coordinates && $this->uploadedImagePath) {
                $this->latitude = $user->location_coordinates->latitude;
                $this->longitude = $user->location_coordinates->longitude;
                $this->currentStep = 3;
            }
        }

        if ($user->interests) {
            $this->interests = $user->interests;
        }
    }

    public function nextStepFromProfilePicture()
    {
        // If we already have an uploaded image, just proceed
        if ($this->uploadedImagePath) {
            $this->currentStep = 2;
            return;
        }

        // Otherwise validate and upload the new image
        $this->validate([
            'profileImage' => 'required|image|max:2048',
        ]);

        // Upload to S3 immediately and save to user
        $path = $this->profileImage->store('profile-images', 's3');

        Auth::user()->update([
            'profile_image_url' => $path,
        ]);

        $this->uploadedImagePath = $path;
        $this->profileImage = null; // Clear the temporary file

        $this->currentStep = 2;
    }

    public function setLocationData($name, $lat, $lng)
    {
        $this->location_name = $name;
        $this->latitude = $lat ? (float) $lat : null;
        $this->longitude = $lng ? (float) $lng : null;
        $this->currentStep = 3;
    }

    public function nextStep()
    {
        // Validate step 2 (location) before proceeding
        $this->validate([
            'location_name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $this->currentStep = 3;
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
        // Validate location and interests (profile image was already saved in step 1)
        $this->validate([
            'location_name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'interests' => 'nullable|array|max:10',
        ]);

        // Ensure we have a profile image (either just uploaded or from previous step)
        if (!$this->uploadedImagePath && !$this->profileImage) {
            $this->addError('profileImage', 'Profile image is required.');
            $this->currentStep = 1;
            return;
        }

        $user = Auth::user();

        $updateData = [
            'location_name' => $this->location_name,
            'location_coordinates' => new Point($this->latitude, $this->longitude),
            'interests' => $this->interests,
        ];

        // If there's a new profile image to upload (shouldn't happen normally, but just in case)
        if ($this->profileImage) {
            $updateData['profile_image_url'] = $this->profileImage->store('profile-images', 's3');
        }

        // Update user with onboarding data
        $user->update($updateData);

        // Mark onboarding as complete
        $user->markOnboardingComplete();

        return redirect()->route('feed.nearby');
    }

    public function render()
    {
        return view('livewire.onboarding.onboarding-wizard')
            ->layout('layouts.auth', [
                'title' => 'Complete Your Profile',
            ]);
    }
}
