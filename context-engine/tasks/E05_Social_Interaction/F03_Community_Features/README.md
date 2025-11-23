# F03 Community Features

## Feature Overview

Enable auto-generated and manual communities around shared activities, interests, and locations using Laravel 12, Filament v4, and PostGIS. Communities aggregate users with similar interests or geographic proximity, creating persistent social groups that transcend individual activities. Builds on E01's foundation and integrates with E05/F01 comments for community discussions.

**Key Architecture**: Communities use PostGIS for location-based discovery (e.g., "Hiking in San Francisco"). Auto-generation creates communities from activity tags, user interests, and geographic clusters. Members have roles (owner, moderator, member) for community management.

## Feature Scope

### In Scope
- **Community creation**: Auto-generated from activities/interests/locations, plus manual creation
- **Community membership**: Join/leave, member roles (owner, moderator, member)
- **Community discussions**: Use E05/F01 comments with community as commentable type
- **Community discovery**: Search by name, interest tags, location (PostGIS radius)
- **Community moderation**: Member management, content moderation, community settings
- **Community analytics**: Member growth, engagement metrics, activity heat maps

### Out of Scope
- **Community payments**: Paid memberships handled in E06
- **Community events**: Events are separate from communities (linked via tags)
- **Advanced moderation**: AI moderation in E07/F02
- **Community chat rooms**: Real-time chat in E05/F04

## Tasks Breakdown

### T01: Community Database Schema
**Estimated Time**: 3-4 hours
**Dependencies**: None
**Artisan Commands**:
```bash
# Create migrations
php artisan make:migration create_communities_table --no-interaction
php artisan make:migration create_community_members_table --no-interaction
php artisan make:migration create_community_settings_table --no-interaction
```

**Description**: Create database tables for communities with PostGIS location columns, community_members pivot table with roles, and community_settings for privacy/moderation rules.

**Key Implementation Details**:
- `communities` table: `id`, `name`, `slug`, `description`, `type` (interest/location/activity), `location_coordinates` (geography point), `location_name`, `is_auto_generated`, `owner_id`, `member_count`, `created_at`
- `community_members` table: `id`, `community_id`, `user_id`, `role` (owner/moderator/member), `joined_at`
- `community_settings` table: `community_id`, `is_private`, `require_approval`, `allow_posts`, `allow_events`
- Use PostGIS geography(POINT, 4326) for location_coordinates
- Add indexes on slug, location_coordinates, type

**Deliverables**:
- [ ] Migration files for communities, community_members, community_settings
- [ ] PostGIS geography column for location-based communities
- [ ] Role enum on community_members table
- [ ] Unique constraint on (community_id, user_id) in pivot table

---

### T02: Community & CommunityMember Models
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
# Create models
php artisan make:model Community --no-interaction
php artisan make:model CommunityMember --no-interaction
php artisan make:model CommunitySetting --no-interaction

# Create factories
php artisan make:factory CommunityFactory --model=Community --no-interaction
php artisan make:factory CommunityMemberFactory --model=CommunityMember --no-interaction
```

**Description**: Create Eloquent models with relationships to User, Activity, Tag models. Implement spatial casting for location_coordinates and role enums for community_members.

**Key Implementation Details**:
- Use `casts()` method with `location_coordinates => Point::class` (Laravel 12)
- Cast `role` as enum on CommunityMember model
- Relationships: `Community hasMany CommunityMembers`, `Community belongsTo User (owner)`
- Inverse: `User belongsToMany Community through CommunityMembers`
- Implement `isMember($user)`, `isModerator($user)`, `isOwner($user)` helpers
- Sluggable trait for community slugs

**Deliverables**:
- [ ] Community model with PostGIS spatial casting
- [ ] CommunityMember model with role enum
- [ ] CommunitySetting model with privacy rules
- [ ] Helper methods for membership checks
- [ ] Factories for testing

---

### T03: CommunityService with Auto-generation Logic
**Estimated Time**: 6-7 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
# Create service class
php artisan make:class Services/CommunityService --no-interaction

# Create job for auto-generation
php artisan make:job GenerateCommunityJob --no-interaction

# Create tests
php artisan make:test --pest Feature/CommunityServiceTest --no-interaction
```

**Description**: Build service class handling community creation, auto-generation algorithms, membership management, and moderation. Implement logic to detect when a community should be auto-created based on activity clusters.

