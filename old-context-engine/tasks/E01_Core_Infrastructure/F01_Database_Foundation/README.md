# F01 Database Foundation

## Feature Overview

This feature establishes the core database foundation for the FunLynk platform using Laravel 12, PostgreSQL with PostGIS, and Eloquent ORM. It implements the complete database schema via Laravel migrations, defines Eloquent models with relationships, and creates Filament v4 resources for administrative data management. This foundation supports the dual **Posts vs Events** content model, which is critical for the platform's spontaneous social discovery capabilities.

**Key Architecture**:
- **Posts**: Ephemeral content (24-48h lifespan) for spontaneous discovery
- **Events**: Structured activities with RSVPs and payments
- **Conversion**: Posts can evolve into events based on traction

## Feature Scope

### In Scope
- **Database Migrations**: Complete schema implementation using Laravel migrations for all tables
- **PostGIS Integration**: Geospatial extension setup and configuration for location-based queries
- **Eloquent Models**: Full model definitions with relationships, casts, and accessors
- **Model Factories**: Factory definitions for all models to support testing and seeding
- **Database Seeders**: Initial data population for development and testing
- **Filament Resources**: Basic CRUD interfaces for core entities (Users, Posts, Activities)
- **Database Triggers**: Laravel-based triggers for counter updates and timestamps
- **Performance Indexes**: Comprehensive indexing strategy for geospatial and standard queries

### Out of Scope
- **Authorization Policies**: Laravel policies for access control (handled in E02 User Management)
- **Business Logic**: Service layers and controllers (handled by feature-specific epics)
- **API Endpoints**: RESTful API implementation (handled by E02-E06)
- **Frontend Components**: Livewire components beyond Filament resources (handled by feature epics)
- **Advanced Analytics**: Reporting and analytics queries (handled by E07 Administration)
- **Payment Integration**: Stripe Connect setup (handled by E06 Payments)

## Tasks Breakdown

### T01: Database Configuration & PostGIS Setup
**Estimated Time**: 2-3 hours
**Dependencies**: None
**Artisan Commands**:
```bash
# Configure database connection in .env
php artisan config:clear

# Enable PostGIS extension (run in PostgreSQL)
CREATE EXTENSION IF NOT EXISTS postgis;

# Install PostGIS package for Laravel
composer require matanyadaev/laravel-eloquent-spatial
```

**Description**: Configure Laravel 12 database connection to PostgreSQL. Enable PostGIS extension on the database server. Install and configure `matanyadaev/laravel-eloquent-spatial` package for geospatial data types in Eloquent. Verify PostGIS functions are accessible from Laravel.

**Deliverables**:
- `.env` configured with PostgreSQL credentials
- PostGIS extension enabled in database
- `config/database.php` configured for spatial data types
- Verification test confirming PostGIS functionality

---

### T02: Core Tables Migration (Users, Posts, Activities)
**Estimated Time**: 6-8 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:migration create_users_table --no-interaction
php artisan make:migration create_posts_table --no-interaction
php artisan make:migration create_activities_table --no-interaction
php artisan make:migration create_post_reactions_table --no-interaction
php artisan make:migration create_post_conversions_table --no-interaction
```

**Description**: Create Laravel migrations for the core content tables: `users`, `posts`, `post_reactions`, `post_conversions`, and `activities`. Implement PostGIS `GEOGRAPHY(POINT, 4326)` columns for location data. Define foreign key constraints, check constraints, and unique constraints. Include fields for Post-to-Event conversion tracking (`originated_from_post_id` in activities, `evolved_to_event_id` in posts).

**Key Schema Elements**:
- **users**: Profile data, location, Stripe account fields, counters
- **posts**: Ephemeral content with expiration, location, mood, tags, conversion tracking
- **post_reactions**: "I'm down" / "Join me" interactions
- **post_conversions**: Post-to-event evolution metrics
- **activities**: Structured events with RSVPs, payments, post origin tracking

**Deliverables**:
- 5 migration files with complete table definitions
- Foreign key relationships properly defined
- Check constraints for business rules (e.g., paid activities must have price > 0)
- Geospatial columns using PostGIS types

---

### T03: Social & Engagement Tables Migration
**Estimated Time**: 4-5 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:migration create_follows_table --no-interaction
php artisan make:migration create_rsvps_table --no-interaction
php artisan make:migration create_comments_table --no-interaction
php artisan make:migration create_tags_table --no-interaction
php artisan make:migration create_notifications_table --no-interaction
php artisan make:migration create_flares_table --no-interaction
php artisan make:migration create_reports_table --no-interaction
```

