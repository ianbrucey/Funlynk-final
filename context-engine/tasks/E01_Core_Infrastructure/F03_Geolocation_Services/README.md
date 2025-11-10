# F03: Geolocation Services - Feature Overview

## Feature Summary

The Geolocation Services feature provides comprehensive location-based functionality for the FunLynk platform, enabling users to discover posts and events near them, create location-based content, and interact with the platform's spatial features. This feature integrates PostGIS spatial database capabilities with Laravel 12's powerful backend, Eloquent ORM, and modern web mapping libraries.

**Critical Architecture Note**: This feature supports the **Posts vs Events dual model** with different proximity radii:
- **Posts**: Tighter radius (5-10km) for spontaneous, ephemeral discovery
- **Events**: Wider radius (25-50km) for planned activities worth traveling to

## Business Context

### Why This Feature Matters
-   **Core Value Proposition**: Location is fundamental to FunLynk's discovery model for both posts and events
-   **User Experience**: Users need to find spontaneous posts and planned events "near me" efficiently
-   **Content Creation**: Users need to specify where posts and events take place
-   **Discovery Algorithm**: Location with different radii is a primary factor in post/event recommendations
-   **Web Experience**: Essential for web users discovering content on the platform

### Success Metrics
-   Location-based discovery accounts for >70% of user searches
-   Location-based recommendations have >40% engagement rate
-   Average proximity search response time <500ms for both posts and events
-   Location accuracy within 100m for 95% of content
-   User satisfaction with location features >4.2/5
-   Spatial query performance <100ms for typical use cases

## Technical Architecture

### Core Components
1.  **Location Data Management**: Store and manage location data with PostGIS via Laravel Eloquent with spatial query support.
2.  **Geocoding Services**: Convert addresses to coordinates and vice versa using Laravel HTTP client with Google Maps Geocoding API.
3.  **Proximity Search**: Find posts/events within specified distances using Laravel Eloquent spatial queries with PostGIS functions.
4.  **Map Integration**: Interactive web maps (Leaflet.js or Mapbox GL JS) for content discovery and creation via Livewire components.
5.  **Location Privacy**: User privacy controls for location sharing managed by Laravel policies and gates.

### Integration Points
-   **Database**: PostgreSQL with PostGIS extension for spatial queries and GIST indexing.
-   **External APIs**: Google Maps Geocoding API for address resolution.
-   **Frontend**: Livewire v3 components with Leaflet/Mapbox for map display and interaction.
-   **Authentication**: Location permissions and privacy settings managed by Laravel's authentication system.
-   **Posts & Events**: Spatial queries for discovery with different radii (Posts: 5-10km, Events: 25-50km).
-   **Filament**: Admin interface for managing location data and viewing spatial analytics.

## Feature Breakdown

### T01: PostGIS Spatial Database Setup
**Scope**: Configure PostGIS extension and integrate spatial data structures within Laravel 12.
**Effort**: 4-6 hours
**Priority**: P0 (Critical Path)

**Key Components**:
-   PostGIS extension installation and configuration on PostgreSQL server (via Laravel migration or direct SQL).
-   Laravel migrations to add `GEOGRAPHY(POINT, 4326)` columns to `users`, `posts`, and `activities` tables.
-   Creation of GIST spatial indexes for performance on all location columns.
-   Integration of Laravel spatial query package (e.g., `matanyadaev/laravel-eloquent-spatial-query` or `grimzy/laravel-postgis`).
-   Configuration of Eloquent model casts for spatial data types (Point, Geography).
-   Database seeder updates to include sample location data for testing.

**Laravel 12 Considerations**:
-   Use `php artisan make:migration add_spatial_columns_to_tables` to create migrations.
-   Leverage Laravel 12's improved database query builder for spatial operations.
-   Ensure PostGIS functions are accessible via Eloquent query builder.

### T02: Geocoding and Address Resolution
**Scope**: Implement address-to-coordinate conversion and reverse geocoding using Laravel HTTP client.
**Effort**: 6-8 hours
**Priority**: P0 (Critical Path)

