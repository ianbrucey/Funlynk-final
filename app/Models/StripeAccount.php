<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeAccount extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'onboarding_complete' => 'boolean',
            'charges_enabled' => 'boolean',
            'payouts_enabled' => 'boolean',
            'requirements' => 'array',
            'onboarded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOnboarded(): bool
    {
        return $this->onboarding_complete && $this->charges_enabled;
    }

    public function canAcceptPayments(): bool
    {
        return $this->charges_enabled && $this->payouts_enabled;
    }
}
