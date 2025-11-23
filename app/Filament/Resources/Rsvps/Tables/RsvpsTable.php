<?php

namespace App\Filament\Resources\Rsvps\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RsvpsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.display_name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('activity.title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'attending' => 'success',
                        'maybe' => 'warning',
                        'declined' => 'danger',
                        'waitlist' => 'gray',
                    }),
                IconColumn::make('attended')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_paid')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('payment_amount')
                    ->money('USD', divideBy: 100)
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'attending' => 'Attending',
                        'maybe' => 'Maybe',
                        'declined' => 'Declined',
                        'waitlist' => 'Waitlist',
                    ]),
                \Filament\Tables\Filters\TernaryFilter::make('attended'),
                \Filament\Tables\Filters\TernaryFilter::make('is_paid'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
