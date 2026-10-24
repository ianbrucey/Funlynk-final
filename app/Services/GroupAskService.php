<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupAsk;
use App\Models\GroupAskChip;
use App\Models\GroupAskEntry;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Every rule for group asks and the receipts ledger (spec 015).
 * Entries are never edited or deleted; corrections are voids with a reason.
 */
class GroupAskService
{
    public const MAX_AMOUNT_CENTS = 100_000_000;

    public function __construct(private ImageProcessor $images) {}

    /**
     * @param  array{title: string, why: string, target_cents: int, deadline?: ?string, payment_url: string}  $data
     */
    public function propose(User $user, Group $group, array $data): GroupAsk
    {
        Gate::forUser($user)->authorize('proposeAsk', $group);

        $this->assertAmount($data['target_cents'], 'target');
        $this->assertText($data['title'], 80, 'title');
        $this->assertText($data['why'], 1000, 'why');
        $this->assertPaymentUrl($data['payment_url']);

        $deadline = $data['deadline'] ?? null;
        if ($deadline && now()->startOfDay()->gt($deadline)) {
            throw ValidationException::withMessages(['deadline' => 'The deadline must be today or later.']);
        }

        return GroupAsk::create([
            'group_id' => $group->id,
            'created_by' => $user->id,
            'title' => trim($data['title']),
            'why' => trim($data['why']),
            'target_cents' => $data['target_cents'],
            'deadline' => $deadline ?: null,
            'payment_url' => trim($data['payment_url']),
            'status' => GroupAsk::STATUS_PROPOSED,
        ]);
    }

    public function approve(User $admin, GroupAsk $ask): GroupAsk
    {
        /** @var Group $group */
        $group = $ask->group;
        Gate::forUser($admin)->authorize('manageFunds', $group);
        $this->assertStatus($ask, GroupAsk::STATUS_PROPOSED);

        if ($ask->created_by === $admin->id && $group->admins()->count() >= 2) {
            throw new AuthorizationException('Another admin has to approve your own ask.');
        }

        $open = GroupAsk::where('group_id', $group->id)->where('status', GroupAsk::STATUS_OPEN)->count();
        if ($open >= GroupAsk::MAX_OPEN_PER_GROUP) {
            throw ValidationException::withMessages(['ask' => 'This group already has 20 open asks.']);
        }

        $ask->update(['status' => GroupAsk::STATUS_OPEN, 'decided_by' => $admin->id, 'decided_at' => now()]);

        return $ask;
    }

    public function decline(User $admin, GroupAsk $ask, string $reason): GroupAsk
    {
        Gate::forUser($admin)->authorize('manageFunds', $ask->group);
        $this->assertStatus($ask, GroupAsk::STATUS_PROPOSED);
        $reason = $this->requireText($reason, 300, 'reason', 'Give a reason so the author understands.');

        $ask->update([
            'status' => GroupAsk::STATUS_DECLINED, 'decided_by' => $admin->id,
            'decided_at' => now(), 'decline_reason' => $reason,
        ]);

        return $ask;
    }

    public function recordReceived(User $admin, GroupAsk $ask, int $amountCents, ?string $counterparty = null, ?string $note = null): GroupAskEntry
    {
        Gate::forUser($admin)->authorize('manageFunds', $ask->group);
        $this->assertStatus($ask, GroupAsk::STATUS_OPEN);
        $this->assertAmount($amountCents, 'amount');

        /** @var GroupAskEntry $entry */
        $entry = $ask->entries()->create([
            'kind' => GroupAskEntry::KIND_IN,
            'amount_cents' => $amountCents,
            'counterparty' => $counterparty ? mb_substr(trim($counterparty), 0, 80) : null,
            'note' => $note ? mb_substr(trim($note), 0, 200) : null,
            'recorded_by' => $admin->id,
        ]);

        return $entry;
    }