**Description**: Create migrations for social graph, engagement, and system tables: `follows`, `rsvps`, `comments`, `tags`, `notifications`, `flares`, and `reports`. Implement proper foreign key cascades, unique constraints, and self-referencing relationships (e.g., comment replies).

**Key Schema Elements**:
- **follows**: Social graph with no-self-follow constraint
- **rsvps**: Event attendance with payment tracking (Stripe PaymentIntent IDs)
- **comments**: Threaded discussions with soft deletes
- **tags**: Categorization system with usage tracking
- **notifications**: Multi-channel delivery tracking (push, email, in-app)
- **flares**: Activity inquiries with location and conversion tracking
- **reports**: Content moderation with polymorphic target relationships

**Deliverables**:
- 7 migration files with complete table definitions
- Unique constraints preventing duplicate relationships
- JSONB columns for flexible data storage (notifications.data)
- Array columns for tags and interests

---

### T04: Database Indexes & Performance Optimization
**Estimated Time**: 3-4 hours
**Dependencies**: T02, T03
**Artisan Commands**:
```bash
php artisan make:migration add_indexes_to_core_tables --no-interaction
```

**Description**: Create a comprehensive indexing migration covering all performance-critical queries. Implement GIST indexes for geospatial columns, GIN indexes for array/JSONB columns, and B-tree indexes for foreign keys and frequently queried columns. Add partial indexes for boolean flags.

**Index Categories**:
- **Geospatial Indexes**: GIST indexes on all `location_coordinates` columns
- **Array Indexes**: GIN indexes on `tags`, `interests`, `images` arrays
- **Foreign Key Indexes**: B-tree indexes on all foreign key columns
- **Temporal Indexes**: Indexes on `created_at`, `start_time`, `expires_at`
- **Partial Indexes**: Conditional indexes for `is_host`, `is_public`, `status = 'active'`
- **Composite Indexes**: Multi-column indexes for common query patterns

**Deliverables**:
- Single migration file with all index definitions
- Comments documenting the purpose of each index
- Performance testing queries to validate index usage

---

### T05: Eloquent Models & Relationships
**Estimated Time**: 8-10 hours
**Dependencies**: T02, T03
**Artisan Commands**:
```bash
php artisan make:model User --no-interaction
php artisan make:model Post --no-interaction
php artisan make:model PostReaction --no-interaction
php artisan make:model PostConversion --no-interaction
php artisan make:model Activity --no-interaction
php artisan make:model Follow --no-interaction
php artisan make:model Rsvp --no-interaction
php artisan make:model Comment --no-interaction
php artisan make:model Tag --no-interaction
php artisan make:model Notification --no-interaction
php artisan make:model Flare --no-interaction
php artisan make:model Report --no-interaction
```

**Description**: Create Eloquent models for all database tables. Define relationships (hasMany, belongsTo, belongsToMany), casts for JSONB/array fields, accessors/mutators for computed properties, and scopes for common queries. Implement geospatial attribute handling using the spatial package.

**Key Relationships**:
- **User**: hasMany(Post, Activity, Rsvp, Comment, Notification, Flare, Report)
- **Post**: belongsTo(User), hasMany(PostReaction), hasOne(PostConversion), belongsTo(Activity as 'evolvedToEvent')
- **Activity**: belongsTo(User as 'host'), hasMany(Rsvp, Comment), belongsTo(Post as 'originatedFromPost')
- **Follow**: belongsTo(User as 'follower'), belongsTo(User as 'following')
- **Comment**: belongsTo(Activity, User), hasMany(Comment as 'replies')

**Model Features**:
- **Casts**: JSONB to array, dates to Carbon, spatial columns to Point
- **Scopes**: `active()`, `expired()`, `public()`, `nearby($lat, $lng, $radius)`
- **Accessors**: `isExpired`, `isActive`, `distanceFrom($point)`
- **Mutators**: Automatic geohash generation for posts
- **Observers**: Counter updates, timestamp management

**Deliverables**:
- 12 Eloquent model files with complete relationship definitions
- Proper use of `casts()` method (Laravel 12 convention)
- Geospatial query scopes for location-based filtering
- PHPDoc blocks for IDE autocomplete

