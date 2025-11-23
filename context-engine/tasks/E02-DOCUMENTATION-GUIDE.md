# E02 User & Profile Management - Documentation Guide

## Epic Context
E02 focuses on user profiles, privacy settings, and user discovery. This epic builds on E01's completed database foundation, specifically the `users` and `follows` tables.

## Completed E01 Foundation

### Database Tables Available
- **users**: id (uuid), name, email, password, bio, interests (json), location_name, location_coordinates (geography), profile_image_url, is_host, email_verified_at, remember_token, timestamps
- **follows**: id, follower_id, following_id, timestamps

### Models Available
- **User**: `app/Models/User.php` - includes profile fields, spatial location, interests
- **Follow**: `app/Models/Follow.php` - follower/following relationships

### Filament Resources Available
- **UserResource**: `app/Filament/Resources/UserResource.php` - basic CRUD for users

---

## F01: Profile Creation & Management

### Feature Purpose
Enable users to create, customize, and manage their profiles with rich content, privacy controls, and social features. This is the foundation for user identity across the platform.

### Key Components to Document
1. **Profile Data Management**: Enhance UserResource with profile fields (bio, interests, location)
2. **Profile Image Upload**: Laravel filesystem integration (not Supabase Storage)
3. **Profile Completion Tracking**: Gamification and progress indicators
4. **Interest Management**: JSON field management in Filament forms
5. **Location Management**: PostGIS integration for user location

### Suggested Tasks (5-7 tasks, 30-40 hours total)
- **T01**: Enhance Filament UserResource with profile fields (3-4 hours)
- **T02**: Implement profile image upload with Laravel filesystem (4-5 hours)
- **T03**: Build profile completion tracking service (3-4 hours)
- **T04**: Create interest management Livewire component (4-5 hours)
- **T05**: Implement location picker with PostGIS integration (5-6 hours)
- **T06**: Build profile view/edit Livewire components (5-6 hours)
- **T07**: Create profile policies and tests (3-4 hours)

### Integration Points
- Uses `users` table from E01
- Integrates with E01 authentication system
- Uses PostGIS for location storage (matanyadaev/laravel-eloquent-spatial)
- Filament forms for admin profile management

### Critical Notes
- Profile fields already exist in users table (bio, interests, location_coordinates, profile_image_url)
- Use Laravel's filesystem for image storage (config/filesystems.php)
- Interests stored as JSON array in database
- Location stored as PostGIS geography point

---

## F02: Privacy Settings

### Feature Purpose
Provide granular privacy controls for user profiles, activity visibility, and notification preferences. Ensures users have control over their data and visibility.

### Key Components to Document
1. **Privacy Policy System**: Laravel policies for profile visibility
2. **Privacy Settings UI**: Filament forms for privacy configuration
3. **Visibility Controls**: Database-level privacy enforcement
4. **Notification Preferences**: Integration with E01 notifications table
5. **Blocked Users**: Relationship management and enforcement

### Suggested Tasks (5-6 tasks, 25-35 hours total)
- **T01**: Create privacy settings migration (add columns to users table) (2-3 hours)
- **T02**: Build ProfilePolicy with visibility rules (3-4 hours)
- **T03**: Create privacy settings Filament form (3-4 hours)
- **T04**: Implement notification preferences service (4-5 hours)
- **T05**: Build blocked users management (4-5 hours)
- **T06**: Create privacy enforcement middleware (3-4 hours)
- **T07**: Write comprehensive privacy tests (3-4 hours)

### Integration Points
- Extends `users` table with privacy columns
- Uses Laravel policies for authorization
- Integrates with E01 notifications system
- May need `blocked_users` pivot table

### Critical Notes
- Privacy should be enforced at query level (global scopes)
- Use Laravel policies for authorization checks
- Notification preferences stored in users table or separate table
- Consider GDPR compliance requirements

---

## F03: User Discovery & Search