    /**
     * @param  mixed  $itemPhoto  Uploaded file for the item
     * @param  mixed  $receiptPhoto  Uploaded file for the receipt
     */
    public function recordPurchase(User $admin, GroupAsk $ask, int $amountCents, string $description, mixed $itemPhoto, mixed $receiptPhoto, ?string $note = null): GroupAskEntry
    {
        Gate::forUser($admin)->authorize('manageFunds', $ask->group);
        $this->assertStatus($ask, GroupAsk::STATUS_OPEN);
        $this->assertAmount($amountCents, 'amount');

        if (trim($description) === '' || ! $itemPhoto || ! $receiptPhoto) {
            throw ValidationException::withMessages(['purchase' => 'Add what was bought, the amount, and photos of the item and the receipt.']);
        }

        $dir = 'group-asks/'.$ask->id;

        /** @var GroupAskEntry $entry */
        $entry = $ask->entries()->create([
            'kind' => GroupAskEntry::KIND_OUT,
            'amount_cents' => $amountCents,
            'description' => mb_substr(trim($description), 0, 120),
            'note' => $note ? mb_substr(trim($note), 0, 200) : null,
            'item_photo_path' => $this->images->store($itemPhoto, $dir, 'post', 's3'),
            'receipt_photo_path' => $this->images->store($receiptPhoto, $dir, 'post', 's3'),
            'recorded_by' => $admin->id,
        ]);

        return $entry;
    }

    public function voidEntry(User $admin, GroupAskEntry $entry, string $reason): GroupAskEntry
    {
        Gate::forUser($admin)->authorize('manageFunds', $entry->ask->group);

        if ($entry->isVoided()) {
            throw ValidationException::withMessages(['entry' => 'This entry is already voided.']);
        }

        $reason = $this->requireText($reason, 300, 'reason', 'A reason is required.');
        $entry->update(['voided_at' => now(), 'voided_by' => $admin->id, 'void_reason' => $reason]);

        return $entry;
    }

    public function close(User $admin, GroupAsk $ask, string $outcome, string $note): GroupAsk
    {
        Gate::forUser($admin)->authorize('manageFunds', $ask->group);
        $this->assertStatus($ask, GroupAsk::STATUS_OPEN);
        $note = $this->requireText($note, 500, 'note', 'Say what happens to any money left over.');

        if (! in_array($outcome, [GroupAsk::STATUS_COMPLETED, GroupAsk::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages(['outcome' => 'Choose completed or cancelled.']);
        }

        if ($outcome === GroupAsk::STATUS_COMPLETED) {
            $hasProof = $ask->entries()->where('kind', GroupAskEntry::KIND_OUT)->whereNull('voided_at')
                ->whereNotNull('receipt_photo_path')->exists();

            if (! $hasProof) {
                throw ValidationException::withMessages(['outcome' => 'Record a purchase with a receipt before completing, or cancel instead.']);
            }
        }

        $ask->update(['status' => $outcome, 'closed_by' => $admin->id, 'closed_at' => now(), 'closing_note' => $note]);

        return $ask;
    }

    public function chipIn(User $user, GroupAsk $ask): GroupAskChip
    {
        Gate::forUser($user)->authorize('proposeAsk', $ask->group);
        $this->assertStatus($ask, GroupAsk::STATUS_OPEN);

        return DB::transaction(fn () => GroupAskChip::firstOrCreate(['group_ask_id' => $ask->id, 'user_id' => $user->id]));
    }

    /**
     * Whole dollars, with cents only when they are non-zero.
     */
    public static function formatMoney(int $cents): string
    {
        return '$'.($cents % 100 === 0 ? number_format($cents / 100) : number_format($cents / 100, 2));
    }

    private function assertStatus(GroupAsk $ask, string $status): void
    {
        if ($ask->fresh()?->status !== $status) {
            throw ValidationException::withMessages(['ask' => 'This ask is not '.$status.'.']);
        }
    }

    private function assertAmount(int $cents, string $field): void
    {
        if ($cents < 100 || $cents > self::MAX_AMOUNT_CENTS) {
            throw ValidationException::withMessages([$field => 'Enter an amount from $1 to $1,000,000.']);
        }
    }

    private function assertText(string $value, int $max, string $field): void
    {
        $this->requireText($value, $max, $field, 'This field is required.');
    }

    private function requireText(string $value, int $max, string $field, string $message): string
    {
        $value = trim($value);

        if ($value === '' || mb_strlen($value) > $max) {
            throw ValidationException::withMessages([$field => $value === '' ? $message : "Keep it under {$max} characters."]);
        }

        return $value;
    }

    private function assertPaymentUrl(string $url): void
    {
        if (! GroupAsk::isAllowedPaymentUrl(trim($url))) {
            throw ValidationException::withMessages(['payment_url' => 'Use an https link from Venmo, Cash App, PayPal, Ko-fi, Buy Me a Coffee, GoFundMe or Zeffy.']);
        }
    }
}
