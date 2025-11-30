<?php

namespace App\Services;

use App\Events\PostCreated;
use App\Events\PostInvitationSent;
use App\Events\PostReacted;
use App\Models\Activity;
use App\Models\Post;
use App\Models\PostInvitation;
use App\Models\PostReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use MatanYadaev\EloquentSpatial\Objects\Point;

class PostService
{
    /**
     * Create a new post from the given data.
     *
     * Expected keys in $data:
     * - user (User instance) OR user_id (string, UUID)
     * - title (string) OR content (legacy name)
     * - description (optional)
     * - location_name (optional)
     * - latitude / longitude (optional, floats)
     * - location_coordinates (optional Point instance)
     * - time_hint, approximate_time, tags, mood (all optional)
     * - expires_at (optional, Carbon|string) OR ttl_hours (int, default 48)
     */
    public function createPost(array $data): Post
    {
        $userId = $data['user_id'] ?? ($data['user'] instanceof User ? $data['user']->id : null);

        if (! $userId) {
            throw new InvalidArgumentException('PostService::createPost requires a user or user_id.');
        }

        $title = $data['title'] ?? $data['content'] ?? null;

        if (! $title || ! is_string($title)) {
            throw new InvalidArgumentException('PostService::createPost requires a non-empty title.');
        }

        // Location: either explicit Point or latitude/longitude pair.
        $locationCoordinates = $data['location_coordinates'] ?? null;

        if (isset($data['latitude']) || isset($data['longitude'])) {
            if (! isset($data['latitude'], $data['longitude'])) {
                throw new InvalidArgumentException('Both latitude and longitude are required when specifying coordinates.');
            }

            $lat = (float) $data['latitude'];
            $lng = (float) $data['longitude'];

            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                throw new InvalidArgumentException('Invalid latitude/longitude values supplied.');
            }

            $locationCoordinates = new Point($lat, $lng, 4326);
        }

        // Expiration: 24-48h window by default (configurable via ttl_hours).
        $expiresAt = $data['expires_at'] ?? null;

        if (! $expiresAt) {
            $ttlHours = (int) ($data['ttl_hours'] ?? 48);

            if ($ttlHours < 1) {
                $ttlHours = 24;
            }

            $expiresAt = now()->addHours($ttlHours);
        }

        $post = Post::create([
            'user_id' => $userId,
            'title' => $title,
            'description' => $data['description'] ?? ($data['content'] ?? null),
            'location_coordinates' => $locationCoordinates,
            'location_name' => $data['location_name'] ?? null,
            'time_hint' => $data['time_hint'] ?? null,
            'approximate_time' => $data['approximate_time'] ?? null,
            'tags' => $data['tags'] ?? [],
            'geo_hash' => $data['geo_hash'] ?? null,
            'mood' => $data['mood'] ?? null,
            'expires_at' => $expiresAt,
            'status' => 'active',
        ]);

        event(new PostCreated($post));

