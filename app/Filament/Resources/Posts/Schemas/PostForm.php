<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('tags')
                    ->columnSpanFull(),
                TextInput::make('location_name'),
                TextInput::make('geo_hash'),
                DateTimePicker::make('approximate_time'),
                DateTimePicker::make('expires_at')
                    ->required(),
                TextInput::make('mood'),
                TextInput::make('evolved_to_event_id'),
                DateTimePicker::make('conversion_triggered_at'),
                TextInput::make('view_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reaction_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('location_coordinates'),
            ]);
    }
}