**Key Implementation Details**:
- `CommunityService`: `createCommunity()`, `joinCommunity()`, `leaveCommunity()`, `updateRole()`, `deleteCommunity()`
- Auto-generation triggers: 5+ activities with same tag + location within 10km in 30 days
- Algorithm detects geographic clusters using PostGIS: `ST_ClusterDBSCAN(location_coordinates, 10000, 3)`
- Generate community names: "Hiking in San Francisco", "Coffee Lovers - Brooklyn"
- Send notifications when users' interests match new communities
- Implement approval workflow for private communities

**Deliverables**:
- [ ] CommunityService with CRUD and membership methods
- [ ] Auto-generation algorithm using PostGIS clustering
- [ ] GenerateCommunityJob for async community creation
- [ ] Community naming logic based on tags + location
- [ ] Tests for all service methods

---

### T04: Filament CommunityResource
**Estimated Time**: 4-5 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
# Create Filament resource
php artisan make:filament-resource Community --generate --no-interaction

# Create custom pages
php artisan make:filament-page ManageCommunityMembers --resource=CommunityResource --type=custom --no-interaction
```

**Description**: Create Filament admin resource for community management with member lists, moderation tools, analytics widgets, and auto-generation controls.

**Key Implementation Details**:
- Use Filament v4 `->components([])` for forms
- Display location on map using Filament map field
- Show member count, activity count, engagement metrics
- Add filters: by type (interest/location/activity), by is_auto_generated, by member count
- Custom page for managing members: promote to moderator, ban, etc.
- Analytics widget: community growth, top communities, engagement rates

**Deliverables**:
- [ ] CommunityResource with spatial field display
- [ ] Custom member management page
- [ ] Bulk actions for community moderation
- [ ] Analytics widgets for community metrics
- [ ] Tests for admin CRUD operations

---

### T05: Livewire Community Components
**Estimated Time**: 7-8 hours
**Dependencies**: T03
**Artisan Commands**:
```bash
# Create Livewire components
php artisan make:livewire Communities/CommunityCard --no-interaction
php artisan make:livewire Communities/CommunityList --no-interaction
php artisan make:livewire Communities/CommunityPage --no-interaction
php artisan make:livewire Communities/JoinButton --no-interaction

# Create tests
php artisan make:test --pest Feature/CommunityComponentsTest --no-interaction
```

**Description**: Build user-facing Livewire components for discovering, joining, and participating in communities. Implement community pages with member lists, discussions (using E05/F01 comments), and activity feeds.

**Key Implementation Details**:
- `CommunityCard`: displays community info, member count, join button
- `CommunityList`: grid of communities with filters (interest, location radius)
- `CommunityPage`: full community view with tabs (about, members, discussions, activities)
- `JoinButton`: handles join/leave with approval workflow for private communities
- Use PostGIS queries for "Communities near you" (5km radius)
- Use DaisyUI and galaxy theme styling
- Integrate with E05/F01 for community discussions

**Deliverables**:
- [ ] CommunityCard component with join functionality
- [ ] CommunityList with PostGIS location filtering
- [ ] CommunityPage with tabbed interface
- [ ] JoinButton with approval workflow
- [ ] Tests for all Livewire interactions

---

### T06: Community Policies & Moderation
**Estimated Time**: 4-5 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
# Create policy
php artisan make:policy CommunityPolicy --model=Community --no-interaction

# Create middleware
php artisan make:middleware CheckCommunityMembership --no-interaction

# Create tests
php artisan make:test --pest Feature/CommunityPolicyTest --no-interaction
```

**Description**: Implement authorization policies controlling who can create, manage, and moderate communities. Integrate with Filament and Livewire components.

**Key Implementation Details**:
- Only members can view private communities
- Only owner/moderators can edit community settings
- Only owner can delete community or change ownership
- Only owner/moderators can ban members
- Implement rate limiting: max 5 communities created per user
- Middleware checks community membership before allowing access

**Deliverables**:
- [ ] CommunityPolicy with role-based authorization
- [ ] CheckCommunityMembership middleware
- [ ] Integration with Livewire components
- [ ] Tests covering all authorization scenarios

---

