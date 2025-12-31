<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoView extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
            'watch_duration_seconds' => 'integer',
            'completed' => 'boolean',
        ];
    }

    /**
     * Get the video that was viewed.
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get the user who viewed (optional).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hash an IP address for privacy.
     */
    public static function hashIp(string $ip): string
    {
        return hash('sha256', $ip . config('app.key'));
    }

    /**
     * Scope for views by a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for views in a date range.
     */
    public function scopeInDateRange($query, $start, $end)
    {
        return $query->whereBetween('viewed_at', [$start, $end]);
    }

    /**
     * Scope for completed views only.
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }
}
