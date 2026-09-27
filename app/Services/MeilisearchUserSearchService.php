<?php

namespace App\Services;

use App\Models\User;

class MeilisearchUserSearchService
{
    /**
     * Search for users using PostgreSQL with infinite scroll support
     */
    public function search(
        ?string $query = null,
        array $interests = [],
        ?int $radius = null,
        ?User $currentUser = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        $offset = ($page - 1) * $perPage;

        $builder = User::query()
            ->where('is_active', true)
            ->when($currentUser, fn ($q) => $q->whereKeyNot($currentUser->id));

        // Filter by interests (ANY match on the JSON array)
        if (! empty($interests)) {
            $builder->where(function ($q) use ($interests) {
                foreach ($interests as $interest) {
                    $q->orWhereJsonContains('interests', $interest);
                }
            });
        }

        // Geo-proximity: filter and order by distance when available
        if ($radius && $currentUser && $currentUser->location_coordinates) {
            $userLocation = $currentUser->location_coordinates;
            $builder->whereDistanceSphere('location_coordinates', $userLocation, '<=', $radius * 1000)
                ->orderByDistanceSphere('location_coordinates', $userLocation, 'asc');
        } else {
            $builder->orderByDesc('follower_count');
        }

        // Text search across identity fields
        if (! empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('username', 'ILIKE', "%{$query}%")
                    ->orWhere('display_name', 'ILIKE', "%{$query}%")
                    ->orWhere('bio', 'ILIKE', "%{$query}%");
            });
        }

        // Count before adding the distance ordering – Postgres rejects ORDER BY
        // on non-aggregated expressions inside a count() query.
        $total = (clone $builder)->toBase()->count();

        $users = $builder->skip($offset)->take($perPage)->get();

        // Calculate if there are more results
        $currentlyLoaded = $offset + $users->count();
        $hasMore = $currentlyLoaded < $total && $currentlyLoaded < 200; // Cap at 200 users

        return [
            'users' => $users,
            'hasMore' => $hasMore,
            'total' => $total,
            'page' => $page,
        ];
    }

    /**
     * Get popular interests from all users
     */
    public function getPopularInterests(int $limit = 20): array
    {
        $result = \DB::select(
            'SELECT interest, count(*) as count
             FROM users, jsonb_array_elements_text(interests::jsonb) as interest
             WHERE is_active = true AND interests IS NOT NULL
             GROUP BY interest
             ORDER BY count DESC
             LIMIT ?',
            [$limit]
        );

        return array_map(fn ($row) => $row->interest, $result);
    }
}
