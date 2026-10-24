<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One ledger line. Append-only: never edited or deleted, only voided (spec 015).
 */
class GroupAskEntry extends Model
{
    use HasUuids;

    public const KIND_IN = 'in';

    public const KIND_OUT = 'out';

    protected $fillable = [
        'group_ask_id', 'kind', 'amount_cents', 'counterparty', 'description', 'note',
        'item_photo_path', 'receipt_photo_path', 'recorded_by', 'voided_at', 'voided_by', 'void_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'voided_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => false);
        static::updating(function (GroupAskEntry $entry): bool {
            $allowed = ['voided_at', 'voided_by', 'void_reason', 'updated_at'];

            return $entry->getOriginal('voided_at') === null
                && array_diff(array_keys($entry->getDirty()), $allowed) === [];
        });
    }

    /**
     * @return BelongsTo<GroupAsk, $this>
     */
    public function ask(): BelongsTo
    {
        return $this->belongsTo(GroupAsk::class, 'group_ask_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
