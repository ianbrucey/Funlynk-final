<?php

namespace App\Livewire\Auth;

use App\Models\Group;
use App\Models\User;
use App\Services\ContextPreservationService;
use App\Services\GuestEngagementService;
use App\Services\GroupService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use MatanYadaev\EloquentSpatial\Objects\Point;

class GroupAuthModal extends Component
{
    use WithFileUploads;

    public ?Group $group = null;
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

    #[On('openGroupAuthModal')]
    public function openModal(?string $groupId = null): void
    {
        if ($groupId) {
            $this->group = Group::find($groupId);
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
        GroupService $groupService
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
            // Generate username from email + timestamp
            $emailPrefix = Str::before($this->email, '@');
            $timestamp = now()->format('ymd');
            $username = Str::slug($emailPrefix) . "_" . $timestamp;
            $username = Str::lower($username);

            // Ensure username is unique
            $counter = 1;
            $originalUsername = $username;
            while (User::where('username', $username)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;
            }

            // Handle profile photo upload to S3
            $profileImageUrl = null;
            if ($this->profilePhoto) {
                $profileImageUrl = $this->profilePhoto->store('profile-images', 's3');
            }

            // Get location from group if available
            $locationCoordinates = null;
            $locationName = null;
            if ($this->group && $this->group->location_coordinates) {
                $locationCoordinates = new Point(
                    $this->group->location_coordinates->latitude,
                    $this->group->location_coordinates->longitude
                );
                $locationName = $this->group->location_name;
            }

            // Create user with smart defaults
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

            // Complete the flow (join group)
            $this->completeFlow($contextService, $groupService, $user);

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
        GroupService $groupService
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
        $this->completeFlow($contextService, $groupService, $user);
    }

    /**
     * Complete the auth flow - auto-join public groups or create join request for private
     */
    protected function completeFlow(
        ContextPreservationService $contextService,
        GroupService $groupService,
        User $user
    ): mixed {
        if (!$this->group) {
            $this->redirect(route('feed.nearby'), navigate: true);
            return null;
        }

        // Check if already a member
        if ($user->isMemberOf($this->group)) {
            session()->flash('info', 'You\'re already a member of this group!');
            $this->show = false;
            $this->redirect(route('groups.show', $this->group), navigate: true);
            return null;
        }

        try {
            if ($this->group->privacy === 'public') {
                // Public group - auto-join
                $groupService->addMember($this->group, $user);
                session()->flash('success', 'Welcome to ' . $this->group->name . '!');
                $this->show = false;
                $this->redirect(route('groups.show', $this->group), navigate: true);
            } else {
                // Private group - create join request
                $groupService->createJoinRequest($this->group, $user);
                session()->flash('success', 'Join request sent! You\'ll be notified when approved.');
                $this->show = false;
                $this->redirect(route('groups.public', $this->group), navigate: true);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Could not join group. Please try again.');
            $this->redirect(route('groups.public', $this->group), navigate: true);
        }

        $contextService->clearIntendedAction();
        return null;
    }

    public function render()
    {
        return view('livewire.auth.group-auth-modal');
    }
}