**Key Components**:
-   Development of `App\Services\GeolocationService` class with constructor property promotion (PHP 8.4).
-   Integration with Google Maps Geocoding API using Laravel's `Http` facade.
-   Forward geocoding (address → coordinates) for post/event creation and user profile updates.
-   Reverse geocoding (coordinates → address) for displaying human-readable locations.
-   Laravel cache integration for geocoding results (reduce API calls, improve performance).
-   Rate limiting using Laravel's built-in rate limiter for external API calls.
-   Comprehensive error handling with custom exceptions (e.g., `GeocodingException`).
-   Configuration management via `config/services.php` for API keys.

**Service Class Pattern**:
```php
// app/Services/GeolocationService.php
class GeolocationService
{
    public function __construct(
        private readonly Http $http,
        private readonly Cache $cache
    ) {}

    public function geocode(string $address): ?Point
    public function reverseGeocode(float $lat, float $lng): ?string
}
```

### T03: Proximity Search and Spatial Queries
**Scope**: Implement location-based discovery for posts and events using Laravel Eloquent and PostGIS.
**Effort**: 8-12 hours
**Priority**: P0 (Critical Path)

**Key Components**:
-   Development of `App\Services\ProximitySearchService` class for spatial query logic.
-   Implementation of spatial proximity queries using PostGIS functions (`ST_DWithin`, `ST_Distance`, `ST_DistanceSphere`).
-   Eloquent local scopes on `Post` and `Activity` models for location-based filtering.
-   **Dual Model Radii Implementation** (Critical):
    -   **Posts**: `withinRadius()` scope with default 5-10km for spontaneous discovery.
    -   **Events**: `withinRadius()` scope with default 25-50km for planned activities.
    -   Configurable radius parameters via `config/discovery.php`.
-   Geographic bounding box searches for initial map views and viewport-based queries.
-   Query optimization: ensure GIST indexes are used, add `EXPLAIN ANALYZE` tests.
-   Distance calculation and sorting (nearest first) with `orderByDistance()` scope.

**Eloquent Scope Pattern**:
```php
// app/Models/Post.php
public function scopeWithinRadius($query, float $lat, float $lng, float $radiusKm = 10)
{
    return $query->whereRaw(
        'ST_DWithin(location_coordinates, ST_MakePoint(?, ?)::geography, ?)',
        [$lng, $lat, $radiusKm * 1000]
    );
}

// app/Models/Activity.php
public function scopeWithinRadius($query, float $lat, float $lng, float $radiusKm = 50)
{
    // Wider default radius for events
}
```

### T04: Interactive Map Integration
**Scope**: Integrate interactive maps for the web platform using Leaflet.js or Mapbox GL JS.
**Effort**: 10-14 hours
**Priority**: P1 (High)

**Key Components**:
-   Integration of Leaflet.js (recommended for simplicity) or Mapbox GL JS into Livewire v3 components.
-   Creation of `App\Livewire\MapView` component for displaying posts/events on a map.
-   Custom marker rendering for Posts (ephemeral style) vs Events (structured style).
-   Marker clustering using Leaflet.markercluster for performance with many markers.
-   Map interaction handlers: drag to search, click marker for details modal.
-   User controls: navigation, zoom, layer selection (posts only, events only, both).
-   Display user's current location using browser Geolocation API.
-   Real-time updates via Livewire wire:poll or Laravel Echo for new posts/events.

**Livewire Component Pattern**:
```php
// app/Livewire/MapView.php
class MapView extends Component
{
    public float $centerLat;
    public float $centerLng;
    public int $zoom = 12;
    public string $filter = 'all'; // 'posts', 'events', 'all'

    public function loadMarkers(): array
    {
        // Return posts and events within viewport
    }
}
```

### T05: Location Privacy and Permissions
**Scope**: Implement location privacy controls and permission management within Laravel.
**Effort**: 4-6 hours
**Priority**: P1 (High)

