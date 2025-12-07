# F02 Social Sharing & Engagement

## Feature Overview

Enable viral growth and social engagement through external sharing, bookmarking, reactions, and social proof indicators. Built with Laravel 12, Filament v4, and Livewire v3, this feature transforms activities into shareable social experiences. It builds on E01's `post_reactions` table and creates new bookmark and share tracking infrastructure.

**Key Architecture**: Sharing works on both Posts (ephemeral) and Events (structured), using polymorphic relationships. External shares integrate with social platforms via `spatie/laravel-share`, while internal shares create engagement notifications.

## Feature Scope

### In Scope
- **External sharing**: Share activities/posts to social media (Twitter, Facebook, Instagram, WhatsApp)
- **Internal sharing**: Share with followers via notifications and activity feeds
- **Bookmark system**: Save activities/posts with personal notes and collections
- **Reaction system**: Multiple reaction types (like, love, excited) for activities
- **Social proof**: Display indicators showing friends' engagement
- **Share tracking**: Monitor viral growth and share effectiveness

### Out of Scope
- **Comment sharing**: Handled by E05/F01
- **Community sharing**: Handled by E05/F03
- **Direct messages**: Handled by E05/F04
- **Paid promotions**: Handled by E06/F04

## Tasks Breakdown

### T01: Bookmark & Share Database Schema
**Estimated Time**: 2-3 hours
**Dependencies**: None
**Artisan Commands**:
```bash
# Create migrations for new tables
php artisan make:migration create_bookmarks_table --no-interaction
php artisan make:migration create_shares_table --no-interaction
php artisan make:migration create_activity_reactions_table --no-interaction
```

**Description**: Create database tables for bookmarks (polymorphic: posts + activities), shares (tracking internal/external), and activity reactions (E01 has post_reactions, need activity_reactions). Implement polymorphic relationships allowing both posts and activities to be bookmarked and shared.

**Key Implementation Details**:
- `bookmarks` table: `id`, `user_id`, `bookmarkable_type`, `bookmarkable_id`, `note` (text), `collection` (string), `created_at`
- `shares` table: `id`, `user_id`, `shareable_type`, `shareable_id`, `share_type` (external/internal), `platform` (twitter/facebook/etc), `created_at`
- `activity_reactions` table: `id`, `user_id`, `activity_id`, `reaction_type` (like/love/excited), `created_at`
- Use foreign keys with cascading deletes
- Add indexes on polymorphic columns and user_id

**Deliverables**:
- [ ] Migration files for bookmarks, shares, activity_reactions tables
- [ ] Database indexes on polymorphic and foreign key columns
- [ ] Schema compatible with E01's post_reactions table structure
- [ ] Migration tests ensuring constraints work

---

### T02: Bookmark, Share, and Reaction Models
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
# Create models
php artisan make:model Bookmark --no-interaction
php artisan make:model Share --no-interaction
php artisan make:model ActivityReaction --no-interaction

# Create factories for testing
php artisan make:factory BookmarkFactory --model=Bookmark --no-interaction
php artisan make:factory ShareFactory --model=Share --no-interaction
php artisan make:factory ActivityReactionFactory --model=ActivityReaction --no-interaction
```

**Description**: Create Eloquent models with polymorphic relationships to Post and Activity models. Implement `casts()` method for timestamps and enums. Add relationships to User model and inverse relationships on Post/Activity models.

**Key Implementation Details**:
- Use `morphTo()` for polymorphic relationships in Bookmark and Share
- Use `casts()` method with `reaction_type` as enum (Laravel 12)
- Add relationships: `bookmarks()`, `shares()`, `activityReactions()` on User
- Add `bookmarkedBy()`, `sharedBy()` on Post and Activity models
- Implement `isBookmarkedBy($user)` helper methods
- Create factories with realistic test data

**Deliverables**:
- [ ] Bookmark, Share, ActivityReaction models with relationships
- [ ] Polymorphic relationships tested with Post and Activity
- [ ] Helper methods for checking bookmark/share status
- [ ] Factories for all three models

---

### T03: SocialService with Business Logic
**Estimated Time**: 5-6 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
# Create service classes
php artisan make:class Services/BookmarkService --no-interaction
php artisan make:class Services/ShareService --no-interaction
php artisan make:class Services/SocialProofService --no-interaction

# Create tests
php artisan make:test --pest Feature/SocialServiceTest --no-interaction
```

**Description**: Build service classes handling bookmark creation/deletion, share tracking, and social proof calculations. Implement logic for viral tracking, friend engagement detection, and bookmark collections.

