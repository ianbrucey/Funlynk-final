<?php

namespace App\Filament\Resources\PostReactions\Pages;

use App\Filament\Resources\PostReactions\PostReactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPostReaction extends EditRecord
{
    protected static string $resource = PostReactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