**Key Components**:
-   User settings table migration for location sharing preferences (public, friends, private).
-   Laravel policy classes (`UserPolicy`, `PostPolicy`, `ActivityPolicy`) for location access control.
-   Granular controls: exact vs. approximate location (fuzzy location within 1km radius).
-   Browser location permission handling (JavaScript prompts, graceful denial handling).
-   Location data retention policies: automatic deletion after X days (configurable).
-   Anonymous location features: allow browsing without sharing personal location.
-   Filament admin panel for managing user location privacy settings and viewing analytics.

**Policy Pattern**:
```php
// app/Policies/PostPolicy.php
public function viewLocation(User $user, Post $post): bool
{
    return match ($post->user->location_privacy) {
        'public' => true,
        'friends' => $user->isFollowing($post->user),
        'private' => $user->id === $post->user_id,
    };
}
```

## Dependencies

### Prerequisites
-   F01: Database Foundation (PostGIS requires PostgreSQL setup with Laravel migrations).
-   F02: Authentication System (location permissions require user accounts and Laravel policies).
-   External API accounts (Google Maps Geocoding API key configured in `.env`).
-   Laravel 12 with PHP 8.4 installed and configured.
-   Composer packages: spatial query support (e.g., `matanyadaev/laravel-eloquent-spatial-query`).

### Dependent Features
-   E03: Activity Management (events require location data with wider radius).
-   E04: Discovery Engine (posts require location data with tighter radius).
-   E05: Social Features (location-based social interactions and discovery).
-   E07: Administration (Filament admin panel for location analytics).

## Technical Specifications

### Database Schema Extensions
```php
// Laravel Migration: database/migrations/xxxx_add_spatial_columns.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Enable PostGIS extension
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        // Add spatial columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->geography('location_coordinates', 'POINT', 4326)->nullable();
            $table->spatialIndex('location_coordinates');
        });

        // Add spatial columns to posts table
        Schema::table('posts', function (Blueprint $table) {
            $table->geography('location_coordinates', 'POINT', 4326)->nullable();
            $table->spatialIndex('location_coordinates');
        });

        // Add spatial columns to activities table
        Schema::table('activities', function (Blueprint $table) {
            $table->geography('location_coordinates', 'POINT', 4326)->nullable();
            $table->spatialIndex('location_coordinates');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('location_coordinates');
        });
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('location_coordinates');
        });
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('location_coordinates');
        });
    }
};
```

### API Endpoints (Laravel Routes)
```php
// routes/api.php

// Location-based discovery (Posts: 5-10km, Events: 25-50km)
Route::get('/posts/nearby', [PostController::class, 'nearby'])
    ->middleware('auth:sanctum');
Route::get('/activities/nearby', [ActivityController::class, 'nearby'])
    ->middleware('auth:sanctum');

// Geocoding services
Route::post('/geocoding/forward', [GeolocationController::class, 'geocode'])
    ->middleware(['auth:sanctum', 'throttle:60,1']);
Route::post('/geocoding/reverse', [GeolocationController::class, 'reverseGeocode'])
    ->middleware(['auth:sanctum', 'throttle:60,1']);

// Location privacy settings
Route::put('/users/location-privacy', [UserLocationController::class, 'updatePrivacy'])
    ->middleware('auth:sanctum');
Route::get('/users/location-settings', [UserLocationController::class, 'getSettings'])
    ->middleware('auth:sanctum');
```

### Performance Requirements
-   **Proximity Search**: <500ms response time for typical radius searches (Posts: 10km, Events: 50km).
-   **Geocoding**: <200ms response time for address resolution (with caching).
-   **Map Loading**: <2s initial map load time with marker clustering.
-   **Spatial Queries**: Support for 100k+ posts/events with sub-second response using GIST indexes.
-   **Concurrent Users**: Handle 1000+ concurrent location requests with Laravel queue workers.
-   **Database**: Spatial queries must use GIST indexes (verify with `EXPLAIN ANALYZE`).

## Security and Privacy

