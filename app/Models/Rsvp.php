<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rsvp extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'attended' => 'boolean',
            'payment_amount' => 'integer',
            'checked_in_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    public function changeResponses(): HasMany
    {
        return $this->hasMany(RsvpChangeResponse::class);
    }

    /**
     * Get pending change response for the active refund window
     */
    public function pendingChangeResponse()
    {
        return $this->hasOne(RsvpChangeResponse::class)
            ->whereNull('response')
            ->whereHas('refundWindow', function ($query) {
                $query->where('status', ActivityRefundWindow::STATUS_ACTIVE)
                    ->where('expires_at', '>', now());
            });
    }

    /**
     * Check if this RSVP has a pending change response
     */
    public function hasPendingChangeResponse(): bool
    {
        return $this->pendingChangeResponse()->exists();
    }
}
