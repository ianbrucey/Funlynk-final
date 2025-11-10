<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('host_id')
                    ->relationship('host', 'id')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('activity_type')
                    ->required(),
                TextInput::make('location_name')
                    ->required(),
                DateTimePicker::make('start_time')
                    ->required(),
                DateTimePicker::make('end_time'),
                TextInput::make('max_attendees')
                    ->numeric(),
                TextInput::make('current_attendees')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_paid')
                    ->required(),
                TextInput::make('price_cents')
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('USD'),
                TextInput::make('stripe_price_id'),
                Toggle::make('is_public')
                    ->required(),
                Toggle::make('requires_approval')
                    ->required(),
                Textarea::make('tags')
                    ->columnSpanFull(),
                Textarea::make('images')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('originated_from_post_id'),
                DateTimePicker::make('conversion_date'),
                TextInput::make('location_coordinates')
                    ->required(),
            ]);
    }
}