### Location Privacy Controls
-   **Granular Sharing**: Users control who sees their location via Laravel policies (public, friends, private).
-   **Approximate Location**: Option to share fuzzy location (within 1km radius) vs. exact coordinates.
-   **Location History**: User control over location data retention with automatic deletion policies.
-   **Anonymous Mode**: Option to browse posts/events without sharing personal location.
-   **Privacy by Default**: New users default to "friends only" location sharing.

### Security Measures
-   **API Key Protection**: Secure storage of Google Maps API key in `.env` file (never committed to version control).
-   **Rate Limiting**: Laravel's built-in rate limiter on geocoding endpoints (60 requests/minute per user).
-   **Data Encryption**: PostgreSQL encryption at rest for sensitive location data.
-   **Access Controls**: Laravel policies and gates for location-based queries (e.g., `PostPolicy::viewLocation()`).
-   **Input Validation**: Validate latitude/longitude ranges and sanitize address inputs.
-   **CORS Configuration**: Restrict API access to authorized domains only.

## Quality Assurance

### Testing Strategy
-   **Unit Tests (Pest v4)**: Test `GeolocationService`, `ProximitySearchService`, and Eloquent spatial scopes.
    ```php
    // tests/Unit/Services/GeolocationServiceTest.php
    it('geocodes address to coordinates', function () {
        $service = app(GeolocationService::class);
        $point = $service->geocode('1600 Amphitheatre Parkway, Mountain View, CA');
        expect($point)->not->toBeNull()
            ->and($point->latitude)->toBeGreaterThan(37.0)
            ->and($point->longitude)->toBeLessThan(-122.0);
    });
    ```
-   **Feature Tests (Pest v4)**: Test API endpoints for geocoding and proximity search.
    ```php
    // tests/Feature/ProximitySearchTest.php
    it('returns posts within 10km radius', function () {
        $user = User::factory()->create();
        Post::factory()->count(5)->create(['location_coordinates' => /* nearby */]);
        Post::factory()->count(3)->create(['location_coordinates' => /* far away */]);

        $response = $this->actingAs($user)->getJson('/api/posts/nearby?lat=37.4&lng=-122.1&radius=10');

        $response->assertSuccessful()
            ->assertJsonCount(5, 'data');
    });
    ```
-   **Integration Tests**: Mock Google Maps API responses and test error handling.
-   **Performance Tests**: Benchmark spatial queries with 100k+ records using `EXPLAIN ANALYZE`.
-   **Browser Tests (Pest v4)**: Test map functionality and user interactions.
    ```php
    // tests/Browser/MapViewTest.php
    it('displays posts on map', function () {
        $user = User::factory()->create();
        Post::factory()->count(10)->create();

        $page = visit('/map')->actingAs($user);

        $page->assertSee('Map View')
            ->assertNoJavascriptErrors()
            ->waitFor('.leaflet-marker')
            ->assertVisible('.leaflet-marker');
    });
    ```
-   **Privacy Tests**: Verify location sharing controls and policy enforcement.

### Success Criteria
-   [ ] PostGIS extension is installed and spatial indexes are created on all location columns.
-   [ ] Geocoding services work reliably with caching and error handling via Laravel HTTP client.
-   [ ] Proximity search returns accurate results within performance targets:
    -   [ ] Posts: 5-10km radius searches complete in <500ms.
    -   [ ] Events: 25-50km radius searches complete in <500ms.
-   [ ] Interactive maps (Leaflet/Mapbox) function correctly in Livewire components.
-   [ ] Location privacy controls work as specified with Laravel policies.
-   [ ] All Pest tests pass with >80% code coverage for geolocation services.

## Risk Assessment

### High Risk
-   **External API Dependencies**: Google Maps Geocoding API could become unavailable or expensive.
    -   **Mitigation**: Implement caching (Laravel cache), consider fallback providers (OpenStreetMap Nominatim).
-   **Performance Issues**: Spatial queries could be slow with large datasets (100k+ posts/events).
    -   **Mitigation**: GIST indexes, query optimization, database connection pooling, consider read replicas.
