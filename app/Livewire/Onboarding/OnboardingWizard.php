<?php

namespace App\Livewire\Onboarding;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use MatanYadaev\EloquentSpatial\Objects\Point;

class OnboardingWizard extends Component
{
    use WithFileUploads;

    // Step order: 1=Location, 2=Photo, 3=Interests
    public int $currentStep = 1;

    // Location data (Step 1)
    public string $location_name = '';
    public ?float $latitude = null;
    public ?float $longitude = null;

    // Profile image (Step 2)
    #[Validate('nullable|image|max:2048')]
    public $profileImage = null;
    public ?string $uploadedImagePath = null;

    // Interests (Step 3)
    public array $interests = [];
    public string $newInterest = '';

    public function mount()
    {
        // If already completed onboarding, redirect to dashboard
        if (Auth::user()->hasCompletedOnboarding()) {
            return redirect()->route('feed.nearby');
        }

        // Pre-fill existing user data
        $user = Auth::user();

        // Load existing location
        if ($user->location_name && $user->location_coordinates) {
            $this->location_name = $user->location_name;
            $this->latitude = $user->location_coordinates->latitude;
            $this->longitude = $user->location_coordinates->longitude;
        }

        // Load existing profile image
        if ($user->profile_image_url) {
            $this->uploadedImagePath = $user->profile_image_url;
        }

        // Load existing interests
        if ($user->interests) {
            $this->interests = $user->interests;
        }

        // Determine starting step based on what's already completed
        if ($this->location_name && $this->latitude && $this->longitude) {
            if ($this->uploadedImagePath) {
                $this->currentStep = 3; // Both location and photo done, go to interests
            } else {
                $this->currentStep = 2; // Location done, go to photo
            }
        }
    }

    /**
     * Called from JavaScript when a location is selected
     */
    public function setLocationData(string $name, $lat, $lng)
    {
        $this->location_name = $name;
        $this->latitude = $lat ? (float) $lat : null;
        $this->longitude = $lng ? (float) $lng : null;
    }

    /**
     * Step 1 (Location) -> Step 2 (Photo)
     */
    public function nextStepFromLocation()
    {
        $this->validate([
            'location_name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $this->currentStep = 2;
    }

    /**
     * Step 2 (Photo) -> Step 3 (Interests)
     */
    public function nextStepFromPhoto()
    {
        // If we already have an uploaded image, just proceed
        if ($this->uploadedImagePath) {
            $this->currentStep = 3;
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

        $this->currentStep = 3;
    }

    /**
     * Go back to previous step
     */
    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Add an interest tag
     */
    public function addInterest()
    {
        $interest = trim($this->newInterest);

        if (empty($interest)) {
            return;
        }

        if (count($this->interests) >= 10) {
            $this->addError('interests', 'Maximum 10 interests allowed.');
            return;
        }

        if (!in_array($interest, $this->interests)) {
            $this->interests[] = $interest;
        }

        $this->newInterest = '';
    }

    /**
     * Remove an interest tag
     */
    public function removeInterest(int $index)
    {
        unset($this->interests[$index]);
        $this->interests = array_values($this->interests);
    }

    /**
     * Complete onboarding and save all data
     */
    public function complete()
    {
        // Final validation
        $this->validate([
            'location_name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        // Ensure we have a profile image
        if (!$this->uploadedImagePath) {
            $this->addError('profileImage', 'Profile image is required.');
            $this->currentStep = 2;
            return;
        }

        $user = Auth::user();

        // Update user with all onboarding data
        $user->update([
            'location_name' => $this->location_name,
            'location_coordinates' => new Point($this->latitude, $this->longitude),
            'interests' => $this->interests ?: null,
        ]);

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
