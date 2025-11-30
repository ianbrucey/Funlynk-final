<?php

namespace App\Livewire\Settings;

use Livewire\Attributes\Validate;
use Livewire\Component;

class NotificationPreferences extends Component
{
    #[Validate('required|in:all,in_app_only,email_only,none')]
    public string $notification_preference = 'all';

    #[Validate('boolean')]
    public bool $email_on_post_converted = true;

    #[Validate('boolean')]
    public bool $email_on_event_invitation = true;

    #[Validate('boolean')]
    public bool $email_on_rsvp_update = true;

    #[Validate('boolean')]
    public bool $email_on_comment = true;

    #[Validate('boolean')]
    public bool $email_on_reaction = false;

    public function mount(): void
    {
        $user = auth()->user();
        $this->notification_preference = $user->notification_preference ?? 'all';
        $this->email_on_post_converted = $user->email_on_post_converted ?? true;
        $this->email_on_event_invitation = $user->email_on_event_invitation ?? true;
        $this->email_on_rsvp_update = $user->email_on_rsvp_update ?? true;
        $this->email_on_comment = $user->email_on_comment ?? true;
        $this->email_on_reaction = $user->email_on_reaction ?? false;
    }

    public function save(): void
    {
        $this->validate();

        auth()->user()->update([
            'notification_preference' => $this->notification_preference,
            'email_on_post_converted' => $this->email_on_post_converted,
            'email_on_event_invitation' => $this->email_on_event_invitation,
            'email_on_rsvp_update' => $this->email_on_rsvp_update,
            'email_on_comment' => $this->email_on_comment,
            'email_on_reaction' => $this->email_on_reaction,
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => '✅ Notification preferences saved!',
        ]);
    }

    public function render()
    {
        return view('livewire.settings.notification-preferences');
    }
}