-   **Privacy Compliance**: Location data handling must comply with GDPR, CCPA, and other regulations.
    -   **Mitigation**: Clear privacy policies, user consent, data retention policies, right to deletion.

### Medium Risk
-   **Browser Permissions**: Users may deny location permissions in their browser.
    -   **Mitigation**: Graceful fallback to manual location entry, clear permission prompts.
-   **Accuracy Issues**: Geocoding may be inaccurate in some regions (rural areas, new developments).
    -   **Mitigation**: Allow manual coordinate adjustment, display accuracy radius on map.
-   **Cost Management**: Google Maps API costs could escalate with usage.
    -   **Mitigation**: Aggressive caching, usage monitoring, consider self-hosted alternatives.

### Low Risk
-   **Package Compatibility**: Laravel spatial query packages may have breaking changes.
    -   **Mitigation**: Pin package versions, comprehensive test coverage.

### Mitigation Strategies
-   **Multiple Geocoding Providers**: Implement adapter pattern for easy provider switching.
-   **Comprehensive Indexing**: GIST indexes on all spatial columns, monitor query performance.
-   **Clear Privacy Policies**: User-facing privacy controls, transparent data usage.
-   **Graceful Degradation**: Handle permission denials, API failures, and network issues.
-   **Cost Monitoring**: Track API usage via Laravel logs, set up billing alerts.

## Implementation Timeline

### Phase 1: Foundation (Week 1)
-   **T01**: PostGIS Spatial Database Setup (4-6 hours)
    -   Enable PostGIS extension via Laravel migration
    -   Add spatial columns to users, posts, activities tables
    -   Create GIST indexes for performance
    -   Install and configure Laravel spatial query package
-   **T02**: Geocoding and Address Resolution (6-8 hours)
    -   Create `GeolocationService` class with Google Maps API integration
    -   Implement forward and reverse geocoding
    -   Add caching and rate limiting
    -   Write unit tests for geocoding service

### Phase 2: Core Features (Week 2)
-   **T03**: Proximity Search and Spatial Queries (8-12 hours)
    -   Create `ProximitySearchService` class
    -   Implement Eloquent scopes for Posts (5-10km) and Events (25-50km)
    -   Add distance calculation and sorting
    -   Optimize queries with GIST indexes
    -   Write feature tests for proximity search
-   **T04**: Interactive Map Integration (10-14 hours)
    -   Integrate Leaflet.js into Livewire components
    -   Create `MapView` Livewire component
    -   Implement marker rendering and clustering
    -   Add map interaction handlers
    -   Write browser tests for map functionality

### Phase 3: Privacy & Polish (Week 3)
-   **T05**: Location Privacy and Permissions (4-6 hours)
    -   Add location privacy settings to user model
    -   Create Laravel policies for location access control
    -   Implement browser permission handling
    -   Add Filament admin panel for location analytics
    -   Write privacy tests

## Next Steps

1.  **Begin T01**: PostGIS Spatial Database Setup
    -   Run `php artisan make:migration add_spatial_columns_to_tables`
    -   Install Laravel spatial query package: `composer require matanyadaev/laravel-eloquent-spatial-query`
2.  **API Account Setup**: Obtain Google Maps Geocoding API key and add to `.env`
3.  **Performance Planning**: Design spatial query optimization strategy with GIST indexes
4.  **Frontend Integration**: Select Leaflet.js (recommended) and plan Livewire v3 integration
5.  **Configuration**: Create `config/discovery.php` for radius defaults (Posts: 10km, Events: 50km)

---

**Feature**: F03 Geolocation Services
**Epic**: E01 Core Infrastructure
**Stack**: Laravel 12, PHP 8.4, PostGIS, Livewire v3, Leaflet.js
**Total Tasks**: 5 (T01-T05)
**Estimated Effort**: 32-46 hours
**Priority**: P0-P1 (Critical to High)
**Status**: ✅ Ready for Implementation
**Key Architecture**: Posts vs Events dual model with different proximity radii