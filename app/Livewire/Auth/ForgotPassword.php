<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $email = '';

    public bool $emailSent = false;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function sendResetLink(): void
    {
        $this->validate();

        // Throttle: 3 attempts per minute per email
        $key = 'password-reset:'.$this->email;
        $maxAttempts = 3;
        $decayMinutes = 1;

        if (cache()->get($key, 0) >= $maxAttempts) {
            $this->addError('email', __('Too many password reset attempts. Please try again in a minute.'));

            return;
        }

        cache()->put($key, cache()->get($key, 0) + 1, now()->addMinutes($decayMinutes));

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->emailSent = true;
            session()->flash('status', __($status));
        } else {
            // Don't reveal if email exists or not for security
            // Always show success message
            $this->emailSent = true;
            session()->flash('status', __('If an account exists with this email, you will receive a password reset link.'));
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('layouts.auth', [
                'title' => __('Forgot Password'),
            ]);
    }
}
