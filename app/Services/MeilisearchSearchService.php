<?php

namespace App\Services;

use App\Contracts\SearchServiceInterface;
use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;

class MeilisearchSearchService implements SearchServiceInterface
{
    /**
     * Search posts and events using Meilisearch
     */
    public function search(
        string $query,
        User $user,
        ?int $radius = null,
        string $contentType = 'all'
    ): Collection {
        $results = collect();
        
        // Search Posts
        if ($contentType !== 'events') {
            $posts = $this->searchPosts($query, $user, $radius);
            $results = $results->merge($posts);
        }
        
        // Search Activities/Events
        if ($contentType !== 'posts') {
            $events = $this->searchActivities($query, $user, $radius);
            $results = $results->merge($events);
        }
        
        return $results;
    }
    
    /**
     * Search posts with Meilisearch
     */
    protected function searchPosts(string $query, User $user, ?int $radius): Collection
    {
        $search = Post::search($query);
        
        // Build filter array
        $filters = ['status = active'];
        
        // Add native Meilisearch geo filter if radius provided and user has location
        if ($radius && $user->latitude && $user->longitude) {
            // Convert radius from km to meters (Meilisearch uses meters)
            $radiusMeters = $radius * 1000;
            
            // Add _geoRadius filter
            $filters[] = "_geoRadius({$user->latitude}, {$user->longitude}, {$radiusMeters})";
        }
        
        // Apply filters
        $search->options(['filter' => $filters]);
        
        return $search->get()
            ->map(fn($post) => [
                'type' => 'post',
                'data' => $post
            ]);
    }
    
    /**
     * Search activities with Meilisearch
     */
    protected function searchActivities(string $query, User $user, ?int $radius): Collection
    {
        $search = Activity::search($query);
        
        // Build filter array
        $filters = ['status = published'];
        
        // Add native Meilisearch geo filter if radius provided and user has location
        if ($radius && $user->latitude && $user->longitude) {
            // Convert radius from km to meters (Meilisearch uses meters)
            $radiusMeters = $radius * 1000;
            
            // Add _geoRadius filter
            $filters[] = "_geoRadius({$user->latitude}, {$user->longitude}, {$radiusMeters})";
        }
        
        // Apply filters
        $search->options(['filter' => $filters]);
        
        return $search->get()
            ->map(fn($activity) => [
                'type' => 'event',
                'data' => $activity
            ]);
    }
}