**Key Implementation Details**:
- `BookmarkService`: `createBookmark()`, `removeBookmark()`, `getCollections()`, `moveToCollection()`
- `ShareService`: `trackShare()`, `getShareUrl()`, `calculateViralCoefficient()`
- `SocialProofService`: `getFriendsEngaged()`, `getEngagementCount()`, `getTrendingScore()`
- Cache social proof data (5 minute TTL)
- Generate shareable URLs with tracking parameters
- Create notifications for internal shares

**Deliverables**:
- [ ] BookmarkService with CRUD operations
- [ ] ShareService with tracking and URL generation
- [ ] SocialProofService with engagement calculations
- [ ] Tests for all service methods

---

### T04: Filament Resources for Moderation
**Estimated Time**: 3-4 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
# Create Filament resources
php artisan make:filament-resource Bookmark --generate --no-interaction
php artisan make:filament-resource Share --generate --no-interaction
php artisan make:filament-resource ActivityReaction --generate --no-interaction
```

**Description**: Create Filament admin resources for viewing and moderating bookmarks, shares, and reactions. Add analytics widgets showing engagement metrics and viral growth trends.

**Key Implementation Details**:
- Use Filament v4 `->components([])` for forms
- Display polymorphic relationships (bookmarkable/shareable type)
- Add filters: by type (post/activity), by date range, by user
- Create analytics widget: total shares, viral coefficient, top shared content
- Add bulk actions for moderation
- Show engagement heatmaps and trending content

**Deliverables**:
- [ ] BookmarkResource with polymorphic display
- [ ] ShareResource with viral metrics
- [ ] ActivityReactionResource with engagement stats
- [ ] Analytics widgets for admin dashboard

---

### T05: Livewire Sharing Components
**Estimated Time**: 6-7 hours
**Dependencies**: T03
**Artisan Commands**:
```bash
# Create Livewire components
php artisan make:livewire Social/ShareButton --no-interaction
php artisan make:livewire Social/BookmarkButton --no-interaction
php artisan make:livewire Social/ReactionButton --no-interaction
php artisan make:livewire Social/SocialProof --no-interaction

# Create tests
php artisan make:test --pest Feature/SocialComponentsTest --no-interaction
```

**Description**: Build user-facing Livewire components for sharing, bookmarking, and reactions. Implement social share modals, bookmark collections UI, and social proof displays.

**Key Implementation Details**:
- `ShareButton`: opens modal with external platform options (Twitter, Facebook, etc) and internal share
- `BookmarkButton`: toggle bookmark, show collection selector
- `ReactionButton`: toggle reactions (like/love/excited), show count
- `SocialProof`: display "X friends are attending" with avatars
- Use DaisyUI styling with galaxy theme
- Implement optimistic UI updates
- Wire up `spatie/laravel-share` for external platforms

**Deliverables**:
- [ ] ShareButton with modal and platform options
- [ ] BookmarkButton with collection management
- [ ] ReactionButton with multiple reaction types
- [ ] SocialProof component showing friend engagement
- [ ] Tests for all Livewire interactions

---

### T06: External Share Integration
**Estimated Time**: 4-5 hours
**Dependencies**: T03, T05
**Artisan Commands**:
```bash
# Install spatie/laravel-share
composer require spatie/laravel-share

# Create notification for internal shares
php artisan make:notification ActivitySharedNotification --no-interaction
```

**Description**: Integrate `spatie/laravel-share` for external social media sharing. Implement internal share notifications and viral tracking with UTM parameters.

**Key Implementation Details**:
- Configure `spatie/laravel-share` with social platforms
- Generate share URLs with UTM tracking parameters
- Create shareable Open Graph meta tags for activities
- Send notifications when users share internally
- Track share conversions (clicks, signups from shares)
- Generate share analytics reports

**Deliverables**:
- [ ] External share integration with Twitter, Facebook, WhatsApp
- [ ] UTM parameter tracking on share URLs
- [ ] Open Graph meta tags on activity pages
- [ ] Internal share notifications working
- [ ] Share conversion tracking implemented
- [ ] Tests for share workflows

---

### T07: Social Engagement Tests
**Estimated Time**: 3-4 hours
**Dependencies**: T01-T06
**Artisan Commands**:
```bash
# Create comprehensive tests
php artisan make:test --pest Feature/BookmarkTest --no-interaction
php artisan make:test --pest Feature/ShareTrackingTest --no-interaction
php artisan make:test --pest Feature/SocialProofTest --no-interaction

