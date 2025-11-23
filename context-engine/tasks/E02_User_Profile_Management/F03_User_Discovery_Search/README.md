# F03 User Discovery & Search

## Feature Overview

Enable users to discover other users using proximity (PostGIS), shared interests, and social graph. Built with Laravel 12 services, Livewire UI, and Filament v4 admin. Builds on E01 `users`, `follows` and E02/F01 profile fields.

## Feature Scope

### In Scope
- **Nearby discovery**: Spatial queries on `users.location_coordinates`
- **Interest matching**: JSON overlap on `users.interests`
- **Search**: Name/keywords; optional Laravel Scout
- **Discovery feed UI**: Livewire component with filters
- **Admin settings**: Filament resource for discovery configuration

### Out of Scope
- Post/Event feeds (E04)
- RSVP logic (E03)
- Messaging/comments (E05)

## Tasks Breakdown

### T01: UserDiscoveryService with PostGIS
**Estimated Time**: 5-6 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:class Services/UserDiscoveryService --no-interaction
php artisan make:test --pest Feature/UserDiscoveryServiceTest --no-interaction
```
**Description**: Implement spatial queries for nearby users with privacy-aware filters.
**Key Implementation Details**:
- `whereDistance('location_coordinates', $point, '<=', $radius)`
- Respect privacy scopes from E02/F02
- Limit eager loading (`latest()->limit(10)`) per Laravel 12 guidance
**Deliverables**:
- [ ] Service with `nearby()` and `search()` methods
- [ ] Unit tests with seeded data

---

### T02: Interest Matching Algorithm
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:test --pest Unit/InterestMatchingTest --no-interaction
```
**Description**: Score users by Jaccard similarity on interests JSON arrays.
**Deliverables**:
- [ ] Helper for similarity scoring
- [ ] Tests for various overlap cases

---

### T03: Optional Scout Search Integration
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan scout:import "App\\Models\\User" --no-interaction || true
```
**Description**: If enabled, integrate Scout for full-text name/username search.
**Deliverables**:
- [ ] Index mapping and searchable fields
- [ ] Fallback to DB search when disabled

---

### T04: Discovery Livewire Component
**Estimated Time**: 5-6 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:livewire Discovery/UserDiscoveryFeed --no-interaction
php artisan make:test --pest Feature/UserDiscoveryFeedTest --no-interaction
```
**Description**: Paginated list with filters (distance, interests, mutuals).
**Key Implementation Details**:
- `wire:loading` states; DaisyUI filters
- Use `wire:key` in loops
- Privacy-aware query inputs
**Deliverables**:
- [ ] Component rendering and pagination
- [ ] Filters wired to service
- [ ] Tests for filters and pagination

---

### T05: Social Graph Optimization
**Estimated Time**: 3-4 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:test --pest Unit/SocialGraphQueriesTest --no-interaction
```
**Description**: Efficient follower/following queries with indexes and eager loading.
**Deliverables**:
- [ ] Optimized queries for mutuals
- [ ] N+1 eliminated via eager loading

---

### T06: Filament Admin Settings
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:filament-resource DiscoverySetting --generate --no-interaction
```
**Description**: Admin controls for default radius, interest weights, toggles.
**Deliverables**:
- [ ] Resource with forms/tables (`->components([])`)
- [ ] Policies and guards

---

### T07: Tests & Performance
**Estimated Time**: 4-5 hours
**Dependencies**: T01–T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/UserDiscoveryPerformanceTest --no-interaction
```
**Description**: Load tests for spatial queries; caching hot results.
**Deliverables**:
- [ ] Passing feature and unit tests
- [ ] Basic caching for frequent queries

## Success Criteria

### Database & Models
- [ ] Spatial queries return expected nearby users
- [ ] Interests JSON queried correctly

### Filament Resources
- [ ] Admin resource functional with policies

### Business Logic & Services
- [ ] DiscoveryService scores and ranks results
- [ ] Privacy enforcement applied

### User Experience
- [ ] Smooth filters and pagination
- [ ] Loading states and empty states handled

### Integration
- [ ] E04 can reuse discovery inputs
- [ ] E02 privacy respected throughout

## Dependencies

### Blocks
- **E04 Discovery Engine**: Needs discovery primitives

### External Dependencies
- **E01 Core**: Users, Follows; PostGIS via spatial package

## Technical Notes

### Laravel 12
- `casts()` usage; `bootstrap/app.php` configuration
- Limit eager loading: `latest()->limit(10)`

### Filament v4
- `->components([])`, `relationship()` selects

### Testing
- Pest v4 + `RefreshDatabase`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P1
**Epic**: E02 User & Profile Management
**Estimated Total Time**: 28-36 hours
**Dependencies**: E01 foundation
