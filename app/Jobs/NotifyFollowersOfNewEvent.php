<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\Follow;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Gate;

/**
 * Tells a host's followers about a newly published event (Spec 008).
 *
 * Recipients are followers who may view the event under ActivityPolicy,
 * minus group members (they already get the group notification) and any
 * explicitly excluded users. In-app only; honours notification_preference.
 */
class NotifyFollowersOfNewEvent implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /**
     * @param  array<int, string>  $excludeUserIds
     */
    public function __construct(
        public string $activityId,
        public bool $recurring = false,
        public array $excludeUserIds = [],
    ) {}

    public function handle(): void
    {
        $activity = Activity::with(['host', 'group'])->find($this->activityId);

        if (! $activity || $activity->status !== 'published' || ! $activity->host) {
            return;
        }

        $hostName = $activity->host->display_name ?? $activity->host->username ?? 'Someone you follow';
        $memberIds = $activity->group_id
            ? $activity->group->memberships()->pluck('user_id')->all()
            : [];
        $skip = array_flip(array_merge($this->excludeUserIds, $memberIds, [$activity->host_id]));

        Follow::query()
            ->where('following_id', $activity->host_id)
            ->select('follower_id')
            ->chunkById(200, function ($follows) use ($activity, $hostName, $skip) {
                $followers = User::query()->whereIn('id', $follows->pluck('follower_id'))->get();

                foreach ($followers as $follower) {
                    if (isset($skip[$follower->id]) || in_array($follower->notification_preference, ['none', 'email_only'], true)) {
                        continue;
                    }

                    if (! Gate::forUser($follower)->allows('view', $activity)) {
                        continue;
                    }

                    $this->notify($follower, $activity, $hostName);
                }
            }, 'follower_id');
    }

    protected function notify(User $follower, Activity $activity, string $hostName): void
    {
        Notification::create([
            'user_id' => $follower->id,
            'type' => 'followed_host_event',
            'title' => $this->recurring ? "{$hostName} started a recurring event" : "New event from {$hostName}",
            'message' => $this->recurring
                ? "{$hostName} set up a recurring event: {$activity->title}"
                : "{$hostName} published a new event: {$activity->title}",
            'data' => [
                'activity_id' => $activity->id,
                'activity_title' => $activity->title,
                'host_id' => $activity->host_id,
                'host_name' => $hostName,
                'recurring' => $this->recurring,
                'url' => route('events.show', $activity->id),
            ],
            'delivery_method' => 'in_app',
            'delivery_status' => 'sent',
        ]);
    }
}
