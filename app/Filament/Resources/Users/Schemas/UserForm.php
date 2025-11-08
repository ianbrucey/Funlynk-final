<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email address')
                            ->required()
                            ->email()
                            ->unique(ignoreRecord: true),
                        TextInput::make('username')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        TextInput::make('display_name')
                            ->label('Display name')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->maxLength(255),
                        Textarea::make('bio')
                            ->rows(4)
                            ->maxLength(1000),
                        TextInput::make('profile_image_url')
                            ->url()
                            ->label('Profile image URL'),
                    ])
                    ->columns(2),
                Section::make('Location & Interests')
                    ->schema([
                        TextInput::make('location_name')
                            ->label('Location name')
                            ->maxLength(255),
                        Fieldset::make('Coordinates')
                            ->schema([
                                TextInput::make('location_coordinates.lat')
                                    ->label('Latitude')
                                    ->numeric(),
                                TextInput::make('location_coordinates.lng')
                                    ->label('Longitude')
                                    ->numeric(),
                            ])
                            ->columns(2),
                        TagsInput::make('interests')
                            ->label('Interest tags'),
                    ])
                    ->columns(2),
                Section::make('Platform Status')
                    ->schema([
                        Toggle::make('is_host')
                            ->label('Host privileges'),
                        Toggle::make('is_verified')
                            ->label('Verified profile'),
                        Toggle::make('is_active')
                            ->label('Active account')
                            ->default(true),
                        Toggle::make('stripe_onboarding_complete')
                            ->label('Stripe onboarding complete'),
                        TextInput::make('stripe_account_id')
                            ->maxLength(255),
                        Select::make('privacy_level')
                            ->options([
                                'public' => 'Public',
                                'friends' => 'Friends only',
                                'private' => 'Private',
                            ])
                            ->required(),
                        TextInput::make('follower_count')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        TextInput::make('following_count')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        TextInput::make('activity_count')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(3),
            ]);
    }
}
