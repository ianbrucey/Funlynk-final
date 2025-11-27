<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ConversationParticipant extends Pivot
{
    use HasUuids;

    protected $table = 'conversation_participants';

    protected function casts(): array
    {
        return [
            'is_muted' => 'boolean',
            'last_read_at' => 'datetime',
        ];
    }
}
