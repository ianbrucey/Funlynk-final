<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use Carbon\CarbonInterface;
use MatanYadaev\EloquentSpatial\Objects\Point;

class RecommendationEngine
{
    /**
     * Score content for personalization (0-100).
     *
     * - Location proximity (0-40)
     * - Interest match (0-30)
     * - Social graph (0-20) – placeholder for now
     * - Temporal relevance (0-10)
     */
    public function scoreContent(User $user, Post|Activity $content): float
    {
        $score = 0.0;

        $score += $this->locationScore($user, $content);   // 0-40
        $score += $this->interestScore($user, $content);   // 0-30
        $score += $this->socialScore($user, $content);     // 0-20 (placeholder)
        $score += $this->temporalScore($content);          // 0-10

        return $score;
    }

    public function getReasonForScore(User $user, Post|Activity $content): string
    {
        $reasons = [];

        // Interest-based reason
        $userInterests = collect($user->interests ?? [])->flatten()->filter()->values()->all();
        $contentTags = collect($content->tags ?? [])->flatten()->filter()->values()->all();
        $matching = array_values(array_intersect($userInterests, $contentTags));

        if (! empty($matching)) {
            $reasons[] = 'Based on your interest in '.implode(', ', $matching);
        }

        // Location-based reason
        if ($user->location_coordinates instanceof Point && $content->location_coordinates instanceof Point) {
            $reasons[] = 'Popular near you';
        }

        // Temporal reason
        if ($content instanceof Post) {
            $reasons[] = 'Happening soon in your area';
        } else {
            $reasons[] = 'Upcoming event that matches your vibe';
        }

        return $reasons[0] ?? 'Recommended for you';
    }

    protected function locationScore(User $user, Post|Activity $content): float
    {
        $userLocation = $user->location_coordinates;
        $contentLocation = $content->location_coordinates;

        if (! ($userLocation instanceof Point) || ! ($contentLocation instanceof Point)) {
            return 20.0; // Neutral mid-score if we lack coordinates
        }

        // Approximate distance in meters using PostgreSQL's ST_Distance via attribute access
        // When using Eloquent Spatial, we can't call distance without a query, so we
        // approximate by simple lat/lng distance buckets on the PHP side.
        $latDiff = abs($userLocation->latitude - $contentLocation->latitude);
        $lngDiff = abs($userLocation->longitude - $contentLocation->longitude);
        $approxKm = max($latDiff, $lngDiff) * 111; // rough upper bound

        return match (true) {
            $approxKm <= 2 => 40.0,
            $approxKm <= 5 => 32.0,
            $approxKm <= 10 => 24.0,
            $approxKm <= 25 => 16.0,
            $approxKm <= 50 => 8.0,
            default => 0.0,
        };
    }

    protected function interestScore(User $user, Post|Activity $content): float
    {
        $userInterests = collect($user->interests ?? [])->flatten()->filter()->values()->all();
        $contentTags = collect($content->tags ?? [])->flatten()->filter()->values()->all();

        if ($userInterests === [] || $contentTags === []) {
            return 10.0; // small base score
        }

        $matching = array_intersect($userInterests, $contentTags);
        $matchCount = count($matching);

        return match (true) {
            $matchCount >= 3 => 30.0,
            $matchCount === 2 => 22.0,
            $matchCount === 1 => 15.0,
            default => 5.0,
        };
    }

    protected function socialScore(User $user, Post|Activity $content): float
    {
        // Placeholder for now – future integration with follows/RSVP graph.
        // Keep non-zero so social can matter later without breaking tests.
        return 10.0;
    }

    protected function temporalScore(Post|Activity $content): float
    {
        if ($content instanceof Post) {
            return $this->postTemporalScore($content);
        }

        return $this->activityTemporalScore($content);
    }

    protected function postTemporalScore(Post $post): float
    {
        $created = $post->created_at;
        if (! $created instanceof CarbonInterface) {
            return 5.0;
        }

        $hours = max(0, $created->diffInHours(now()));

        // decay_factor = 1 / (1 + hours)
        $decay = 1 / (1 + $hours);

        return 10.0 * $decay; // 0-10
    }

    protected function activityTemporalScore(Activity $activity): float
    {
        $start = $activity->start_time;
        if (! $start instanceof CarbonInterface) {
            return 5.0;
        }

        if ($start->isPast()) {
            return 0.0; // already started/past
        }

        $hoursUntil = now()->diffInHours($start, false);

        return match (true) {
            $hoursUntil <= 6 => 10.0,
            $hoursUntil <= 24 => 8.0,
            $hoursUntil <= 72 => 6.0,
            default => 4.0,
        };
    }
}
