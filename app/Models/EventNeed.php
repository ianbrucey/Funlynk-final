<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventNeed extends Model
{
    use HasUuids;

    public const MAX_PER_EVENT = 30;

    protected $guarded = [];

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * @return HasMany<EventNeedClaim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(EventNeedClaim::class);
    }

    public function claimedQuantity(): int
    {
        return (int) $this->claims->sum('quantity');
    }

    /**
     * Derived status: open, partial, covered or brought.
     */
    public function status(): string
    {
        $claimed = $this->claimedQuantity();
        $brought = (int) $this->claims->whereNotNull('brought_at')->sum('quantity');

        return match (true) {
            $brought >= $this->quantity && $this->quantity > 0 => 'brought',
            $claimed >= $this->quantity => 'covered',
            $claimed > 0 => 'partial',
            default => 'open',
        };
    }
}
