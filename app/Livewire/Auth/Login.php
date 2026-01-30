<?php

namespace App\Livewire\Auth;

use App\Services\ContextPreservationService;
use App\Services\GuestEngagementService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'remember' => false,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->extraAttributes(['class' => 'w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-pink-500/50 focus:outline-none transition text-white'])
                    ->extraInputAttributes(['class' => 'w-full !bg-slate-800/50 !border-white/10 !rounded-2xl focus:!border-pink-500/50 !text-white']),
                TextInput::make('password')
                    ->password()
                    ->required()
                    ->revealable()
                    ->extraAttributes(['class' => 'w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-pink-500/50 focus:outline-none transition text-white'])
                    ->extraInputAttributes(['class' => 'w-full !bg-slate-800/50 !border-white/10 !rounded-2xl focus:!border-pink-500/50 !text-white']),
                Toggle::make('remember')
                    ->label('Remember me'),
            ])
            ->statePath('data');
    }

    public function authenticate(
        ContextPreservationService $contextService,
        GuestEngagementService $guestService
    ): void {
        $data = $this->form->getState();

        if (! Auth::attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            $data['remember'] ?? false
        )) {
            throw ValidationException::withMessages([
                'data.email' => __('The provided credentials do not match our records.'),
            ]);
        }

        request()->session()->regenerate();

        $user = Auth::user();

        // Migrate guest data to user account
        $guestToken = Cookie::get('guest_token');
        if ($guestToken) {
            $guestService->migrateGuestData($user, $user->email, $guestToken);
        }

        // Check for intended action
        if ($contextService->hasIntendedAction()) {
            $action = $contextService->getIntendedAction();
            $redirectUrl = $contextService->getRedirectUrl($action);
            $contextService->clearIntendedAction();

            $this->redirect($redirectUrl, navigate: true);

            return;
        }

        $this->redirectIntended(route('feed.nearby'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.auth', [
                'title' => __('Sign in'),
            ]);
    }
}
