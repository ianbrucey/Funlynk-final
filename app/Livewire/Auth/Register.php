<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Register extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'privacy_level' => 'public',
            'is_host' => false,
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
                    ->unique(User::class, 'email'),
                TextInput::make('username')
                    ->label('Username')
                    ->required()
                    ->maxLength(50)
                    ->rules(['alpha_dash'])
                    ->unique(User::class, 'username'),
                TextInput::make('display_name')
                    ->label('Display name')
                    ->required()
                    ->maxLength(100),
                TextInput::make('password')
                    ->password()
                    ->required()
                    ->revealable()
                    ->rules([Password::defaults()])
                    ->same('password_confirmation'),
                TextInput::make('password_confirmation')
                    ->password()
                    ->revealable()
                    ->required()
                    ->label('Confirm password'),
                TextInput::make('location_name')
                    ->label('Location')
                    ->maxLength(255),
                TagsInput::make('interests')
                    ->suggestions([
                        'sports',
                        'music',
                        'outdoors',
                        'gaming',
                        'travel',
                        'food',
                    ])
                    ->label('Interests'),
                Textarea::make('bio')
                    ->rows(4)
                    ->maxLength(500),
                Toggle::make('is_host')
                    ->label('I want to host paid activities'),
                Select::make('privacy_level')
                    ->label('Profile privacy')
                    ->options([
                        'public' => 'Public',
                        'friends' => 'Friends only',
                        'private' => 'Private',
                    ])
                    ->required(),
            ])
            ->statePath('data');
    }

    public function register(): void
    {
        $data = $this->form->getState();

        $payload = Arr::except($data, ['password_confirmation']);

        $payload['username'] = Str::lower(Str::slug($payload['username']));
        $payload['password'] = Hash::make($payload['password']);
        $payload['email_verified_at'] = null;

        $user = User::create($payload);

        Auth::login($user);
        request()->session()->regenerate();

        $this->redirectIntended(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('layouts.auth', [
                'title' => __('Create account'),
            ]);
    }
}
