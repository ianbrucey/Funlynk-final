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

    #[Validate('required|string|min:3|max:50|alpha_dash')]
    public $username;

    #[Validate('nullable|string|max:100')]
    public $display_name;

    #[Validate('nullable|string|max:500')]
    public $bio;

    public ?bool $usernameAvailable = null;

    #[Validate('nullable|array|max:10')]
    public $interests = [];

    #[Validate('nullable|string')]
    public $newInterest = '';

    #[Validate('nullable|string|max:255')]
    public $location_name;

    #[Validate('nullable|numeric|between:-90,90')]
    public $latitude = null;

    #[Validate('nullable|numeric|between:-180,180')]
    public $longitude = null;

    #[Validate('nullable|image|max:6144')]
    public $profile_image;

    public $current_profile_image_url;

    public function getCanAcceptPaymentsProperty()
    {
        $user = Auth::user();

        if (! $user->stripeAccount) {
            return false;
        }

        return $user->stripeAccount->canAcceptPayments();
    }

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
        if ($this->latitude !== null && ! is_float($this->latitude) && ! is_int($this->latitude)) {
            $this->latitude = (float) $this->latitude;
        }
        if ($this->longitude !== null && ! is_float($this->longitude) && ! is_int($this->longitude)) {
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
            $this->usernameAvailable = ! $exists;
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

    public string $successMessage = '';

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

        // Find the user model (without loading location_coordinates to avoid Point issues)
        $user = \App\Models\User::withoutGlobalScopes()->find($userId);

        if (! $user) {
            $this->addError('save', 'User not found.');

            return;
        }

        // Update fields
        $user->username = $this->username;
        $user->display_name = $this->display_name;
        $user->bio = $this->bio;
        $user->interests = $this->interests; // Cast will handle JSON encoding
        $user->location_name = $this->location_name;

        // Handle location coordinates
        if ($this->latitude && $this->longitude) {
            $user->location_coordinates = new Point($this->latitude, $this->longitude);
        }

        // Handle profile image upload
        if ($this->profile_image) {
            // Delete old image if exists
            if ($currentProfileImage) {
                Storage::disk('s3')->delete($currentProfileImage);
            }

            // Store new image to S3
            $path = $this->profile_image->store('profiles', 's3');
            $user->profile_image_url = $path;
            $this->current_profile_image_url = $path;
        }

        try {
            $user->save();

            $this->dispatch('profile-updated');

            // Use component property instead of session flash for immediate display
            $this->successMessage = 'Profile updated successfully!';
        } catch (\Exception $e) {
            $this->addError('save', 'Failed to save profile: '.$e->getMessage());
            \Log::error('Profile save failed', ['error' => $e->getMessage(), 'user_id' => $userId]);
        }
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
            Storage::disk('s3')->delete($currentProfileImage);
            \App\Models\User::where('id', $userId)->update(['profile_image_url' => null]);
            $this->current_profile_image_url = null;
        }
    }

    // Delete Account Properties
    public bool $showDeleteConfirmation = false;

    // Note: deletePassword validation is handled in deleteAccount() method, not via attribute
    // Using attribute would cause it to be validated on every save() call
    public string $deletePassword = '';

    public function confirmDeleteAccount()
    {
        $this->showDeleteConfirmation = true;
        $this->deletePassword = '';
    }

    public function cancelDeleteAccount()
    {
        $this->showDeleteConfirmation = false;
        $this->deletePassword = '';
        $this->resetErrorBag('deletePassword');
    }

    public function deleteAccount()
    {
        $this->validate([
            'deletePassword' => 'required|string',
        ]);

        $user = Auth::user();

        // Verify password
        if (! password_verify($this->deletePassword, $user->password)) {
            $this->addError('deletePassword', 'The password you entered is incorrect.');

            return;
        }

        // Delete profile image from S3 if exists
        if ($user->profile_image_url) {
            Storage::disk('s3')->delete($user->profile_image_url);
        }

        // Log out the user
        Auth::logout();

        // Delete the user account
        $user->delete();

        // Invalidate session
        session()->invalidate();
        session()->regenerateToken();

        // Redirect to homepage with message
        return redirect('/')->with('message', 'Your account has been permanently deleted.');
    }

    public function render()
    {
        return view('livewire.profile.edit-profile')
            ->layout('layouts.app', [
                'title' => 'Edit Profile',
            ]);
    }
}
