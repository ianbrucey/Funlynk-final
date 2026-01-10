<?php

namespace App\Livewire\Auth;

use App\Models\Activity;
use App\Models\User;
use App\Services\ContextPreservationService;
use App\Services\GuestEngagementService;
use App\Services\RsvpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use MatanYadaev\EloquentSpatial\Objects\Point;

class EventAuthModal extends Component
{
    use WithFileUploads;

    public ?Activity $activity = null;
    public bool $show = false;
    public string $mode = 'quick-join'; // 'quick-join' or 'sign-in'

    // Form fields
    public string $email = '';
    public string $password = '';
    public $profilePhoto = null;

    public bool $processing = false;
    public ?string $errorMessage = null;

    protected function rules()
    {
        if ($this->mode === 'quick-join') {
            return [
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'min:8'],
                'profilePhoto' => ['nullable', 'image', 'max:2048'],
            ];
        }

        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    #[On('openEventAuthModal')]
    public function openModal(?string $activityId = null): void
    {
        if ($activityId) {
            $this->activity = Activity::find($activityId);
        }
        $this->show = true;
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->email = '';
        $this->password = '';
        $this->profilePhoto = null;
        $this->errorMessage = null;
        $this->processing = false;
    }

    public function switchMode(string $mode): void
    {
        $this->mode = $mode;
        $this->errorMessage = null;
    }

    /**
     * Quick Join - Email-only registration
     */
    public function quickJoin(
        ContextPreservationService $contextService,
        GuestEngagementService $guestService,
        RsvpService $rsvpService
    ): void {
        $this->processing = true;
        $this->errorMessage = null;

        $this->validate();

        // Check if email already exists
        if (User::where('email', $this->email)->exists()) {
            $this->errorMessage = 'This email is already registered. Please sign in instead.';
            $this->mode = 'sign-in';
            $this->processing = false;
            return;
        }

        try {
            // Generate username from email
            $emailPrefix = Str::before($this->email, '@');
            $username = Str::slug($emailPrefix) . '-' . Str::random(4);
            $username = Str::lower($username);

            // Ensure username is unique
            while (User::where('username', $username)->exists()) {
                $username = Str::slug($emailPrefix) . '-' . Str::random(4);
            }

            // Handle profile photo upload to S3
            $profileImageUrl = null;
            if ($this->profilePhoto) {
                $profileImageUrl = $this->profilePhoto->store('profile-images', 's3');
            }

            // Get location from activity if available - create a new Point object
            $locationCoordinates = null;
            $locationName = null;
            if ($this->activity && $this->activity->location_coordinates) {
                $locationCoordinates = new Point(
                    $this->activity->location_coordinates->latitude,
                    $this->activity->location_coordinates->longitude
                );
                $locationName = $this->activity->location_name;
            }

            // Create user with smart defaults
            // Mark onboarding as complete since we're setting location from the event
            $user = User::create([
                'email' => $this->email,
                'username' => $username,
                'display_name' => $emailPrefix,
                'password' => Hash::make($this->password),
                'needs_password_setup' => false,
                'profile_image_url' => $profileImageUrl,
                'location_coordinates' => $locationCoordinates,
                'location_name' => $locationName,
                'privacy_level' => 'public',
                'is_host' => false,
                'email_verified_at' => null,
                'onboarding_completed_at' => $locationCoordinates ? now() : null,
            ]);

            // Log in the user
            Auth::login($user);
            request()->session()->regenerate();

            // Migrate guest data
            $guestToken = Cookie::get('guest_token');
            if ($guestToken) {
                $guestService->migrateGuestData($user, $user->email, $guestToken);
            }

            // Complete the flow (RSVP or redirect to checkout)
            $this->completeFlow($contextService, $rsvpService, $user);

        } catch (\Exception $e) {
            $this->errorMessage = 'Something went wrong. Please try again.';
            $this->processing = false;
            report($e);
        }
    }

    /**
     * Sign In - Existing user login
     */
    public function signIn(
        ContextPreservationService $contextService,
        GuestEngagementService $guestService,
        RsvpService $rsvpService
    ): void {
        $this->processing = true;
        $this->errorMessage = null;

        $this->validate();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->errorMessage = 'Invalid email or password.';
            $this->processing = false;
            return;
        }

        request()->session()->regenerate();

        $user = Auth::user();

        // Migrate guest data
        $guestToken = Cookie::get('guest_token');
        if ($guestToken) {
            $guestService->migrateGuestData($user, $user->email, $guestToken);
        }

        // Complete the flow
        $this->completeFlow($contextService, $rsvpService, $user);
    }

    /**
     * Complete the auth flow - auto-RSVP for free events or redirect to checkout
     */
    protected function completeFlow(
        ContextPreservationService $contextService,
        RsvpService $rsvpService,
        User $user
    ): void {
        if (!$this->activity) {
            $this->redirect(route('feed.nearby'), navigate: true);
            return;
        }

        // Check if event is free or paid
        if (!$this->activity->is_paid) {
            // Free event - auto-RSVP
            try {
                $rsvpService->createRsvp($this->activity, $user, ['status' => 'attending']);
                session()->flash('success', 'You\'re all set! You\'ve successfully joined this event.');
                $this->show = false;
                $this->redirect(route('events.show', $this->activity), navigate: true);
            } catch (\Exception $e) {
                session()->flash('error', 'Could not complete RSVP. Please try again.');
                $this->redirect(route('events.show', $this->activity), navigate: true);
            }
        } else {
            // Paid event - redirect to checkout
            $contextService->clearIntendedAction();
            $this->redirect(route('events.checkout', $this->activity), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.auth.event-auth-modal');
    }
}
