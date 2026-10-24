<?php

namespace App\Jobs;

use App\Models\EventNeedClaim;
use App\Models\Notification;
use App\Services\EventNeedService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

/**
 * About 24 hours before an event, ask each person who claimed items whether
 * they are still bringing them (spec 014). One notification per person per
 * event; every claim is marked reminded so nothing is sent twice.
 */
class SendNeedsCheckReminders implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public const HOURS_BEFORE = 24;

    public const MIN_CLAIM_AGE_HOURS = 3;

    public function handle(EventNeedService $needs): void
    {
        $claims = EventNeedClaim::query()
            ->whereNull('reminded_at')
            ->where('created_at', '<=', now()->subHours(self::MIN_CLAIM_AGE_HOURS))
            ->whereHas('need.activity', fn ($q) => $q
                ->whereIn('status', ['published', 'active'])
                ->where('start_time', '>', now())
                ->where('start_time', '<=', now()->addHours(self::HOURS_BEFORE)))
            ->with(['need.activity', 'user'])
            ->get();

        foreach ($claims->groupBy(fn (EventNeedClaim $c) => $c->user_id.'|'.$c->need->activity_id) as $group) {
            $group = $group->sortBy(fn (EventNeedClaim $c) => sprintf('%05d', $c->need->sort_order).$c->need->name)->values();
            $first = $group->first();
            $user = $first->user;
            $activity = $first->need->activity;

            if ($user->wantsInAppNotifications() && $needs->isAttending($activity, $user)) {
                $names = $group->map(fn (EventNeedClaim $c) => $c->need->name)->values();
                $label = $names->count() > 1 ? $names->first().' and '.($names->count() - 1).' more' : $names->first();

                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'event_need_check',
                    'title' => "Still bringing {$label}?",
                    'message' => $activity->title.' starts '.\Illuminate\Support\Carbon::parse($activity->start_time)->format('D, M j \a\t g:i A'),
                    'data' => [
                        'activity_id' => $activity->id,
                        'claim_ids' => $group->pluck('id')->all(),
                        'url' => route('events.show', $activity).'#needs',
                    ],
                    'delivery_method' => 'in_app',
                    'delivery_status' => 'sent',
                ]);
            }

            EventNeedClaim::query()->whereIn('id', $group->pluck('id'))->update(['reminded_at' => now()]);
        }
    }
}
