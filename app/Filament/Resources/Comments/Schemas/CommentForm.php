<?php

namespace App\Filament\Resources\Comments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('commentable_type')
                    ->label('Commentable Type')
                    ->options([
                        'App\\Models\\Post' => 'Post',
                        'App\\Models\\Activity' => 'Activity',
                    ])
                    ->required()
                    ->live(),
                
                TextInput::make('commentable_id')
                    ->label('Commentable ID')
                    ->required(),
                
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'username')
                    ->searchable()
                    ->required(),
                
                Select::make('parent_comment_id')
                    ->label('Parent Comment (Reply)')
                    ->relationship('parent', 'id')
                    ->searchable()
                    ->nullable(),
                
                TextInput::make('depth')
                    ->label('Depth')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false),
                
                Textarea::make('content')
                    ->label('Comment Content')
                    ->required()
                    ->maxLength(500)
                    ->rows(4)
                    ->columnSpanFull(),
                
                Toggle::make('is_edited')
                    ->label('Edited')
                    ->default(false)
                    ->disabled(),
                
                Toggle::make('is_deleted')
                    ->label('Deleted')
                    ->default(false)
                    ->disabled(),
            ]);
    }
}
