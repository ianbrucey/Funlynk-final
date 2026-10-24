<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\EventNeed;
use App\Models\EventNeedClaim;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Event needs list (spec 014): items an event needs and who is bringing them.
 * Authorization (who may manage or claim) is decided by ActivityPolicy; this
 * service enforces data rules: limits, quantities, and that the event is open.
 */
class EventNeedService
{
    public function isOpen(Activity $activity): bool
    {
        return in_array($activity->status, ['published', 'active'], true) && $activity->start_time !== null && \Illuminate\Support\Carbon::parse($activity->start_time)->isFuture();
    }

    public function isAttending(Activity $activity, User $user): bool
    {
        return Rsvp::query()->where('activity_id', $activity->id)->where('user_id', $user->id)->where('status', 'attending')->exists();
    }

    public function addNeed(Activity $activity, User $actor, string $name, ?string $note, int $quantity): EventNeed
    {
        $this->assertOpen($activity);
        [$name, $note, $quantity] = $this->clean($name, $note, $quantity);

        return DB::transaction(function () use ($activity, $actor, $name, $note, $quantity) {
            Activity::query()->whereKey($activity->id)->lockForUpdate()->first();
            $count = EventNeed::query()->where('activity_id', $activity->id)->count();

            if ($count >= EventNeed::MAX_PER_EVENT) {
                throw new \DomainException('An event can list up to '.EventNeed::MAX_PER_EVENT.' items.');
            }

            return EventNeed::create([
                'activity_id' => $activity->id,
                'created_by' => $actor->id,
                'name' => $name,
                'note' => $note,
                'quantity' => $quantity,
                'sort_order' => (int) EventNeed::query()->where('activity_id', $activity->id)->max('sort_order') + 1,
            ]);
        });
    }

    public function updateNeed(EventNeed $need, string $name, ?string $note, int $quantity): EventNeed
    {
        $this->assertOpen($need->activity);
        [$name, $note, $quantity] = $this->clean($name, $note, $quantity);

        return DB::transaction(function () use ($need, $name, $note, $quantity) {
            $locked = EventNeed::query()->whereKey($need->id)->lockForUpdate()->firstOrFail();
            $claimed = (int) EventNeedClaim::query()->where('event_need_id', $locked->id)->sum('quantity');

            if ($quantity < $claimed) {
                throw new \DomainException("{$claimed} are already claimed, so the quantity cannot go below that.");
            }

            $locked->update(['name' => $name, 'note' => $note, 'quantity' => $quantity]);

            return $locked;
        });
    }

    public function removeNeed(EventNeed $need): void
    {
        $need->delete();
    }

    public function claim(EventNeed $need, User $user, int $quantity): EventNeedClaim
    {
        $this->assertOpen($need->activity);

        if ($quantity < 1 || $quantity > 99) {
            throw new \DomainException('Quantity must be between 1 and 99.');
        }

        return DB::transaction(function () use ($need, $user, $quantity) {
            $locked = EventNeed::query()->whereKey($need->id)->lockForUpdate()->firstOrFail();

            if (EventNeedClaim::query()->where('event_need_id', $locked->id)->where('user_id', $user->id)->exists()) {
                throw new \DomainException('You already claimed this item. Release it first to change it.');
            }

            $left = $locked->quantity - (int) EventNeedClaim::query()->where('event_need_id', $locked->id)->sum('quantity');

            if ($left < 1) {
                throw new \DomainException('Someone just took the last one.');
            }

            if ($quantity > $left) {
                throw new \DomainException("Only {$left} still needed.");
            }

            return EventNeedClaim::create(['event_need_id' => $locked->id, 'user_id' => $user->id, 'quantity' => $quantity]);
        });
    }

    public function release(EventNeedClaim $claim): void
    {
        $claim->delete();
    }

    public function confirm(EventNeedClaim $claim): void
    {
        $claim->update(['confirmed_at' => now()]);
    }

    public function assign(EventNeed $need, User $target): EventNeedClaim
    {
        if (! $this->isAttending($need->activity, $target)) {
            throw new \DomainException('Only people who are going can be assigned an item.');
        }

        return $this->claim($need, $target, 1);
    }

    public function markBrought(EventNeed $need, bool $brought): void
    {
        $need->claims()->update(['brought_at' => $brought ? now() : null]);
    }

    /**
     * Copy items (never claims) from another event, up to the per-event limit.
     */
    public function copyFrom(Activity $target, Activity $source, User $actor): int
    {
        $this->assertOpen($target);
        $copied = 0;

        foreach (EventNeed::query()->where('activity_id', $source->id)->orderBy('sort_order')->get() as $need) {
            try {
                $this->addNeed($target, $actor, $need->name, $need->note, $need->quantity);
                $copied++;
            } catch (\DomainException) {
                break;
            }
        }

        return $copied;
    }

    /**
     * Free every claim a user holds on an event (they stopped attending).
     */
    public function releaseForUser(string $activityId, string $userId): void
    {
        EventNeedClaim::query()
            ->where('user_id', $userId)
            ->whereIn('event_need_id', EventNeed::query()->where('activity_id', $activityId)->select('id'))
            ->delete();
    }

    protected function assertOpen(Activity $activity): void
    {
        if (! $this->isOpen($activity)) {
            throw new \DomainException('This event is no longer open for changes to the list.');
        }
    }

    /**
     * @return array{0: string, 1: ?string, 2: int}
     */
    protected function clean(string $name, ?string $note, int $quantity): array
    {
        $name = trim($name);
        $note = $note !== null ? trim($note) : null;

        if ($name === '' || mb_strlen($name) > 80) {
            throw new \DomainException('Give the item a name of up to 80 characters.');
        }

        if ($note !== null && mb_strlen($note) > 200) {
            throw new \DomainException('Notes can be up to 200 characters.');
        }

        if ($quantity < 1 || $quantity > 99) {
            throw new \DomainException('Quantity must be between 1 and 99.');
        }

        return [$name, $note === '' ? null : $note, $quantity];
    }
}
