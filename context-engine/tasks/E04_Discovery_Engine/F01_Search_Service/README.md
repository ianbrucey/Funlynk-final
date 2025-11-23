# F01 Discovery Feed Service (formerly "Search Service")

## Feature Overview

Provide location-aware discovery feeds for Posts and Events: Nearby, For You, and Map View. Uses PostGIS for spatial queries, temporal decay for posts, and Redis caching. Builds on E01 `posts`, `activities`, `post_reactions`.

**Key Architecture**: Different radii: Posts 5–10km; Events 25–50km. Posts are ephemeral (24–48h), events persist.

## Feature Scope

### In Scope
- **Nearby feed**: Proximity queries with PostGIS
- **For You feed**: Personalized blending (interests + social)
- **Map view**: Interactive map with posts/events pins
- **Temporal decay**: Posts decay, events persist until start
- **Caching**: Redis layer for hot queries

### Out of Scope
- Recommendation model details (E04/F02)
- Post-to-event conversion (E04/F03), activity CRUD (E03)

## Tasks Breakdown

### T01: DiscoveryFeedService Foundation
**Estimated Time**: 6-7 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:class Services/DiscoveryFeedService --no-interaction
php artisan make:class Services/NearbyFeedService --no-interaction
php artisan make:test --pest Feature/DiscoveryFeedServiceTest --no-interaction
```
**Description**: Implement base service with composable providers for Nearby, For You, and Map feeds.
**Key Implementation Details**:
- Spatial queries with `whereDistance('location_coordinates', $point, '<=', $radius)`
- Posts radius 5–10km; events radius 25–50km
- Filter expired posts: `where('expires_at', '>', now())`
**Deliverables**:
- [ ] Service classes with unit tests
- [ ] Configurable radii and limits

---

### T02: Temporal Decay Scoring
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:class Services/ScoringService --no-interaction
php artisan make:test --pest Unit/TemporalDecayTest --no-interaction
```
**Description**: Apply decay to post ranking; events use start_time proximity.
**Deliverables**:
- [ ] Scoring helpers and tests

---

### T03: Nearby Feed Livewire Component
**Estimated Time**: 5-6 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:livewire Discovery/NearbyFeed --no-interaction
php artisan make:test --pest Feature/NearbyFeedTest --no-interaction
```
**Description**: Render mixed posts/events; filters for distance, time, tags.
**Deliverables**:
- [ ] Component with pagination and loading states
- [ ] Filters wired to service

---

### T04: For You Feed
**Estimated Time**: 5-6 hours
**Dependencies**: T01, T02
**Artisan Commands**:
```bash
php artisan make:class Services/ForYouFeedService --no-interaction
php artisan make:livewire Discovery/ForYouFeed --no-interaction
```
**Description**: Blend interests (E02), social signals (follows), and recency.
**Deliverables**:
- [ ] Service + component

---

### T05: Map View
**Estimated Time**: 6-7 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:livewire Discovery/MapView --no-interaction
php artisan make:test --pest Feature/MapViewTest --no-interaction
```
**Description**: Map visualization of nearby posts/events with clustering and pin details.
**Deliverables**:
- [ ] Component with map library integration
- [ ] Tests for data endpoints

---

### T06: Redis Caching Strategy
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:job CacheFeedResults --no-interaction
```
**Description**: Cache hot feed results for 5–10 minutes; invalidate on new posts/events.
**Deliverables**:
- [ ] Cache keys and invalidation hooks
- [ ] Basic metrics on hit rate

---

### T07: Tests & Performance
**Estimated Time**: 4-5 hours
**Dependencies**: T01–T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/DiscoveryFeedPerformanceTest --no-interaction
```
**Description**: Load test spatial queries; ensure <200ms typical.
**Deliverables**:
- [ ] Passing load/perf tests

## Success Criteria

### Database & Models
- [ ] Spatial queries return correct results at different radii
- [ ] Posts filtered by expiration

### Filament Resources
- [ ] Admin can view diagnostics/metrics widgets

### Business Logic & Services
- [ ] Nearby/ForYou/Map services return expected items
- [ ] Caching improves p95 latency

### User Experience
- [ ] Smooth scrolling, loading states, empty states

### Integration
- [ ] Uses E02 interests and follows
- [ ] Uses E03 activities for event feed

## Dependencies

### Blocks
- **E04/F02 Recommendation**: Enhances personalization

### External Dependencies
- **E01 Core**: Posts, Activities tables
- **predis/predis**: Redis client

## Technical Notes

### Laravel 12
- Use `casts()` and configure in `bootstrap/app.php`

### Filament v4
- Use `->components([])`; widgets for diagnostics

### PostGIS
- Spatial indexes; `geography(POINT,4326)`; performance tuning

### Testing
- Pest v4 + `RefreshDatabase`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P1
**Epic**: E04 Discovery Engine
**Estimated Total Time**: 35-45 hours
**Dependencies**: E01/E02/E03 ready
