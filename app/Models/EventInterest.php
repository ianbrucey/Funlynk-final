<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventInterest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'converted_to_rsvp_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }

    /**
     * Get the activity this interest is for
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the user (if converted from guest)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the RSVP (if converted)
     */
    public function rsvp(): BelongsTo
    {
        return $this->belongsTo(Rsvp::class);
    }

    /**
     * Check if interest has been converted to RSVP
     */
    public function isConverted(): bool
    {
        return $this->converted_to_rsvp_at !== null;
    }

    /**
     * Check if reminder has been sent
     */
    public function hasBeenReminded(): bool
    {
        return $this->reminded_at !== null;
    }

    /**
     * Scope to get unconverted interests
     */
    public function scopeUnconverted($query)
    {
        return $query->whereNull('converted_to_rsvp_at');
    }

    /**
     * Scope to get interests that need reminders
     */
    public function scopeNeedingReminder($query)
    {
        return $query->whereNull('converted_to_rsvp_at')
            ->whereNull('reminded_at')
            ->whereHas('activity', function ($q) {
                $q->where('start_time', '>', now())
                    ->where('start_time', '<=', now()->addDay());
            });
    }
}
