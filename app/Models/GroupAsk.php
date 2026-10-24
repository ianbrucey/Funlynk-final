<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A group's request for money, with an append-only ledger (spec 015).
 * FunLynk never holds the money; admins record what happened outside the app.
 */
class GroupAsk extends Model
{
    use HasUuids;

    public const STATUS_PROPOSED = 'proposed';

    public const STATUS_OPEN = 'open';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_DECLINED = 'declined';

    public const MAX_OPEN_PER_GROUP = 20;

    public const ALLOWED_PAYMENT_HOSTS = [
        'venmo.com', 'cash.app', 'paypal.com', 'paypal.me', 'ko-fi.com',
        'buymeacoffee.com', 'gofundme.com', 'zeffy.com',
    ];

    protected $fillable = [
        'group_id', 'created_by', 'title', 'why', 'target_cents', 'deadline', 'payment_url', 'status',
        'decided_by', 'decided_at', 'decline_reason', 'closed_by', 'closed_at', 'closing_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_cents' => 'integer',
            'deadline' => 'date',
            'decided_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<GroupAskEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(GroupAskEntry::class);
    }

    /**
     * @return HasMany<GroupAskChip, $this>
     */
    public function chips(): HasMany
    {
        return $this->hasMany(GroupAskChip::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isProposed(): bool
    {
        return $this->status === self::STATUS_PROPOSED;
    }

    public static function isAllowedPaymentUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user'])) {
            return false;
        }

        $host = strtolower($parts['host']);

        foreach (self::ALLOWED_PAYMENT_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }
}