---

### T06: Model Factories & Database Seeders
**Estimated Time**: 5-6 hours
**Dependencies**: T05
**Artisan Commands**:
```bash
php artisan make:factory UserFactory --no-interaction
php artisan make:factory PostFactory --no-interaction
php artisan make:factory ActivityFactory --no-interaction
php artisan make:factory PostReactionFactory --no-interaction
php artisan make:factory RsvpFactory --no-interaction
php artisan make:factory CommentFactory --no-interaction
php artisan make:factory TagFactory --no-interaction
php artisan make:seeder DatabaseSeeder --no-interaction
```

**Description**: Create model factories for all core models to support testing and development. Implement database seeders to populate realistic test data including users, posts, activities, reactions, RSVPs, and social relationships. Include geospatial data generation for location-based testing.

**Factory Features**:
- **Realistic Data**: Use Faker for names, emails, descriptions, locations
- **Geospatial Data**: Generate coordinates within realistic bounds (e.g., San Francisco Bay Area)
- **Relationships**: Factory states for creating related records (e.g., `withPosts()`, `withActivities()`)
- **Temporal Data**: Generate posts with varying expiration times, activities with future dates
- **Conversion Scenarios**: Factory states for posts that evolved into events

**Seeder Strategy**:
- 50 users with varied profiles (hosts, regular users, verified users)
- 100 posts (mix of active and expired, with/without location)
- 30 activities (mix of free/paid, public/private, with/without post origin)
- 200 post reactions across various posts
- 50 RSVPs across various activities
- 20 follows relationships creating a social graph
- 10 tags with usage counts

**Deliverables**:
- Factory files for all core models
- `DatabaseSeeder` with comprehensive test data
- Seeder can be run multiple times idempotently
- Geospatial data covers realistic geographic area

---

### T07: Filament Resources for Core Entities
**Estimated Time**: 6-8 hours
**Dependencies**: T05
**Artisan Commands**:
```bash
php artisan make:filament-resource User --generate --no-interaction
php artisan make:filament-resource Post --generate --no-interaction
php artisan make:filament-resource Activity --generate --no-interaction
php artisan make:filament-resource PostReaction --generate --no-interaction
php artisan make:filament-resource Rsvp --generate --no-interaction
php artisan make:filament-resource Comment --generate --no-interaction
php artisan make:filament-resource Tag --generate --no-interaction
```

**Description**: Generate Filament v4 resources for core models to provide an administrative interface for data management. Implement forms with proper field types, tables with filters and actions, and relationship managers for nested data. Configure geospatial field display and editing.

**Resource Features**:
- **Forms**: Use Filament form components (TextInput, Textarea, Select, DateTimePicker, etc.)
- **Tables**: Configure columns, filters, actions (view, edit, delete)
- **Relationships**: Use relationship() method for selects and repeaters
- **Geospatial Fields**: Custom fields or text inputs for lat/lng coordinates
- **Validation**: Form validation rules matching database constraints
- **Bulk Actions**: Enable bulk delete, bulk status updates

**Key Resources**:
- **UserResource**: Profile management, host status, Stripe account linking
- **PostResource**: Content moderation, expiration management, conversion tracking
- **ActivityResource**: Event management, RSVP tracking, payment status
- **PostReactionResource**: Reaction analytics, user engagement tracking
- **RsvpResource**: Attendance management, payment verification
- **CommentResource**: Content moderation, threaded view
- **TagResource**: Tag management, usage statistics

**Deliverables**:
- 7 Filament resource files with forms and tables
- Proper use of Filament v4 conventions (`->components([])` instead of `->schema([])`)
- Filters for common queries (status, date ranges, location)
- Actions for common operations (approve, reject, convert post to event)

---

## Success Criteria

