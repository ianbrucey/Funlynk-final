<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MeilisearchUserSearchService
{
    /**
     * Search for users using Meilisearch
     */
    public function search(
        ?string $query = null,
        array $interests = [],
        ?int $radius = null,
        ?User $currentUser = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        // Build Meilisearch query
        $searchQuery = $query ?? '';
        
        // Build filters
        $filters = ['is_active = true'];
        
        // Exclude current user
        if ($currentUser) {
            $filters[] = "id != {$currentUser->id}";
        }
        
        // Filter by interests (ANY match)
        if (!empty($interests)) {
            $interestFilters = array_map(function ($interest) {
                return "interests = '{$interest}'";
            }, $interests);
            $filters[] = '(' . implode(' OR ', $interestFilters) . ')';
        }
        
        // Build search parameters
        $searchParams = [
            'filter' => implode(' AND ', $filters),
            'limit' => $perPage,
            'attributesToRetrieve' => ['*'],
        ];
        
        // Add geo filtering if radius and user location provided
        if ($radius && $currentUser && $currentUser->location_coordinates) {
            $lat = $currentUser->location_coordinates->latitude;
            $lng = $currentUser->location_coordinates->longitude;
            $radiusMeters = $radius * 1000;
            
            $searchParams['filter'] .= " AND _geoRadius({$lat}, {$lng}, {$radiusMeters})";
            $searchParams['sort'] = ['_geoPoint(' . $lat . ', ' . $lng . '):asc'];
        } else {
            // Sort by follower count if no geo filter
            $searchParams['sort'] = ['follower_count:desc'];
        }
        
        // Perform search
        $results = User::search($searchQuery, function ($meilisearch, $query, $options) use ($searchParams) {
            $options = array_merge($options, $searchParams);
            return $meilisearch->search($query, $options);
        })->raw();
        
        // Get current page from request
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;
        
        // Get total hits
        $total = $results['estimatedTotalHits'] ?? 0;
        
        // Extract user IDs from results
        $userIds = collect($results['hits'] ?? [])->pluck('id')->toArray();
        
        // Load users from database maintaining Meilisearch order
        $users = User::whereIn('id', $userIds)
            ->get()
            ->sortBy(function ($user) use ($userIds) {
                return array_search($user->id, $userIds);
            })
            ->values();
        
        // Create paginator
        return new LengthAwarePaginator(
            $users,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
    
    /**
     * Get popular interests from all users
     */
    public function getPopularInterests(int $limit = 20): array
    {
        $result = \DB::select(
            "SELECT interest, count(*) as count
             FROM users, jsonb_array_elements_text(interests::jsonb) as interest
             WHERE is_active = true AND interests IS NOT NULL
             GROUP BY interest
             ORDER BY count DESC
             LIMIT ?",
            [$limit]
        );

        return array_map(fn ($row) => $row->interest, $result);
    }
}
