<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'platform_fee' => 'integer',
            'host_earnings' => 'integer',
            'refunded_amount' => 'integer',
            'succeeded_at' => 'datetime',
            'refunded_at' => 'datetime',
            'metadata' => 'array',
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

    public function rsvp(): BelongsTo
    {
        return $this->belongsTo(Rsvp::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }
}
