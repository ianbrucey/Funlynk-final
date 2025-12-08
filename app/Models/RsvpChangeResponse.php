<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RsvpChangeResponse extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public const RESPONSE_ACCEPTED = 'accepted';
    public const RESPONSE_REFUNDED = 'refunded';

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function rsvp(): BelongsTo
    {
        return $this->belongsTo(Rsvp::class);
    }

    public function refundWindow(): BelongsTo
    {
        return $this->belongsTo(ActivityRefundWindow::class, 'refund_window_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->response === null;
    }

    public function isAccepted(): bool
    {
        return $this->response === self::RESPONSE_ACCEPTED;
    }

    public function isRefunded(): bool
    {
        return $this->response === self::RESPONSE_REFUNDED;
    }

    public function markAsAccepted(): void
    {
        $this->update([
            'response' => self::RESPONSE_ACCEPTED,
            'responded_at' => now(),
        ]);
    }

    public function markAsRefunded(?string $transactionId = null): void
    {
        $this->update([
            'response' => self::RESPONSE_REFUNDED,
            'responded_at' => now(),
            'transaction_id' => $transactionId,
        ]);
    }
}
