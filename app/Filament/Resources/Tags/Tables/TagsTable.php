<?php

namespace App\Filament\Resources\Tags\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->description),
                
                TextColumn::make('category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sports' => 'success',
                        'music' => 'warning',
                        'food' => 'danger',
                        'social' => 'info',
                        'outdoor' => 'primary',
                        'arts' => 'secondary',
                        default => 'gray',
                    }),
                
                TextColumn::make('usage_count')
                    ->numeric()
                    ->sortable()
                    ->label('Usage')
                    ->description('Times used in activities')
                    ->alignEnd(),
                
                IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
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
                    ->multiple(),
                
                TernaryFilter::make('is_featured')
                    ->label('Featured Tags')
                    ->placeholder('All tags')
                    ->trueLabel('Featured only')
                    ->falseLabel('Not featured'),
                
                SelectFilter::make('usage_count')
                    ->label('Usage Level')
                    ->options([
                        'high' => 'High (50+)',
                        'medium' => 'Medium (10-49)',
                        'low' => 'Low (1-9)',
                        'unused' => 'Unused (0)',
                    ])
                    ->query(function ($query, $state) {
                        return match ($state['value'] ?? null) {
                            'high' => $query->where('usage_count', '>=', 50),
                            'medium' => $query->whereBetween('usage_count', [10, 49]),
                            'low' => $query->whereBetween('usage_count', [1, 9]),
                            'unused' => $query->where('usage_count', 0),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('feature')
                        ->label('Mark as Featured')
                        ->icon('heroicon-o-star')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_featured' => true]))
                        ->deselectRecordsAfterCompletion(),
                    
                    BulkAction::make('unfeature')
                        ->label('Remove from Featured')
                        ->icon('heroicon-o-star')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_featured' => false]))
                        ->deselectRecordsAfterCompletion(),
                    
                    BulkAction::make('categorize')
                        ->label('Set Category')
                        ->icon('heroicon-o-tag')
                        ->form([
                            \Filament\Forms\Components\Select::make('category')
                                ->label('Category')
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
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['category' => $data['category']]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('usage_count', 'desc');
    }
}
