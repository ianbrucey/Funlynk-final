<?php

namespace App\Filament\Resources\SocialAccounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SocialAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'display_name')
                    ->searchable()
                    ->required(),
                TextInput::make('provider')
                    ->required()
                    ->maxLength(50),
                TextInput::make('provider_id')
                    ->required()
                    ->maxLength(255),
                TextInput::make('provider_email')
                    ->email()
                    ->label('Provider email'),
                TextInput::make('name'),
                TextInput::make('nickname'),
                TextInput::make('avatar_url')
                    ->url()
                    ->label('Avatar URL'),
                TextInput::make('token')
                    ->password()
                    ->dehydrateStateUsing(fn (?string $state) => $state),
                TextInput::make('refresh_token')
                    ->password(),
                DateTimePicker::make('token_expires_at'),
                Textarea::make('meta')
                    ->label('Raw payload')
                    ->rows(4)
                    ->json(),
            ]);
    }
}
