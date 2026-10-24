<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventNeedClaim extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reminded_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'brought_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EventNeed, $this>
     */
    public function need(): BelongsTo
    {
        return $this->belongsTo(EventNeed::class, 'event_need_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
