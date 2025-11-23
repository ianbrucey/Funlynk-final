<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_image_url')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF'),
                TextColumn::make('display_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => '@'.$record->username),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location_name')
                    ->label('Location')
                    ->searchable()
                    ->icon('heroicon-o-map-pin')
                    ->placeholder('Not set'),
                TextColumn::make('interests')
                    ->label('Interests')
                    ->badge()
                    ->separator(',')
                    ->limit(3)
                    ->placeholder('None')
                    ->toggleable(),
                IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_host')
                    ->label('Host')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('follower_count')
                    ->label('Followers')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('activity_count')
                    ->label('Activities')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_verified')
                    ->label('Verified Users')
                    ->placeholder('All users')
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only'),
                TernaryFilter::make('is_host')
                    ->label('Hosts')
                    ->placeholder('All users')
                    ->trueLabel('Hosts only')
                    ->falseLabel('Non-hosts only'),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All users')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                SelectFilter::make('privacy_level')
                    ->label('Privacy Level')
                    ->options([
                        'public' => 'Public',
                        'friends' => 'Friends Only',
                        'private' => 'Private',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
