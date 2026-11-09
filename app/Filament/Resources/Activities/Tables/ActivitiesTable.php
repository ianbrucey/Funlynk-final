<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Models\Activity;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('host.id')
                    ->searchable(),
                TextColumn::make('kind')
                    ->badge()
                    ->color(fn (string $state): string => $state === Activity::KIND_EXTERNAL ? 'warning' : 'gray'),
                TextColumn::make('source_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('activity_type')
                    ->searchable(),
                TextColumn::make('location_name')
                    ->searchable(),
                TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('max_attendees')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('current_attendees')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('payment_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'free' => 'success',
                        'online' => 'info',
                        'at_door' => 'warning',
                    }),
                TextColumn::make('price_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('stripe_price_id')
                    ->searchable(),
                IconColumn::make('is_public')
                    ->boolean(),
                IconColumn::make('requires_approval')
                    ->boolean(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('originated_from_post_id'),
                TextColumn::make('conversion_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location_coordinates'),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->options([
                        Activity::KIND_HOSTED => 'Hosted',
                        Activity::KIND_EXTERNAL => 'External listing',
                    ]),
                SelectFilter::make('status'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('viewOnSite')
                    ->label('View on site')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Activity $record): string => route('events.show', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Activity $record): bool => $record->status === 'published'),
                // Spec 019: approval is an explicit, attributable act.
                Action::make('approveListing')
                    ->label('Approve listing')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Activity $record): bool => $record->isListing()
                        && $record->status !== 'published'
                        && auth()->user()?->can('approveListing', $record) === true)
                    ->action(function (Activity $record): void {
                        $record->update([
                            'status' => 'published',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }),
                Action::make('unpublishListing')
                    ->label('Unpublish listing')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Activity $record): bool => $record->isListing()
                        && $record->status === 'published'
                        && auth()->user()?->can('unpublishListing', $record) === true)
                    ->action(function (Activity $record): void {
                        $record->update(['status' => 'draft']);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    // Spec 019 [D]: the owner approves listings by batch.
                    BulkAction::make('approveListings')
                        ->label('Approve listings')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (): bool => auth()->user()?->can('listings-admin') === true)
                        ->action(function (Collection $records): void {
                            /** @var Activity $record */
                            foreach ($records as $record) {
                                if ($record->isListing()
                                    && $record->status !== 'published'
                                    && auth()->user()?->can('approveListing', $record)) {
                                    $record->update([
                                        'status' => 'published',
                                        'approved_by' => auth()->id(),
                                        'approved_at' => now(),
                                    ]);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublishListings')
                        ->label('Unpublish listings')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => auth()->user()?->can('listings-admin') === true)
                        ->action(function (Collection $records): void {
                            /** @var Activity $record */
                            foreach ($records as $record) {
                                if ($record->isListing()
                                    && $record->status === 'published'
                                    && auth()->user()?->can('unpublishListing', $record)) {
                                    $record->update(['status' => 'draft']);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
