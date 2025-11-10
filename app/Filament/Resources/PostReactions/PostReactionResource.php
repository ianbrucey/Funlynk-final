<?php

namespace App\Filament\Resources\PostReactions;

use App\Filament\Resources\PostReactions\Pages\CreatePostReaction;
use App\Filament\Resources\PostReactions\Pages\EditPostReaction;
use App\Filament\Resources\PostReactions\Pages\ListPostReactions;
use App\Filament\Resources\PostReactions\Schemas\PostReactionForm;
use App\Filament\Resources\PostReactions\Tables\PostReactionsTable;
use App\Models\PostReaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PostReactionResource extends Resource
{
    protected static ?string $model = PostReaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PostReactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostReactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPostReactions::route('/'),
            'create' => CreatePostReaction::route('/create'),
            'edit' => EditPostReaction::route('/{record}/edit'),
        ];
    }
}
