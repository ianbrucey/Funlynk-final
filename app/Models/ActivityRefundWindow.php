<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityRefundWindow extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const WINDOW_HOURS = 72;

    protected function casts(): array
    {
        return [
            'triggered_at' => 'datetime',
            'expires_at' => 'datetime',
            'changes_summary' => 'array',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function triggerEditLog(): BelongsTo
    {
        return $this->belongsTo(ActivityEditLog::class, 'trigger_edit_log_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(RsvpChangeResponse::class, 'refund_window_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->where('expires_at', '<=', now());
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->expires_at > now();
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->status === self::STATUS_ACTIVE && $this->expires_at <= now());
    }

    public function timeRemaining(): ?string
    {
        if (!$this->isActive()) {
            return null;
        }
        return $this->expires_at->diffForHumans(['parts' => 2]);
    }

    public function hoursRemaining(): float
    {
        if (!$this->isActive()) {
            return 0;
        }
        return max(0, now()->diffInHours($this->expires_at, false));
    }
}