        return $post;
    }

    /**
     * React to a post using one of the supported reaction types.
     *
     * @param  string  $postId  UUID of the post
     * @param  string  $reactionType  im_down|join_me
     * @param  User|null  $user  Reactor; falls back to auth() if null
     */
    /**
     * Toggle a reaction on a post (add if not exists, remove if exists)
     *
     * @param  string  $postId  UUID of the post
     * @param  string  $reactionType  im_down|invite_friends
     * @param  User|null  $user  Reactor; falls back to auth() if null
     * @return array ['action' => 'added'|'removed', 'reaction' => PostReaction|null]
     */
    public function toggleReaction(string $postId, string $reactionType, ?User $user = null): array
    {
        $allowed = PostReaction::validReactionTypes();

        if (! in_array($reactionType, $allowed, true)) {
            throw new InvalidArgumentException('Invalid reaction type: '.$reactionType);
        }

        $userId = $user?->id ?? auth()->id();

        if (! $userId) {
            throw new InvalidArgumentException('PostService::toggleReaction requires an authenticated user or explicit User instance.');
        }

        $post = Post::findOrFail($postId);

        if ($post->user_id === $userId) {
            throw new InvalidArgumentException('Post owner cannot react to their own post.');
        }

        return DB::transaction(function () use ($post, $userId, $reactionType) {
            // Check if reaction already exists
            $existingReaction = PostReaction::where('post_id', $post->id)
                ->where('user_id', $userId)
                ->where('reaction_type', $reactionType)
                ->first();

            if ($existingReaction) {
                // Remove the reaction
                $existingReaction->delete();

                // Update denormalized count
                $reactionCount = PostReaction::where('post_id', $post->id)->count();
                $post->update(['reaction_count' => $reactionCount]);

                return ['action' => 'removed', 'reaction' => null];
            } else {
                // Add the reaction
                $reaction = PostReaction::create([
                    'post_id' => $post->id,
                    'user_id' => $userId,
                    'reaction_type' => $reactionType,
                    'created_at' => now(),
                ]);

                // Update denormalized count
                $reactionCount = PostReaction::where('post_id', $post->id)->count();
                $post->update(['reaction_count' => $reactionCount]);

                $eligibility = $this->checkConversionEligibility($post->id);

                event(new PostReacted($post->fresh(), $reaction, $eligibility));

                return ['action' => 'added', 'reaction' => $reaction];
            }
        });
    }

    public function reactToPost(string $postId, string $reactionType, ?User $user = null): PostReaction
    {
        $allowed = PostReaction::validReactionTypes();

        if (! in_array($reactionType, $allowed, true)) {
            throw new InvalidArgumentException('Invalid reaction type: '.$reactionType);
        }

        $userId = $user?->id ?? auth()->id();

        if (! $userId) {
            throw new InvalidArgumentException('PostService::reactToPost requires an authenticated user or explicit User instance.');
        }

        $post = Post::findOrFail($postId);

        if ($post->user_id === $userId) {
            throw new InvalidArgumentException('Post owner cannot react to their own post.');
        }

        return DB::transaction(function () use ($post, $userId, $reactionType) {
            // Create or update the user reaction for this post
            $reaction = PostReaction::updateOrCreate(
                [
                    'post_id' => $post->id,
                    'user_id' => $userId,
                ],
                [
                    'reaction_type' => $reactionType,
                    'created_at' => now(),
                ]
            );

            // Keep the denormalised reaction_count in sync
            $reactionCount = PostReaction::where('post_id', $post->id)->count();
            $post->update(['reaction_count' => $reactionCount]);

            $eligibility = $this->checkConversionEligibility($post->id);

            event(new PostReacted($post->fresh(), $reaction, $eligibility));

            return $reaction;
        });
    }

    /**
     * Check whether a post is eligible for conversion based on reaction thresholds.
     *
     * @return array{
     *     eligible: bool,
     *     auto_convert: bool,
     *     reaction_count: int,
     *     threshold_5: int,
     *     threshold_10: int,
     * }
     */
    public function checkConversionEligibility(string $postId): array
    {
        $post = Post::findOrFail($postId);
        $reactionCount = (int) $post->reaction_count;

        return [
            'eligible' => $reactionCount >= Post::CONVERSION_SOFT_THRESHOLD,
            'auto_convert' => $reactionCount >= Post::CONVERSION_STRONG_THRESHOLD,
            'reaction_count' => $reactionCount,
            'threshold_soft' => Post::CONVERSION_SOFT_THRESHOLD,
            'threshold_strong' => Post::CONVERSION_STRONG_THRESHOLD,
        ];
    }

    /**
     * Expire all active posts whose expires_at is in the past.
     *
     * @return int Number of posts marked as expired
     */
    public function expirePosts(): int
    {
        return Post::query()
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);
    }

    /**
     * Get all reactions for a post, newest first, including the reacting user.
     */
    public function getPostReactions(string $postId): Collection
    {
        return PostReaction::where('post_id', $postId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Invite friends to a post.
     *
     * @param  array  $friendIds  Array of user IDs to invite
     * @param  User|null  $inviter  The user sending the invitation
     * @return Collection Collection of PostInvitation models
     */
    public function inviteFriendsToPost(string $postId, array $friendIds, ?User $inviter = null): Collection
    {
        $inviter = $inviter ?? auth()->user();
        $post = Post::findOrFail($postId);
        $invitations = new Collection;

        foreach ($friendIds as $friendId) {
            $invitation = PostInvitation::updateOrCreate(
                ['post_id' => $postId, 'inviter_id' => $inviter->id, 'invitee_id' => $friendId],
                ['status' => 'pending', 'created_at' => now()]
            );

            // Broadcast invitation sent event
            event(new PostInvitationSent($invitation, $post, $inviter, User::find($friendId)));
            $invitations->push($invitation);
        }

        return $invitations;
    }

    /**
     * Get all invitees for a post.
     */
    public function getPostInvitees(string $postId): Collection
    {
        return PostInvitation::where('post_id', $postId)->with('invitee')->get();
    }

    /**
     * Mark an invitation as viewed.
     */
    public function markInvitationViewed(string $invitationId): void
    {
        PostInvitation::where('id', $invitationId)->update(['viewed_at' => now(), 'status' => 'viewed']);
    }

    /**
     * Get all pending invitations for a user.
     */
    public function getUserPendingInvitations(string $userId): Collection
    {
        return PostInvitation::where('invitee_id', $userId)
            ->where('status', 'pending')
            ->with(['post', 'inviter'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Dismiss conversion prompt for a post
     *
     *
     * @throws \Exception
     */
    public function dismissConversionPrompt(string $postId, ?User $user = null): void
    {
        $user = $user ?? auth()->user();
        $post = Post::findOrFail($postId);

        // Authorization check
        if ($post->user_id !== $user->id) {
            throw new \Exception('Unauthorized');
        }

        DB::transaction(function () use ($post) {
            $post->update([
                'conversion_dismissed_at' => now(),
                'conversion_dismiss_count' => $post->conversion_dismiss_count + 1,
            ]);
        });
    }

    /**
     * Get conversion eligibility for a post
     */
    public function getConversionEligibility(string $postId): array
    {
        $post = Post::findOrFail($postId);

        return app(ConversionEligibilityService::class)->checkAndPrompt($post);
    }

    /**
     * Convert a post to an event
     *
     *
     * @throws \Exception
     */
    public function convertToEvent(string $postId, array $eventData, ?User $user = null): Activity
    {
        $user = $user ?? auth()->user();
        $post = Post::with(['reactions', 'invitations'])->findOrFail($postId);

        // Authorization check
        if ($post->user_id !== $user->id) {
            throw new \Exception('Unauthorized: Only post owner can convert');
        }

        // Validate required event fields
        $this->validateEventData($eventData);

        return app(ActivityConversionService::class)->createFromPost($post, $eventData, $user);
    }

    /**
     * Validate event data for conversion
     *
     *
     * @throws \Exception
     */
    protected function validateEventData(array $data): void
    {
        $required = ['start_time', 'end_time', 'max_attendees'];

        foreach ($required as $field) {
            if (! isset($data[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        // Validate times using Carbon for better date parsing
        $startTime = \Carbon\Carbon::parse($data['start_time']);
        $endTime = \Carbon\Carbon::parse($data['end_time']);

        if ($startTime->isPast()) {
            throw new \Exception('Start time must be in the future');
        }

        if ($endTime->lessThanOrEqualTo($startTime)) {
            throw new \Exception('End time must be after start time');
        }

        // Validate capacity
        if ($data['max_attendees'] < 1) {
            throw new \Exception('Max attendees must be at least 1');
        }
    }
}
