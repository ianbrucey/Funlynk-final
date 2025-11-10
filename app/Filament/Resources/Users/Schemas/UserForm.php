<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('username')
                    ->required(),
                TextInput::make('display_name')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                Textarea::make('bio')
                    ->columnSpanFull(),
                Textarea::make('profile_image_url')
                    ->columnSpanFull(),
                TextInput::make('location_name'),
                Textarea::make('interests')
                    ->columnSpanFull(),
                Toggle::make('is_host')
                    ->required(),
                TextInput::make('stripe_account_id'),
                Toggle::make('stripe_onboarding_complete')
                    ->required(),
                TextInput::make('follower_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('following_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('activity_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_verified')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('privacy_level')
                    ->required()
                    ->default('public'),
                TextInput::make('location_coordinates'),
            ]);
    }
}