### Database Schema
- [ ] All 12 tables are created via Laravel migrations with proper structure
- [ ] PostGIS extension is enabled and geospatial columns are functional
- [ ] Foreign key constraints maintain referential integrity
- [ ] Check constraints enforce business rules (e.g., paid activities have price > 0)
- [ ] Unique constraints prevent duplicate relationships (e.g., user can't follow same user twice)

### Performance & Indexing
- [ ] GIST indexes are applied to all geospatial columns
- [ ] GIN indexes are applied to all array and JSONB columns
- [ ] B-tree indexes are applied to all foreign keys and frequently queried columns
- [ ] Partial indexes optimize boolean flag queries
- [ ] Query performance meets targets (<100ms for typical location-based queries)

### Eloquent Models
- [ ] All 12 models are defined with proper relationships
- [ ] Casts are configured for JSONB, arrays, and spatial data
- [ ] Query scopes are implemented for common filters (active, nearby, public)
- [ ] Accessors provide computed properties (isExpired, distanceFrom)
- [ ] Models follow Laravel 12 conventions (casts() method, not $casts property)

### Factories & Seeders
- [ ] Factories generate realistic test data for all core models
- [ ] Geospatial data is generated within realistic bounds
- [ ] Seeders populate database with comprehensive test data
- [ ] Seeders can be run multiple times without errors
- [ ] Test data includes Post-to-Event conversion scenarios

### Filament Resources
- [ ] Resources are functional for User, Post, Activity, PostReaction, Rsvp, Comment, Tag
- [ ] Forms allow creating and editing records with proper validation
- [ ] Tables display records with filters and actions
- [ ] Relationship fields use relationship() method correctly
- [ ] Resources follow Filament v4 conventions

### Posts vs Events Architecture
- [ ] Posts table includes expiration, mood, and conversion tracking fields
- [ ] Activities table includes post origin tracking fields
- [ ] Post reactions table supports "I'm down" / "Join me" interactions
- [ ] Post conversions table tracks evolution metrics
- [ ] Models support querying posts that evolved into events and vice versa

## Dependencies

### Blocks
- **E02 User Management**: Requires `users` table, User model, and authentication setup
- **E03 Activity Management**: Requires `activities`, `rsvps`, `comments` tables and models
- **E04 Discovery Engine**: Requires `posts`, `post_reactions`, `post_conversions` tables and models
- **E05 Social Features**: Requires `follows`, `comments`, `notifications` tables and models
- **E06 Payments**: Requires Stripe account fields in `users`, payment fields in `activities` and `rsvps`
- **E07 Administration**: Requires all tables and Filament resources for admin dashboard

### External Dependencies
- **PostgreSQL 14+**: Database server with PostGIS support
- **PostGIS 3.0+**: Geospatial extension for location-based queries
- **Laravel 12**: Framework with Eloquent ORM and migration system
- **Filament v4**: Admin panel framework for CRUD interfaces
- **matanyadaev/laravel-eloquent-spatial**: Package for PostGIS integration with Eloquent

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method instead of `$casts` property in models
- Use `php artisan make:` commands with `--no-interaction` flag for automation
- Middleware and exception handling configured in `bootstrap/app.php`
- Commands auto-register from `app/Console/Commands/`

### PostGIS Integration
- Use `matanyadaev/laravel-eloquent-spatial` for spatial data types
- Define columns as `$table->geography('location_coordinates', 'point', 4326)`
- Cast spatial columns to `Point::class` in models
- Use spatial query scopes: `whereDistance('location_coordinates', $point, '<=', $radius)`

### UUID Primary Keys
- All tables use UUID primary keys: `$table->uuid('id')->primary()`
- Foreign keys reference UUIDs: `$table->foreignUuid('user_id')->constrained()->cascadeOnDelete()`
- Models should use `HasUuids` trait or configure `$keyType = 'string'` and `$incrementing = false`

### Database Triggers & Observers
- Use Laravel model observers instead of database triggers where possible
- Implement observers for counter updates (follower_count, reaction_count, etc.)
- Use Eloquent events (creating, created, updating, updated, deleting, deleted)
- Database triggers only for performance-critical operations

### Testing Considerations
- Factories should generate data that passes all validation rules
- Use `RefreshDatabase` trait in tests to reset database state
- Seed database before running feature tests
- Test geospatial queries with known coordinates and distances

### Performance Optimization
- Eager load relationships to prevent N+1 queries: `Post::with('user', 'reactions')->get()`
- Use query builder for complex aggregations instead of Eloquent collections
- Limit eager loading: `Post::with('reactions')->latest()->limit(10)->get()`
- Use database transactions for multi-step operations

---

**Feature Status**: ✅ Ready for Implementation
**Priority**: P0 (Highest - blocks all other development)
**Epic**: E01 Core Infrastructure
**Estimated Total Time**: 34-44 hours
**Dependencies**: None (foundation feature)