<?php

namespace App\Filament\Resources\Rsvps\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RsvpForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                Select::make('activity_id')
                    ->relationship('activity', 'title')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('attending'),
                Toggle::make('is_paid')
                    ->required(),
                TextInput::make('payment_intent_id'),
                TextInput::make('payment_status'),
            ]);
    }
}
