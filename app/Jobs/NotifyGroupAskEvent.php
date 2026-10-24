<?php

namespace App\Jobs;

use App\Models\GroupAsk;
use App\Models\GroupAskEntry;
use App\Models\Notification;
use App\Models\User;
use App\Services\GroupAskService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

/**
 * In-app notifications for group asks (spec 015): proposal -> admins,
 * opened -> members, purchase posted -> members, declined -> author.
 * Honours the global notification preference; never notifies the actor.
 */
class NotifyGroupAskEvent implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public const PROPOSED = 'proposed';

    public const OPENED = 'opened';

    public const PURCHASE = 'purchase';

    public const DECLINED = 'declined';

    public function __construct(
        public string $askId,
        public string $event,
        public string $actorId,
        public ?string $entryId = null,
    ) {}

    public function handle(): void
    {
        $ask = GroupAsk::query()->with('group')->find($this->askId);

        if (! $ask || ! $ask->group) {
            return;
        }

        $url = route('groups.show', $ask->group).'?tab=funds';
        $group = $ask->group;

        [$title, $message, $type, $recipients] = match ($this->event) {
            self::PROPOSED => [
                'New ask to review',
                "A proposal \"{$ask->title}\" is waiting for approval in {$group->name}.",
                'group_ask_proposed',
                $group->admins()->get(),
            ],
            self::OPENED => [
                'New ask in '.$group->name,
                "\"{$ask->title}\" is open. Target ".GroupAskService::formatMoney($ask->target_cents).'.',
                'group_ask_opened',
                $group->members()->get(),
            ],
            self::PURCHASE => [
                'Purchase posted in '.$group->name,
                $this->purchaseMessage($ask),
                'group_ask_purchase',
                $group->members()->get(),
            ],
            self::DECLINED => [
                'Your ask was declined',
                "\"{$ask->title}\": {$ask->decline_reason}",
                'group_ask_declined',
                User::query()->whereKey($ask->created_by)->get(),
            ],
            default => [null, null, null, collect()],
        };

        if ($title === null) {
            return;
        }

        foreach ($recipients as $user) {
            if ($user->id === $this->actorId || ! $user->wantsInAppNotifications()) {
                continue;
            }

            Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'delivery_method' => 'in_app',
                'data' => ['group_id' => $group->id, 'ask_id' => $ask->id, 'url' => $url],
            ]);
        }
    }

    private function purchaseMessage(GroupAsk $ask): string
    {
        $entry = $this->entryId ? GroupAskEntry::query()->find($this->entryId) : null;

        return $entry
            ? "{$entry->description}: ".GroupAskService::formatMoney($entry->amount_cents)." for \"{$ask->title}\" (receipt attached)."
            : "A purchase was posted for \"{$ask->title}\".";
    }
}
