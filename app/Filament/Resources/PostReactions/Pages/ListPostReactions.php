<?php

namespace App\Filament\Resources\PostReactions\Pages;

use App\Filament\Resources\PostReactions\PostReactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPostReactions extends ListRecords
{
    protected static string $resource = PostReactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
