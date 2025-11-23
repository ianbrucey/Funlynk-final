<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
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
                // Basic Information Section
                Section::make('Basic Information')
                    ->schema([
                        Select::make('host_id')
                            ->label('Host')
                            ->relationship('host', 'name')
                            ->searchable()
                            ->required()
                            ->default(fn () => auth()->id()),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Pickup Basketball Game'),

                        Select::make('activity_type')
                            ->label('Activity Type')
                            ->options([
                                'sports' => 'Sports',
                                'music' => 'Music',
                                'food' => 'Food & Drink',
                                'social' => 'Social',
                                'outdoor' => 'Outdoor',
                                'arts' => 'Arts & Culture',
                                'wellness' => 'Wellness',
                                'tech' => 'Technology',
                                'education' => 'Education',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('social'),

                        Textarea::make('description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('Describe your activity...'),

                        FileUpload::make('images')
                            ->label('Activity Images')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->directory('activities')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Location Section
                Section::make('Location')
                    ->schema([
                        TextInput::make('location_name')
                            ->required()
                            ->placeholder('e.g., Central Park Basketball Courts'),

                        TextInput::make('location_coordinates')
                            ->label('Coordinates (Latitude, Longitude)')
                            ->placeholder('40.7829, -73.9654')
                            ->helperText('Format: latitude, longitude (e.g., 40.7829, -73.9654)')
                            ->required(),
                    ])
                    ->columns(2),

                // Date & Time Section
                Section::make('Date & Time')
                    ->schema([
                        DateTimePicker::make('start_time')
                            ->label('Start Time')
                            ->required()
                            ->native(false)
                            ->minDate(now())
                            ->default(now()->addDay()),

                        DateTimePicker::make('end_time')
                            ->label('End Time')
                            ->native(false)
                            ->after('start_time'),
                    ])
                    ->columns(2),

                // Capacity & Attendance Section
                Section::make('Capacity & Attendance')
                    ->schema([
                        TextInput::make('max_attendees')
                            ->label('Maximum Attendees')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Leave empty for unlimited')
                            ->helperText('Maximum number of people who can attend'),

                        TextInput::make('current_attendees')
                            ->label('Current Attendees')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(true),
                    ])
                    ->columns(2),

                // Pricing Section
                Section::make('Pricing')
                    ->schema([
                        Toggle::make('is_paid')
                            ->label('Paid Activity')
                            ->default(false)
                            ->live(),

                        TextInput::make('price_cents')
                            ->label('Price (in cents)')
                            ->numeric()
                            ->prefix('$')
                            ->helperText('Enter amount in cents (e.g., 1500 for $15.00)')
                            ->visible(fn ($get) => $get('is_paid'))
                            ->required(fn ($get) => $get('is_paid')),

                        TextInput::make('currency')
                            ->default('USD')
                            ->disabled()
                            ->visible(fn ($get) => $get('is_paid')),

                        TextInput::make('stripe_price_id')
                            ->label('Stripe Price ID')
                            ->placeholder('price_xxxxx')
                            ->visible(fn ($get) => $get('is_paid')),
                    ])
                    ->columns(2),

                // Settings Section
                Section::make('Settings')
                    ->schema([
                        Toggle::make('is_public')
                            ->label('Public Activity')
                            ->default(true)
                            ->helperText('Public activities appear in discovery feeds'),

                        Toggle::make('requires_approval')
                            ->label('Require Approval')
                            ->default(false)
                            ->helperText('Host must approve RSVPs'),

                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required(),
                    ])
                    ->columns(3),

                // Tags Section
                Section::make('Tags')
                    ->schema([
                        Select::make('tags')
                            ->label('Activity Tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(50),
                                Select::make('category')
                                    ->options([
                                        'sports' => 'Sports',
                                        'music' => 'Music',
                                        'food' => 'Food & Drink',
                                        'social' => 'Social',
                                        'outdoor' => 'Outdoor',
                                        'arts' => 'Arts & Culture',
                                        'wellness' => 'Wellness',
                                        'tech' => 'Technology',
                                        'education' => 'Education',
                                        'other' => 'Other',
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                // Post Origin Section (for converted activities)
                Section::make('Post Origin')
                    ->schema([
                        Select::make('originated_from_post_id')
                            ->label('Originated from Post')
                            ->relationship('postOrigin', 'title')
                            ->searchable()
                            ->disabled()
                            ->dehydrated(true),

                        DateTimePicker::make('conversion_date')
                            ->label('Conversion Date')
                            ->disabled()
                            ->dehydrated(true),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->originated_from_post_id !== null),
            ]);
    }
}