### Feature Purpose
Enable users to discover other users based on location, interests, and social connections. Uses PostGIS for proximity-based discovery.

### Key Components to Document
1. **Location-Based Discovery**: PostGIS spatial queries for nearby users
2. **Interest Matching**: JSON field queries for interest overlap
3. **Search Functionality**: Laravel Scout integration (optional)
4. **Social Graph Queries**: Efficient follower/following queries
5. **Discovery Feed**: Livewire component for user discovery

### Suggested Tasks (5-7 tasks, 30-40 hours total)
- **T01**: Create UserDiscoveryService with PostGIS queries (5-6 hours)
- **T02**: Build interest matching algorithm (3-4 hours)
- **T03**: Implement user search with Laravel Scout (optional) (4-5 hours)
- **T04**: Create discovery feed Livewire component (5-6 hours)
- **T05**: Build social graph query optimization (3-4 hours)
- **T06**: Create discovery settings Filament resource (3-4 hours)
- **T07**: Write discovery tests and performance optimization (4-5 hours)

### Integration Points
- Uses `users` table with PostGIS location_coordinates
- Uses `follows` table for social graph
- Queries interests JSON field
- May integrate with Laravel Scout for full-text search

### Critical Notes
- Use matanyadaev/laravel-eloquent-spatial for spatial queries
- Example query: `User::whereDistance('location_coordinates', $point, '<=', 10000)`
- Interests stored as JSON array - use JSON queries
- Cache discovery results for performance (Redis)
- Consider privacy settings when showing users

---

## Common Patterns for E02

### Database Migrations
```bash
php artisan make:migration add_privacy_columns_to_users_table --no-interaction
php artisan make:migration create_blocked_users_table --no-interaction
```

### Models
```bash
# Usually don't need new models - extend User model
# If needed:
php artisan make:model BlockedUser --no-interaction
```

### Filament Resources
```bash
# Enhance existing UserResource
# Or create new resources:
php artisan make:filament-resource PrivacySetting --generate --no-interaction
```

### Service Classes
```bash
php artisan make:class Services/ProfileService --no-interaction
php artisan make:class Services/UserDiscoveryService --no-interaction
php artisan make:class Services/PrivacyService --no-interaction
```

### Policies
```bash
php artisan make:policy ProfilePolicy --model=User --no-interaction
php artisan make:policy PrivacyPolicy --no-interaction
```

### Livewire Components
```bash
php artisan make:livewire Profile/EditProfile --no-interaction
php artisan make:livewire Profile/ProfileCompletion --no-interaction
php artisan make:livewire Discovery/UserDiscoveryFeed --no-interaction
```

### Tests
```bash
php artisan make:test --pest Feature/ProfileManagementTest --no-interaction
php artisan make:test --pest Feature/PrivacySettingsTest --no-interaction
php artisan make:test --pest Feature/UserDiscoveryTest --no-interaction
```

---

## Key Packages for E02

- **matanyadaev/laravel-eloquent-spatial**: PostGIS integration for location queries
- **laravel/scout** (optional): Full-text search for user discovery
- **intervention/image** (optional): Image processing for profile photos
- **spatie/laravel-medialibrary** (optional): Advanced media management

---

## Testing Checklist for E02

### Profile Management
- [ ] User can update profile fields (bio, interests, location)
- [ ] Profile image upload works correctly
- [ ] Profile completion tracking calculates correctly
- [ ] Location picker saves PostGIS coordinates
- [ ] Profile policies enforce visibility rules

### Privacy Settings
- [ ] Privacy settings can be updated
- [ ] Visibility rules are enforced at query level
- [ ] Blocked users cannot interact
- [ ] Notification preferences are respected
- [ ] Privacy middleware works correctly

### User Discovery
- [ ] Location-based discovery returns nearby users
- [ ] Interest matching works correctly
- [ ] Search functionality returns relevant results
- [ ] Social graph queries are efficient (no N+1)
- [ ] Discovery respects privacy settings

