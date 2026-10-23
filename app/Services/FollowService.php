<?php

namespace App\Services;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FollowService
{
    /**
     * Follow a user. Idempotent: counters only change when a row is created.
     *
     * @return bool true when a new follow was created
     */
    public function follow(User $follower, User $host): bool
    {
        if ($follower->id === $host->id) {
            return false;
        }

        return DB::transaction(function () use ($follower, $host) {
            $follow = Follow::firstOrCreate([
                'follower_id' => $follower->id,
                'following_id' => $host->id,
            ]);

            if (! $follow->wasRecentlyCreated) {
                return false;
            }

            User::whereKey($host->id)->increment('follower_count');
            User::whereKey($follower->id)->increment('following_count');

            return true;
        });
    }

    /**
     * Unfollow a user. Idempotent: counters only change when a row is removed.
     *
     * @return bool true when a follow was removed
     */
    public function unfollow(User $follower, User $host): bool
    {
        return DB::transaction(function () use ($follower, $host) {
            $deleted = Follow::where('follower_id', $follower->id)
                ->where('following_id', $host->id)
                ->delete();

            if ($deleted === 0) {
                return false;
            }

            User::whereKey($host->id)->decrement('follower_count');
            User::whereKey($follower->id)->decrement('following_count');

            return true;
        });
    }

    /**
     * Which of these host ids does the user follow? One query for the whole list.
     *
     * @param  array<int, string>  $hostIds
     * @return array<int, string>
     */
    public function followedIds(User $user, array $hostIds): array
    {
        if ($hostIds === []) {
            return [];
        }

        return Follow::where('follower_id', $user->id)
            ->whereIn('following_id', $hostIds)
            ->pluck('following_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /**
     * Record that the user has seen the "what following does" intro.
     *
     * @return bool true only the first time (atomic)
     */
    public function markIntroSeen(User $user): bool
    {
        return User::whereKey($user->id)
            ->whereNull('follow_intro_seen_at')
            ->update(['follow_intro_seen_at' => now()]) === 1;
    }
}
