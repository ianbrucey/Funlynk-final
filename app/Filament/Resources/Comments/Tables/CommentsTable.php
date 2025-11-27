<?php

namespace App\Filament\Resources\Comments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.username')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('commentable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'App\\Models\\Post' => 'warning',
                        'App\\Models\\Activity' => 'success',
                        default => 'gray',
                    }),
                
                TextColumn::make('content')
                    ->label('Content')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),
                
                TextColumn::make('depth')
                    ->label('Depth')
                    ->badge()
                    ->color(fn ($state) => $state > 5 ? 'danger' : 'info'),
                
                TextColumn::make('replies_count')
                    ->label('Replies')
                    ->counts('replies')
                    ->sortable(),
                
                TextColumn::make('reactions_count')
                    ->label('Reactions')
                    ->counts('reactions')
                    ->sortable(),
                
                IconColumn::make('is_edited')
                    ->label('Edited')
                    ->boolean(),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('commentable_type')
                    ->label('Type')
                    ->options([
                        'App\\Models\\Post' => 'Posts',
                        'App\\Models\\Activity' => 'Activities',
                    ]),
                
                SelectFilter::make('depth')
                    ->label('Thread Depth')
                    ->options([
                        '0' => 'Top-level (0)',
                        '1' => 'First reply (1)',
                        '2' => 'Second reply (2)',
                        '3' => 'Third reply (3)',
                        '4+' => 'Deep thread (4+)',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === '4+') {
                            return $query->where('depth', '>=', 4);
                        }
                        return $query->where('depth', $state['value']);
                    }),
                
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
