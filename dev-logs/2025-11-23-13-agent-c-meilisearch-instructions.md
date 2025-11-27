# Agent C - Meilisearch Search Implementation

**Date**: 2025-11-23  
**Agent**: Agent C (Backend Specialist)  
**Task**: Implement Meilisearch-powered search service  
**Dependencies**: Agent A must complete SearchServiceInterface first

---

## 🎯 Your Mission

Agent A is building the PostgreSQL-based search (DatabaseSearchService). You will build the Meilisearch-powered search (MeilisearchSearchService) that implements the same interface for easy swapping.

**Key Requirement**: Both services must be interchangeable via `SEARCH_DRIVER` config.

---

## 📋 Task Checklist

### T01: Install Dependencies (15 min)

```bash
# Install Laravel Scout
composer require laravel/scout

# Install Meilisearch driver
composer require meilisearch/meilisearch-php http-interop/http-factory-guzzle

# Publish Scout config
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

**Verify .env has**:
```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=LARAVEL-HERD
```

---

### T02: Configure Post Model for Search (30 min)

**File**: `app/Models/Post.php`

```php
use Laravel\Scout\Searchable;

class Post extends Model
{
    use HasFactory, HasUuids, HasSpatial, Searchable;
    
    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'tags' => $this->tags, // Array of tags
            'location_name' => $this->location_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->timestamp,
            'created_at' => $this->created_at->timestamp,
        ];
    }
    
    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'posts_index';
    }
    
    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'active' && $this->expires_at > now();
    }
}
```

---

### T03: Configure Activity Model for Search (30 min)

**File**: `app/Models/Activity.php`

```php
use Laravel\Scout\Searchable;

class Activity extends Model
{
    use HasFactory, HasUuids, HasSpatial, Searchable;
    
    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'tags' => $this->tags?->pluck('name')->toArray() ?? [], // Extract tag names
            'location_name' => $this->location_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'start_time' => $this->start_time?->timestamp,
            'created_at' => $this->created_at->timestamp,
        ];
    }
    
    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'activities_index';
    }
    
    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published' && $this->start_time > now();
    }
}
```

---

### T04: Configure Meilisearch Indexes (30 min)

**Create**: `app/Console/Commands/ConfigureMeilisearchIndexes.php`

```bash
php artisan make:command ConfigureMeilisearchIndexes --no-interaction
```

```php
<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Post;
use Illuminate\Console\Command;

class ConfigureMeilisearchIndexes extends Command
{
    protected $signature = 'meilisearch:configure';
    protected $description = 'Configure Meilisearch indexes with settings';

    public function handle(): int
    {
        $this->info('Configuring Posts index...');
        
        Post::search()->updateSettings([
            'filterableAttributes' => ['status', 'expires_at', 'created_at'],
            'sortableAttributes' => ['created_at', 'expires_at'],
            'searchableAttributes' => ['title', 'description', 'tags'],
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ],
        ]);
        
        $this->info('Configuring Activities index...');
        
        Activity::search()->updateSettings([
            'filterableAttributes' => ['status', 'start_time', 'created_at'],
            'sortableAttributes' => ['created_at', 'start_time'],
            'searchableAttributes' => ['title', 'description', 'tags'],
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ],
        ]);
        
        $this->info('✅ Meilisearch indexes configured!');
        
        return Command::SUCCESS;
    }
}
```

**Run**:
```bash
php artisan meilisearch:configure
```

---

### T05: Import Existing Data (15 min)

```bash
# Import all posts
php artisan scout:import "App\\Models\\Post"

# Import all activities
php artisan scout:import "App\\Models\\Activity"

# Verify in Meilisearch dashboard: http://127.0.0.1:7700
```

---

### T06: Create MeilisearchSearchService (1-2 hours)

**File**: `app/Services/MeilisearchSearchService.php`

```bash
php artisan make:class Services/MeilisearchSearchService --no-interaction
```

**Implementation**: See next section for full code.

---

### T07: Update SearchServiceProvider (15 min)

**File**: `app/Providers/SearchServiceProvider.php`

Update the binding to include Meilisearch:

```php
public function register(): void
{
    $this->app->bind(SearchServiceInterface::class, function ($app) {
        $driver = config('search.driver', 'database');
        
        return match($driver) {
            'meilisearch' => $app->make(MeilisearchSearchService::class),
            'database' => $app->make(DatabaseSearchService::class),
            default => $app->make(DatabaseSearchService::class),
        };
    });
}
```

---

### T08: Write Tests (1 hour)

**File**: `tests/Feature/MeilisearchSearchServiceTest.php`

```bash
php artisan make:test --pest Feature/MeilisearchSearchServiceTest --no-interaction
```

**Tests to write**:
- Search posts by title
- Search events by description
- Search by tags
- Filter by content type
- Geo-proximity filtering (if supported)
- Empty query handling
- No results handling

---

## 📝 MeilisearchSearchService Implementation

```php
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
        $search = Post::search($query)
            ->where('status', 'active');
        
        // Add geo filter if radius provided and user has location
        if ($radius && $user->latitude && $user->longitude) {
            // Note: Meilisearch geo filtering requires _geo field
            // For now, we'll filter in PHP after getting results
            // TODO: Configure _geo field in toSearchableArray
        }
        
        return $search->get()
            ->when($radius && $user->latitude && $user->longitude, function($collection) use ($user, $radius) {
                // Filter by distance in PHP
                return $collection->filter(function($post) use ($user, $radius) {
                    if (!$post->latitude || !$post->longitude) {
                        return false;
                    }
                    
                    $distance = $this->calculateDistance(
                        $user->latitude,
                        $user->longitude,
                        $post->latitude,
                        $post->longitude
                    );
                    
                    return $distance <= $radius;
                });
            })
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
        $search = Activity::search($query)
            ->where('status', 'published');
        
        return $search->get()
            ->when($radius && $user->latitude && $user->longitude, function($collection) use ($user, $radius) {
                return $collection->filter(function($activity) use ($user, $radius) {
                    if (!$activity->latitude || !$activity->longitude) {
                        return false;
                    }
                    
                    $distance = $this->calculateDistance(
                        $user->latitude,
                        $user->longitude,
                        $activity->latitude,
                        $activity->longitude
                    );
                    
                    return $distance <= $radius;
                });
            })
            ->map(fn($activity) => [
                'type' => 'event',
                'data' => $activity
            ]);
    }
    
    /**
     * Calculate distance between two points (Haversine formula)
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
}
```

---

## 🧪 Testing

```bash
# Set driver to meilisearch
# In .env: SEARCH_DRIVER=meilisearch

# Run tests
php artisan test --filter=MeilisearchSearchServiceTest

# Test manually
php artisan tinker
>>> $user = User::first();
>>> $results = app(\App\Contracts\SearchServiceInterface::class)->search('basketball', $user);
>>> $results->count();
```

---

## ✅ Success Criteria

- [ ] Scout installed and configured
- [ ] Post model searchable
- [ ] Activity model searchable
- [ ] Indexes configured in Meilisearch
- [ ] Existing data imported
- [ ] MeilisearchSearchService implements interface
- [ ] Service provider binding updated
- [ ] Tests passing
- [ ] Can switch drivers via .env

---

## 🚀 Next Steps After Completion

1. Test search in UI (Agent A's search page)
2. Compare performance with DatabaseSearch
3. Monitor Meilisearch dashboard for query analytics
4. Consider adding typo tolerance tuning
5. Consider adding synonyms for better results

---

**Estimated Total Time**: 4-5 hours
**Start After**: Agent A completes SearchServiceInterface