### T07: Community Discovery & Tests
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T06
**Artisan Commands**:
```bash
# Create additional tests
php artisan make:test --pest Feature/CommunityDiscoveryTest --no-interaction
php artisan make:test --pest Feature/CommunityAutoGenerationTest --no-interaction

# Run all community tests
php artisan test --filter=Community
```

**Description**: Implement community discovery algorithms using PostGIS spatial queries and user interests. Write comprehensive tests for all community features including auto-generation, membership, and authorization.

**Key Implementation Details**:
- Discover communities by PostGIS distance: `whereDistance('location_coordinates', $point, '<=', 5000)`
- Match communities to user interests: intersection of tags
- Score communities by relevance: distance + interest match + member count
- Test auto-generation triggers
- Test spatial queries with various distances
- Test membership workflows (join, leave, approval)
- Test moderation actions

**Deliverables**:
- [ ] Community discovery algorithm with spatial + interest matching
- [ ] Tests for auto-generation logic
- [ ] Tests for spatial discovery queries
- [ ] Tests for membership workflows
- [ ] All tests passing with >80% coverage

---

## Success Criteria

### Functional Requirements
- [ ] Communities auto-generate from activity clusters
- [ ] Users can manually create communities
- [ ] Users can join/leave communities
- [ ] Private communities require approval
- [ ] Owner/moderators can manage members
- [ ] Communities have discussions (via E05/F01)
- [ ] Location-based community discovery works

### Technical Requirements
- [ ] PostGIS geography column for location_coordinates
- [ ] Spatial queries find communities within radius
- [ ] Auto-generation uses DBSCAN clustering algorithm
- [ ] Role-based permissions enforced
- [ ] Member count cached and updated efficiently
- [ ] Community slugs are unique and SEO-friendly

### User Experience Requirements
- [ ] Community discovery intuitive and fast
- [ ] Join/leave process smooth
- [ ] Community pages display relevant info
- [ ] Location-based recommendations accurate
- [ ] Galaxy theme applied to all components
- [ ] Mobile-friendly community interface

### Performance Requirements
- [ ] Spatial queries optimized with PostGIS indexes
- [ ] Community lists load <2 seconds
- [ ] Member counts cached
- [ ] Discovery algorithm runs efficiently

## Dependencies

### Blocks
- **E04 Discovery**: Communities boost content discovery
- **E06 Monetization**: Paid community features

### External Dependencies
- **E01 Core Infrastructure**: `users`, `activities`, `tags` tables
- **E05/F01 Comments**: Community discussions
- **matanyadaev/laravel-eloquent-spatial**: PostGIS spatial queries

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method instead of `$casts` property in models
- Use `php artisan make:` commands with `--no-interaction` flag
- Middleware configured in `bootstrap/app.php`

### Filament v4 Conventions
- Use `->components([])` instead of `->schema([])` in form methods
- Use `relationship()` method for pivot table relationships
- Map fields for displaying PostGIS coordinates

### PostGIS Spatial Queries
```php
// Find communities within 5km
$communities = Community::whereDistance(
    'location_coordinates',
    $userLocation,
    '<=',
    5000
)->get();

// Auto-generate using clustering
DB::select("
    SELECT 
        ST_ClusterDBSCAN(location_coordinates, 10000, 3) OVER () as cluster_id,
        array_agg(id) as activity_ids
    FROM activities
    WHERE created_at > NOW() - INTERVAL '30 days'
");
```

### Community Naming Logic
```php
// Generate community name from tags + location
$tags = $activities->pluck('tags')->flatten()->unique();
$topTag = $tags->sortByDesc('usage_count')->first();
$name = "{$topTag->name} in {$locationName}";
```

### Testing Considerations
- Use Pest v4 for all tests
- Use `RefreshDatabase` trait in feature tests
- Test PostGIS spatial queries with various locations
- Test auto-generation triggers and thresholds
- Run tests with: `php artisan test --filter=Community`

### Performance Optimization
- Cache member counts: `communities.member_count` column updated on join/leave
- Index PostGIS columns: `CREATE INDEX ON communities USING GIST(location_coordinates)`
- Eager load members: `Community::with('members.user')`
- Cache community discovery results (1 hour TTL)

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P2
**Epic**: E05 Social Interaction
**Estimated Total Time**: 31-38 hours
**Dependencies**: E01 foundation complete, E05/F01 comments available
