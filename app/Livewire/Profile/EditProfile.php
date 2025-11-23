<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use MatanYadaev\EloquentSpatial\Objects\Point;

class EditProfile extends Component
{
    use WithFileUploads;

    #[Validate('required|string|min:3|max:50|alpha_dash')]
    public $username;

    #[Validate('nullable|string|max:100')]
    public $display_name;

    #[Validate('nullable|string|max:500')]
    public $bio;

    public ?bool $usernameAvailable = null;

    #[Validate('nullable|array|min:1|max:10')]
    public $interests = [];

    #[Validate('nullable|string')]
    public $newInterest = '';

    #[Validate('nullable|string|max:255')]
    public $location_name;

    #[Validate('nullable|numeric|between:-90,90')]
    public $latitude = null;

    #[Validate('nullable|numeric|between:-180,180')]
    public $longitude = null;

    #[Validate('nullable|image|max:2048')]
    public $profile_image;

    public $current_profile_image_url;

    public function mount()
    {
        // Use DB query to avoid loading the Point object through Eloquent casting
        $userData = \DB::table('users')
            ->select('username', 'display_name', 'bio', 'interests', 'location_name', 'profile_image_url',
                     \DB::raw('ST_Y(location_coordinates::geometry) as latitude'),
                     \DB::raw('ST_X(location_coordinates::geometry) as longitude'))
            ->where('id', Auth::id())
            ->first();

        if ($userData) {
            $this->username = $userData->username ?? '';
            $this->display_name = $userData->display_name ?? '';
            $this->bio = $userData->bio ?? '';
            $this->interests = $userData->interests ? json_decode($userData->interests, true) : [];
            $this->location_name = $userData->location_name ?? '';
            $this->current_profile_image_url = $userData->profile_image_url;
            $this->latitude = $userData->latitude ? (float) $userData->latitude : null;
            $this->longitude = $userData->longitude ? (float) $userData->longitude : null;
        }
    }

    public function hydrate()
    {
        // Ensure coordinates are always primitives after hydration
        if ($this->latitude !== null && !is_float($this->latitude) && !is_int($this->latitude)) {
            $this->latitude = (float) $this->latitude;
        }
        if ($this->longitude !== null && !is_float($this->longitude) && !is_int($this->longitude)) {
            $this->longitude = (float) $this->longitude;
        }
    }

    public function dehydrate()
    {
        // Ensure coordinates are always primitives, never objects
        if ($this->latitude !== null) {
            $this->latitude = (float) $this->latitude;
        }
        if ($this->longitude !== null) {
            $this->longitude = (float) $this->longitude;
        }
    }

    public function updatedUsername($value)
    {
        if (strlen($value) >= 3) {
            $username = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::slug($value));
            $exists = \App\Models\User::where('username', $username)
                ->where('id', '!=', Auth::id())
                ->exists();
            $this->usernameAvailable = !$exists;
        } else {
            $this->usernameAvailable = null;
        }
    }

    public function updatedLatitude($value)
    {
        $this->latitude = $value ? (float) $value : null;
    }

    public function updatedLongitude($value)
    {
        $this->longitude = $value ? (float) $value : null;
    }

    public function setLocationData($name, $lat, $lng)
    {
        $this->location_name = $name;
        $this->latitude = $lat ? (float) $lat : null;
        $this->longitude = $lng ? (float) $lng : null;
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

        $this->reset('newInterest');
    }

    public function removeInterest($index)
    {
        unset($this->interests[$index]);
        $this->interests = array_values($this->interests);
    }

    public function save()
    {
        $this->validate();

        $userId = Auth::id();

        // Get current username and profile image without loading Point object
        $currentData = \DB::table('users')
            ->select('username', 'profile_image_url')
            ->where('id', $userId)
            ->first();

        $currentUsername = $currentData->username ?? '';
        $currentProfileImage = $currentData->profile_image_url ?? null;

        // Validate username uniqueness if changed
        if ($this->username !== $currentUsername) {
            $usernameSlug = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::slug($this->username));
            $exists = \App\Models\User::where('username', $usernameSlug)
                ->where('id', '!=', $userId)
                ->exists();

            if ($exists) {
                $this->addError('username', 'This username is already taken.');
                return;
            }

            $this->username = $usernameSlug;
        }

        $data = [
            'username' => $this->username,
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
            if ($currentProfileImage) {
                Storage::disk('public')->delete($currentProfileImage);
            }

            // Store new image
            $path = $this->profile_image->store('profiles', 'public');
            $data['profile_image_url'] = $path;
            $this->current_profile_image_url = $path;
        }

        \App\Models\User::where('id', $userId)->update($data);

        $this->dispatch('profile-updated');

        session()->flash('message', 'Profile updated successfully!');
    }

    public function removeProfileImage()
    {
        $userId = Auth::id();

        // Get current profile image without loading Point object
        $currentData = \DB::table('users')
            ->select('profile_image_url')
            ->where('id', $userId)
            ->first();

        $currentProfileImage = $currentData->profile_image_url ?? null;

        if ($currentProfileImage) {
            Storage::disk('public')->delete($currentProfileImage);
            \App\Models\User::where('id', $userId)->update(['profile_image_url' => null]);
            $this->current_profile_image_url = null;
        }
    }

    public function render()
    {
        return view('livewire.profile.edit-profile')
            ->layout('layouts.app', [
                'title' => 'Edit Profile',
            ]);
    }
}
