<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Filament\Forms\Components\TextInput;
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

    public ?bool $usernameAvailable = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function updatedDataUsername($value): void
    {
        if (strlen($value) >= 3) {
            $username = Str::lower(Str::slug($value));
            $exists = User::where('username', $username)->exists();
            $this->usernameAvailable = !$exists;
        } else {
            $this->usernameAvailable = null;
        }
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
                    ->unique(User::class, 'username')
                    ->live(debounce: 300)
                    ->helperText(fn () => match ($this->usernameAvailable) {
                        true => '✓ Username is available',
                        false => '✗ Username is already taken',
                        default => 'Choose a unique username (letters, numbers, dashes, underscores)',
                    })
                    ->suffixIcon(fn () => match ($this->usernameAvailable) {
                        true => 'heroicon-o-check-circle',
                        false => 'heroicon-o-x-circle',
                        default => null,
                    })
                    ->suffixIconColor(fn () => match ($this->usernameAvailable) {
                        true => 'success',
                        false => 'danger',
                        default => null,
                    }),
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
                    ->label('Location (optional)')
                    ->maxLength(255)
                    ->helperText('We\'ll use this to show you nearby activities'),
                TextInput::make('latitude')
                    ->hidden()
                    ->numeric(),
                TextInput::make('longitude')
                    ->hidden()
                    ->numeric(),
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

        // Set default values for fields not in the registration form
        $payload['display_name'] = $payload['username'];
        $payload['privacy_level'] = 'public';
        $payload['is_host'] = false;

        // Handle location coordinates if provided
        if (!empty($payload['latitude']) && !empty($payload['longitude'])) {
            $payload['location_coordinates'] = new \MatanYadaev\EloquentSpatial\Objects\Point(
                $payload['latitude'],
                $payload['longitude']
            );
        }

        // Remove latitude/longitude from payload as they're not direct columns
        unset($payload['latitude'], $payload['longitude']);

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
