# Search Implementation Plan - Dual Strategy

**Date**: 2025-11-23 1:00 PM  
**Feature**: Text search for Posts and Events  
**Strategy**: Build both PostgreSQL and Meilisearch implementations with interface-based swapping

---

## 🎯 Architecture Overview

### Interface-Based Design
Both implementations will use the same interface for easy swapping:

```php
interface SearchServiceInterface
{
    /**
     * Search posts and events by text query
     * 
     * @param string $query Search text
     * @param User $user Current user for geo context
     * @param int|null $radius Optional geo filter (km)
     * @param string $contentType 'all', 'posts', 'events'
     * @return Collection Mixed results with type + data
     */
    public function search(
        string $query,
        User $user,
        ?int $radius = null,
        string $contentType = 'all'
    ): Collection;
}
```

### Configuration-Based Swapping
```php
// config/search.php
return [
    'driver' => env('SEARCH_DRIVER', 'database'), // 'database' or 'meilisearch'
];

// Service Provider binding
$this->app->bind(SearchServiceInterface::class, function ($app) {
    $driver = config('search.driver');
    return match($driver) {
        'meilisearch' => $app->make(MeilisearchSearchService::class),
        'database' => $app->make(DatabaseSearchService::class),
        default => $app->make(DatabaseSearchService::class),
    };
});
```

---

## 👤 Agent A (Me) - PostgreSQL Full-Text Search

### Tasks:
1. **Create SearchServiceInterface** (`app/Contracts/SearchServiceInterface.php`)
2. **Create DatabaseSearchService** (`app/Services/DatabaseSearchService.php`)
3. **Create config/search.php** with driver selection
4. **Create SearchServiceProvider** for binding
5. **Create Livewire SearchComponent** (`app/Livewire/Search/SearchPage.php`)
6. **Create search UI** (`resources/views/livewire/search/search-page.blade.php`)
7. **Add search to navbar** (search icon + route)
8. **Write Pest tests** (`tests/Feature/DatabaseSearchServiceTest.php`)

### Implementation Details:

**DatabaseSearchService**:
- Uses PostgreSQL `ILIKE` for case-insensitive search
- Searches: `posts.title`, `posts.description`, `posts.tags`
- Searches: `activities.title`, `activities.description`, `activities.tags`
- Optional geo-proximity filter (reuses FeedService logic)
- Returns mixed collection: `['type' => 'post'|'event', 'data' => Model]`

**Search Query**:
```php
// Posts
Post::active()
    ->where(function($q) use ($query) {
        $q->where('title', 'ILIKE', "%{$query}%")
          ->orWhere('description', 'ILIKE', "%{$query}%")
          ->orWhereRaw("tags::text ILIKE ?", ["%{$query}%"]);
    })
    ->when($radius, fn($q) => $q->nearUser($user->latitude, $user->longitude, $radius * 1000))
    ->get();
```

**UI Features**:
- Search input with live search (debounced)
- Filter by content type (all, posts, events)
- Optional distance filter
- Empty state with suggestions
- Results use existing Post Card component

### Estimated Time: 3-4 hours

---

## 👤 Agent C - Meilisearch Search

### Tasks:
1. **Install Laravel Scout** (`composer require laravel/scout`)
2. **Install Meilisearch Scout Driver** (`composer require meilisearch/meilisearch-php http-interop/http-factory-guzzle`)
3. **Configure Scout** (publish config, set up indexes)
4. **Add Searchable trait to models** (Post, Activity)
5. **Create MeilisearchSearchService** (`app/Services/MeilisearchSearchService.php`)
6. **Import existing data** (`php artisan scout:import`)
7. **Write Pest tests** (`tests/Feature/MeilisearchSearchServiceTest.php`)

### Implementation Details:

**Model Configuration**:
```php
// app/Models/Post.php
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;
    
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'tags' => $this->tags,
            'location_name' => $this->location_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'expires_at' => $this->expires_at?->timestamp,
            'status' => $this->status,
        ];
    }
    
    public function searchableAs(): string
    {
        return 'posts_index';
    }
}

// Same for Activity model
```

**MeilisearchSearchService**:
```php
class MeilisearchSearchService implements SearchServiceInterface
{
    public function search(string $query, User $user, ?int $radius = null, string $contentType = 'all'): Collection
    {
        $results = collect();
        
        // Search posts
        if ($contentType !== 'events') {
            $posts = Post::search($query)
                ->where('status', 'active')
                ->when($radius, function($search) use ($user, $radius) {
                    // Meilisearch geo filter
                    return $search->aroundLatLng($user->latitude, $user->longitude, $radius * 1000);
                })
                ->get()
                ->map(fn($post) => ['type' => 'post', 'data' => $post]);
            
            $results = $results->merge($posts);
        }
        
        // Search events (similar)
        
        return $results;
    }
}
```

**Meilisearch Index Configuration**:
```php
// Configure filterable/sortable attributes
Post::search()->updateSettings([
    'filterableAttributes' => ['status', 'expires_at'],
    'sortableAttributes' => ['created_at', 'expires_at'],
    'searchableAttributes' => ['title', 'description', 'tags'],
]);
```

### Estimated Time: 4-5 hours

---

## 🔗 Integration Points

### Navbar Search Icon
```blade
<!-- Add to navbar between Discover and Create Post -->
<a href="{{ route('search') }}" class="p-3 hover:bg-white/10 rounded-xl">
    <svg class="w-6 h-6"><!-- Search icon --></svg>
</a>
```

### Route
```php
Route::get('/search', \App\Livewire\Search\SearchPage::class)->name('search');
```

### Service Resolution
```php
// In any controller/component
$results = app(SearchServiceInterface::class)->search($query, $user);
```

---

## 🧪 Testing Strategy

Both implementations must pass the same test suite:

```php
it('searches posts by title', function() {
    $post = Post::factory()->create(['title' => 'Basketball Game']);
    $results = app(SearchServiceInterface::class)->search('basketball', auth()->user());
    expect($results)->toHaveCount(1);
});

it('searches events by description', function() { /* ... */ });
it('filters by content type', function() { /* ... */ });
it('filters by geo radius', function() { /* ... */ });
it('returns empty for no matches', function() { /* ... */ });
```

---

## 📊 Performance Comparison

| Feature | DatabaseSearch | MeilisearchSearch |
|---------|---------------|-------------------|
| Setup Time | 3-4 hours | 4-5 hours |
| Infrastructure | None (uses PostgreSQL) | Meilisearch server |
| Typo Tolerance | ❌ No | ✅ Yes |
| Speed (1k records) | ~50-100ms | ~10-20ms |
| Speed (100k records) | ~500ms+ | ~20-50ms |
| Geo Search | ✅ PostGIS | ✅ Built-in |
| Instant Search | ⚠️ Slower | ✅ Fast |
| Fallback | N/A | Can fallback to DB |

---

## 🎯 Success Criteria

- [ ] Both services implement SearchServiceInterface
- [ ] Config-based driver selection works
- [ ] Search page UI with filters
- [ ] Search icon in navbar
- [ ] Both pass same test suite
- [ ] Can switch drivers via .env without code changes
- [ ] Empty states and loading states
- [ ] Galaxy theme styling

---

## 🚀 Deployment Notes

**Development**: Use `SEARCH_DRIVER=database` (no Meilisearch needed)
**Production**: Use `SEARCH_DRIVER=meilisearch` (better performance)
**Fallback**: If Meilisearch fails, catch exception and use DatabaseSearch

---

**Agent A starts now, Agent C starts after Agent A completes the interface!**

