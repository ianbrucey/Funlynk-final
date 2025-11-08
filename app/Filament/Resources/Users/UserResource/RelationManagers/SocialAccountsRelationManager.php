<?php

namespace App\Filament\Resources\Users\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SocialAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'socialAccounts';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('provider')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('provider_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('provider_email')
                    ->email()
                    ->label('Provider email'),
                Forms\Components\TextInput::make('name'),
                Forms\Components\TextInput::make('nickname'),
                Forms\Components\TextInput::make('avatar_url')
                    ->url()
                    ->label('Avatar URL'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('provider')
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->label('Provider')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('provider_email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('token_expires_at')
                    ->label('Expires')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Linked')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->recordActions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