# Run tests
php artisan test --filter=Social
```

**Description**: Write comprehensive Pest tests for all social engagement features. Test polymorphic bookmarks/shares, viral tracking, social proof calculations, and authorization.

**Key Implementation Details**:
- Test bookmark on posts and activities
- Test share tracking and viral coefficient calculation
- Test social proof with friend relationships
- Test reaction toggling and counts
- Test collection management
- Test external share URL generation
- Test authorization (users can only manage own bookmarks)

**Deliverables**:
- [ ] Bookmark tests (CRUD, collections, polymorphic)
- [ ] Share tracking tests (internal, external, viral)
- [ ] Social proof tests (friend engagement, trending)
- [ ] Reaction tests (toggle, count, types)
- [ ] All tests passing with >80% coverage

---

## Success Criteria

### Functional Requirements
- [ ] Users can bookmark both posts and activities
- [ ] Users can organize bookmarks into collections
- [ ] Users can share to external platforms (Twitter, Facebook, etc)
- [ ] Users can share internally to followers
- [ ] Users can react to activities (like, love, excited)
- [ ] Social proof displays friends' engagement
- [ ] Share tracking measures viral growth

### Technical Requirements
- [ ] Polymorphic relationships work for bookmarks and shares
- [ ] Reactions work on both posts (via post_reactions) and activities (via activity_reactions)
- [ ] Social proof calculations cached for performance
- [ ] Share URLs include UTM tracking parameters
- [ ] External share integration with spatie/laravel-share
- [ ] Open Graph meta tags generated for shared content

### User Experience Requirements
- [ ] Bookmark button toggles state instantly (optimistic UI)
- [ ] Share modal shows all platform options clearly
- [ ] Reaction buttons provide visual feedback
- [ ] Social proof displays friends' avatars
- [ ] Collection selector intuitive and fast
- [ ] Galaxy theme applied to all components
- [ ] Mobile-friendly sharing interface

### Performance Requirements
- [ ] Social proof queries optimized with eager loading
- [ ] Bookmark counts cached per user
- [ ] Share tracking doesn't slow page loads
- [ ] Reaction counts updated efficiently

## Dependencies

### Blocks
- **E04 Discovery**: Social proof boosts content in discovery feeds
- **E07 Analytics**: Share metrics feed into platform analytics

### External Dependencies
- **E01 Core Infrastructure**: `post_reactions` table, `notifications` table, `follows` table
- **E02 User Profiles**: Friend relationships for social proof
- **spatie/laravel-share**: External social media sharing

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method instead of `$casts` property in models
- Use `php artisan make:` commands with `--no-interaction` flag
- Middleware configured in `bootstrap/app.php`

### Filament v4 Conventions
- Use `->components([])` instead of `->schema([])` in form methods
- Use `relationship()` method for polymorphic relationships
- Analytics widgets use `StatsOverviewWidget`

### Polymorphic Relationships
```php
// Bookmark model
public function bookmarkable(): MorphTo
{
    return $this->morphTo();
}

// Post model
public function bookmarks(): MorphMany
{
    return $this->morphMany(Bookmark::class, 'bookmarkable');
}

// Activity model
public function bookmarks(): MorphMany
{
    return $this->morphMany(Bookmark::class, 'bookmarkable');
}
```

### Social Proof Calculation
```php
// Get friends who are attending an activity
$friendIds = auth()->user()->follows()->pluck('followed_id');
$friendsEngaged = Activity::find($id)
    ->rsvps()
    ->whereIn('user_id', $friendIds)
    ->with('user')
    ->get();
```

### Share Tracking with UTM
```php
// Generate shareable URL with tracking
$url = route('activities.show', $activity) . '?utm_source=share&utm_medium=social&utm_campaign=user_' . $userId;
```

### Testing Considerations
- Use Pest v4 for all tests
- Use `RefreshDatabase` trait in feature tests
- Test polymorphic relationships thoroughly
- Mock external share services in tests
- Run tests with: `php artisan test --filter=Social`

### Performance Optimization
- Cache social proof data: `Cache::remember("social_proof.{$id}", 300, fn() => ...)`
- Eager load relationships: `Bookmark::with('user', 'bookmarkable')`
- Use database counters for reaction counts
- Index polymorphic columns: `bookmarkable_type`, `bookmarkable_id`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P1
**Epic**: E05 Social Interaction
**Estimated Total Time**: 27-33 hours
**Dependencies**: E01 foundation complete, E02 user profiles available
