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
                    ->relationship('user', 'display_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('activity_id')
                    ->relationship('activity', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('status')
                    ->options([
                        'attending' => 'Attending',
                        'maybe' => 'Maybe',
                        'declined' => 'Declined',
                        'waitlist' => 'Waitlist',
                    ])
                    ->required()
                    ->default('attending'),
                Toggle::make('is_paid')
                    ->label('Paid RSVP')
                    ->reactive(),
                TextInput::make('payment_amount')
                    ->numeric()
                    ->prefix('$')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 2) : null)
                    ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : null)
                    ->visible(fn ($get) => $get('is_paid')),
                TextInput::make('payment_intent_id')
                    ->visible(fn ($get) => $get('is_paid')),
                Select::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'succeeded' => 'Succeeded',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->visible(fn ($get) => $get('is_paid')),
                Toggle::make('attended')
                    ->label('Checked In'),
            ]);
    }
}
