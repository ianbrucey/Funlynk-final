<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialShare extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'clicks' => 'integer',
            'conversions' => 'integer',
        ];
    }

    /**
     * Get the activity this share is for
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the user who shared (if authenticated)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Increment click count
     */
    public function incrementClicks(): void
    {
        $this->increment('clicks');
    }

    /**
     * Increment conversion count
     */
    public function incrementConversions(): void
    {
        $this->increment('conversions');
    }

    /**
     * Get conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->clicks === 0) {
            return 0;
        }

        return ($this->conversions / $this->clicks) * 100;
    }

    /**
     * Scope to get shares by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to get shares with conversions
     */
    public function scopeWithConversions($query)
    {
        return $query->where('conversions', '>', 0);
    }
}
