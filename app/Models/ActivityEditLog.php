<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ActivityEditLog extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'triggered_refund_window' => 'boolean',
            'paid_attendee_count' => 'integer',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function refundWindow(): HasOne
    {
        return $this->hasOne(ActivityRefundWindow::class, 'trigger_edit_log_id');
    }

    // Helpers
    public function isCosmetic(): bool
    {
        return $this->change_category === 'cosmetic';
    }

    public function isMinor(): bool
    {
        return $this->change_category === 'minor';
    }

    public function isSignificant(): bool
    {
        return $this->change_category === 'significant';
    }

    public function wasBlocked(): bool
    {
        return $this->change_category === 'blocked';
    }
}
