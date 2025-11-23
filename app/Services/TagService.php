<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TagService
{
    /**
     * Get trending tags based on usage count and recency
     * 
     * @param int $limit Number of trending tags to return
     * @param int $days Number of days to consider for recency
     * @return Collection
     */
    public function getTrendingTags(int $limit = 10, int $days = 7): Collection
    {
        return Cache::remember('tags:trending', 3600, function () use ($limit, $days) {
            $cutoffDate = now()->subDays($days);
            
            return Tag::query()
                ->select('tags.*')
                ->selectRaw('
                    (usage_count * 
                     CASE 
                         WHEN created_at >= ? THEN 2.0
                         WHEN created_at >= ? THEN 1.5
                         ELSE 1.0
                     END) as trending_score
                ', [
                    $cutoffDate,
                    now()->subDays($days * 2)
                ])
                ->where('usage_count', '>', 0)
                ->orderByDesc('trending_score')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get tag suggestions based on search query
     * 
     * @param string $query Search query
     * @param int $limit Maximum number of suggestions
     * @return Collection
     */
    public function getSuggestions(string $query, int $limit = 10): Collection
    {
        if (empty(trim($query))) {
            return collect();
        }

        return Tag::query()
            ->where('name', 'ILIKE', '%' . $query . '%')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new tag with auto-generated slug
     * 
     * @param string $name Tag name
     * @param string|null $category Optional category
     * @param string|null $description Optional description
     * @return Tag
     */
    public function createTag(string $name, ?string $category = null, ?string $description = null): Tag
    {
        $slug = Str::slug($name);
        
        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (Tag::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return Tag::create([
            'name' => $name,
            'slug' => $slug,
            'category' => $category,
            'description' => $description,
            'usage_count' => 0,
            'is_featured' => false,
        ]);
    }

    /**
     * Increment usage count for a tag
     * 
     * @param Tag $tag
     * @return void
     */
    public function incrementUsage(Tag $tag): void
    {
        $tag->increment('usage_count');
        $this->clearTrendingCache();
    }

    /**
     * Decrement usage count for a tag
     * 
     * @param Tag $tag
     * @return void
     */
    public function decrementUsage(Tag $tag): void
    {
        $tag->decrement('usage_count');
        $this->clearTrendingCache();
    }

    /**
     * Get tag analytics by category
     * 
     * @return Collection
     */
    public function getAnalyticsByCategory(): Collection
    {
        return Tag::query()
            ->select('category')
            ->selectRaw('COUNT(*) as tag_count')
            ->selectRaw('SUM(usage_count) as total_usage')
            ->selectRaw('AVG(usage_count) as avg_usage')
            ->groupBy('category')
            ->orderByDesc('total_usage')
            ->get();
    }

    /**
     * Get unused tags (usage_count = 0)
     * 
     * @param int $olderThanDays Only return tags older than X days
     * @return Collection
     */
    public function getUnusedTags(int $olderThanDays = 30): Collection
    {
        return Tag::query()
            ->where('usage_count', 0)
            ->where('created_at', '<', now()->subDays($olderThanDays))
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Merge two tags (move all activities from source to target, delete source)
     * 
     * @param Tag $source Tag to merge from
     * @param Tag $target Tag to merge into
     * @return void
     */
    public function mergeTags(Tag $source, Tag $target): void
    {
        DB::transaction(function () use ($source, $target) {
            // Get all activities using the source tag
            $activityIds = DB::table('activity_tag')
                ->where('tag_id', $source->id)
                ->pluck('activity_id');

            // Remove source tag from activities
            DB::table('activity_tag')
                ->where('tag_id', $source->id)
                ->delete();

            // Add target tag to those activities (if not already present)
            foreach ($activityIds as $activityId) {
                DB::table('activity_tag')->insertOrIgnore([
                    'activity_id' => $activityId,
                    'tag_id' => $target->id,
                ]);
            }

            // Update usage count
            $target->usage_count = DB::table('activity_tag')
                ->where('tag_id', $target->id)
                ->count();
            $target->save();

            // Delete source tag
            $source->delete();

            $this->clearTrendingCache();
        });
    }

    /**
     * Recalculate usage counts for all tags
     * 
     * @return void
     */
    public function recalculateUsageCounts(): void
    {
        $tagUsages = DB::table('activity_tag')
            ->select('tag_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('tag_id')
            ->get();

        foreach ($tagUsages as $usage) {
            Tag::where('id', $usage->tag_id)
                ->update(['usage_count' => $usage->count]);
        }

        // Set usage_count to 0 for tags not in the pivot table
        Tag::whereNotIn('id', $tagUsages->pluck('tag_id'))
            ->update(['usage_count' => 0]);

        $this->clearTrendingCache();
    }

    /**
     * Clear trending tags cache
     * 
     * @return void
     */
    protected function clearTrendingCache(): void
    {
        Cache::forget('tags:trending');
    }

    /**
     * Get featured tags
     * 
     * @param int $limit Maximum number of featured tags
     * @return Collection
     */
    public function getFeaturedTags(int $limit = 5): Collection
    {
        return Tag::query()
            ->where('is_featured', true)
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Validate tag moderation rules
     * 
     * @param string $name Tag name to validate
     * @return array ['valid' => bool, 'message' => string|null]
     */
    public function validateTag(string $name): array
    {
        // Check minimum length
        if (strlen($name) < 2) {
            return ['valid' => false, 'message' => 'Tag must be at least 2 characters long'];
        }

        // Check maximum length
        if (strlen($name) > 50) {
            return ['valid' => false, 'message' => 'Tag must be 50 characters or less'];
        }

        // Check for profanity (basic example - extend with real profanity filter)
        $bannedWords = ['spam', 'test123']; // Add more as needed
        foreach ($bannedWords as $word) {
            if (stripos($name, $word) !== false) {
                return ['valid' => false, 'message' => 'Tag contains inappropriate content'];
            }
        }

        // Check if tag already exists
        if (Tag::where('name', 'ILIKE', $name)->exists()) {
            return ['valid' => false, 'message' => 'Tag already exists'];
        }

        return ['valid' => true, 'message' => null];
    }
}
