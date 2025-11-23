<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use MatanYadaev\EloquentSpatial\Objects\Point;

class EditProfile extends Component
{
    use WithFileUploads;

    #[Validate('nullable|string|max:100')]
    public $display_name;

    #[Validate('nullable|string|max:500')]
    public $bio;

    #[Validate('nullable|array|min:1|max:10')]
    public $interests = [];

    #[Validate('nullable|string')]
    public $newInterest = '';

    #[Validate('nullable|string|max:255')]
    public $location_name;

    #[Validate('nullable|numeric|between:-90,90')]
    public $latitude;

    #[Validate('nullable|numeric|between:-180,180')]
    public $longitude;

    #[Validate('nullable|image|max:2048')]
    public $profile_image;

    public $current_profile_image_url;

    public function mount()
    {
        $user = Auth::user();
        $this->display_name = $user->display_name;
        $this->bio = $user->bio;
        $this->interests = $user->interests ?? [];
        $this->location_name = $user->location_name;
        $this->current_profile_image_url = $user->profile_image_url;

        if ($user->location_coordinates) {
            $this->latitude = $user->location_coordinates->latitude;
            $this->longitude = $user->location_coordinates->longitude;
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
        if (! in_array($interest, $this->interests)) {
            $this->interests[] = $interest;
        }

        $this->newInterest = '';
    }

    public function removeInterest($index)
    {
        unset($this->interests[$index]);
        $this->interests = array_values($this->interests);
    }

    public function save()
    {
        $this->validate();

        $user = Auth::user();

        $data = [
            'display_name' => $this->display_name,
            'bio' => $this->bio,
            'interests' => $this->interests,
            'location_name' => $this->location_name,
        ];

        // Handle location coordinates
        if ($this->latitude && $this->longitude) {
            $data['location_coordinates'] = new Point($this->latitude, $this->longitude);
        }

        // Handle profile image upload
        if ($this->profile_image) {
            // Delete old image if exists
            if ($user->profile_image_url) {
                Storage::disk('public')->delete($user->profile_image_url);
            }

            // Store new image
            $path = $this->profile_image->store('profiles', 'public');
            $data['profile_image_url'] = $path;
            $this->current_profile_image_url = $path;
        }

        $user->update($data);

        $this->dispatch('profile-updated');

        session()->flash('message', 'Profile updated successfully!');
    }

    public function removeProfileImage()
    {
        $user = Auth::user();

        if ($user->profile_image_url) {
            Storage::disk('public')->delete($user->profile_image_url);
            $user->update(['profile_image_url' => null]);
            $this->current_profile_image_url = null;
        }
    }

    public function render()
    {
        return view('livewire.profile.edit-profile')
            ->layout('layouts.auth', [
                'title' => 'Edit Profile',
            ]);
    }
}
