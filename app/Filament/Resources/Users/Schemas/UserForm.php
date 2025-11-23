<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Information')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('username')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->alphaDash()
                            ->helperText('Letters, numbers, dashes and underscores only'),
                        TextInput::make('display_name')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('password')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At'),
                    ])
                    ->columns(2),

                Section::make('Profile Information')
                    ->schema([
                        FileUpload::make('profile_image_url')
                            ->label('Profile Image')
                            ->image()
                            ->disk('public')
                            ->directory('profiles')
                            ->maxSize(2048)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200')
                            ->helperText('Max 2MB. Will be resized to 200x200px.')
                            ->columnSpanFull(),
                        Textarea::make('bio')
                            ->label('Biography')
                            ->rows(4)
                            ->maxLength(500)
                            ->helperText('Tell others about yourself (max 500 characters)')
                            ->columnSpanFull(),
                        TagsInput::make('interests')
                            ->label('Interests')
                            ->placeholder('Add interests (e.g., hiking, photography, cooking)')
                            ->helperText('Add at least 3 interests to help others discover you')
                            ->columnSpanFull(),
                        TextInput::make('location_name')
                            ->label('Location')
                            ->maxLength(255)
                            ->helperText('City or area (e.g., "San Francisco, CA")'),
                        TextInput::make('location_coordinates')
                            ->label('Coordinates (Lat, Lng)')
                            ->helperText('Auto-filled when location is set')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make('Host & Payment Settings')
                    ->schema([
                        Toggle::make('is_host')
                            ->label('Can Host Paid Activities')
                            ->helperText('Enable to allow this user to create paid events'),
                        TextInput::make('stripe_account_id')
                            ->label('Stripe Account ID')
                            ->disabled()
                            ->helperText('Managed by Stripe Connect'),
                        Toggle::make('stripe_onboarding_complete')
                            ->label('Stripe Onboarding Complete')
                            ->disabled()
                            ->helperText('Automatically updated by Stripe'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                Section::make('Statistics & Status')
                    ->schema([
                        TextInput::make('follower_count')
                            ->label('Followers')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Auto-calculated'),
                        TextInput::make('following_count')
                            ->label('Following')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Auto-calculated'),
                        TextInput::make('activity_count')
                            ->label('Activities Hosted')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Auto-calculated'),
                        Toggle::make('is_verified')
                            ->label('Verified Account')
                            ->helperText('Show verification badge'),
                        Toggle::make('is_active')
                            ->label('Account Active')
                            ->default(true)
                            ->helperText('Inactive accounts cannot log in'),
                        Select::make('privacy_level')
                            ->label('Privacy Level')
                            ->options([
                                'public' => 'Public',
                                'friends' => 'Friends Only',
                                'private' => 'Private',
                            ])
                            ->default('public')
                            ->required()
                            ->helperText('Controls profile visibility'),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }
}
