# E03 Activity Management - Documentation Guide

## Epic Context
E03 focuses on **Events** (structured activities with RSVPs and payments). This epic builds on E01's completed database foundation, specifically the `activities`, `rsvps`, and `tags` tables.

**CRITICAL**: "Activities" in this epic = "Events" in the Posts vs Events dual model. These are structured, planned activities with RSVPs, NOT ephemeral Posts.

## Completed E01 Foundation

### Database Tables Available
- **activities**: id (uuid), user_id, title, description, activity_type, location_name, location_coordinates (geography), start_time, end_time, capacity, is_paid, price, currency, image_url, status, originated_from_post_id (nullable), timestamps
- **rsvps**: id, activity_id, user_id, status (going/interested/not_going), payment_status, payment_amount, attended, timestamps
- **tags**: id, name, slug, category, usage_count, timestamps
- **activity_tag** (pivot): activity_id, tag_id

### Models Available
- **Activity**: `app/Models/Activity.php` - includes spatial location, post conversion tracking
- **Rsvp**: `app/Models/Rsvp.php` - includes payment tracking
- **Tag**: `app/Models/Tag.php` - includes usage count

### Filament Resources Available
- **ActivityResource**: `app/Filament/Resources/ActivityResource.php` - basic CRUD
- **RsvpResource**: `app/Filament/Resources/RsvpResource.php` - basic CRUD
- **TagResource**: `app/Filament/Resources/TagResource.php` - basic CRUD

---

## F01: Activity CRUD Operations

### Feature Purpose
Provide comprehensive CRUD operations for activities (events), including creation, editing, status management, and post-to-event conversion handling. This is the core of the event management system.

### Key Components to Document
1. **Activity Creation**: Enhanced Filament forms with all activity fields
2. **Post-to-Event Conversion**: Handle activities created from posts (originated_from_post_id)
3. **Activity Image Upload**: Laravel filesystem integration
4. **Activity Templates**: Quick creation system for common activity types
5. **Status Management**: Workflow for draft → published → completed → cancelled

### Suggested Tasks (6-7 tasks, 35-45 hours total)
- **T01**: Enhance ActivityResource with complete form fields (4-5 hours)
- **T02**: Implement post-to-event conversion service (5-6 hours)
- **T03**: Build activity image upload with Laravel filesystem (3-4 hours)
- **T04**: Create activity templates system (4-5 hours)
- **T05**: Implement activity status workflow (4-5 hours)
- **T06**: Build activity editing Livewire components (6-7 hours)
- **T07**: Create activity policies and comprehensive tests (4-5 hours)

### Integration Points
- Uses `activities` table from E01
- Integrates with `posts` table for conversion tracking (originated_from_post_id)
- Uses PostGIS for location storage
- **E04 Integration**: E04 initiates post-to-event conversion, E03 creates the activity

### Critical Notes
- **Post-to-Event Conversion**: When E04 detects high engagement on a post, it triggers E03 to create an activity
- The `originated_from_post_id` field tracks which post spawned this activity
- Activity status workflow: draft → published → active → completed → cancelled
- Use Laravel filesystem for image storage
- Location stored as PostGIS geography point

### Post-to-Event Conversion Flow
1. E04 detects post with high engagement (many "I'm down" reactions)
2. E04 calls E03's `ActivityConversionService::createFromPost($post)`
3. E03 creates new activity with `originated_from_post_id = $post->id`
4. E03 notifies post creator to complete activity details
5. E03 tracks conversion in `post_conversions` table

---

## F02: RSVP & Attendance System

### Feature Purpose
Manage RSVPs, capacity limits, waitlists, payment tracking, and attendance verification for activities. Ensures smooth event participation and host management.

### Key Components to Document
1. **RSVP Management**: Enhanced RsvpResource with status tracking
2. **Capacity Management**: Service class for capacity limits and waitlists
3. **Payment Tracking**: Integration with payment fields in rsvps table
4. **Attendance Verification**: Check-in system (QR codes, location-based)
5. **RSVP Notifications**: Integration with E01 notifications

### Suggested Tasks (6-7 tasks, 30-40 hours total)
- **T01**: Enhance RsvpResource with complete functionality (3-4 hours)
- **T02**: Build capacity management service (4-5 hours)
- **T03**: Implement waitlist logic and notifications (4-5 hours)
- **T04**: Create attendance check-in system (5-6 hours)
- **T05**: Build RSVP Livewire components (5-6 hours)
- **T06**: Implement RSVP notifications (3-4 hours)
- **T07**: Create RSVP policies and tests (3-4 hours)

### Integration Points
- Uses `rsvps` table from E01
- Integrates with `activities` table for capacity checks
- Uses E01 notifications for RSVP updates
- May integrate with payment gateway (future)

### Critical Notes
- RSVP status: going, interested, not_going
- Payment status: pending, completed, refunded, failed
- Capacity management: track current RSVPs vs activity capacity
- Waitlist: when capacity reached, add to waitlist
- Attendance tracking: mark `attended = true` on check-in

---

## F03: Tagging & Category System

### Feature Purpose
Provide comprehensive tagging and categorization for activities, enabling discovery, filtering, and organization. Includes tag autocomplete, trending tags, and analytics.

### Key Components to Document
1. **Tag Management**: Enhanced TagResource with usage analytics
2. **Tag Autocomplete**: Livewire component for tag suggestions
3. **Category Hierarchy**: Organize tags into categories
4. **Trending Tags**: Analytics for popular tags
5. **Tag-Based Discovery**: Integration with E04 discovery engine

### Suggested Tasks (5-6 tasks, 25-35 hours total)
- **T01**: Enhance TagResource with analytics (3-4 hours)
- **T02**: Build tag autocomplete Livewire component (4-5 hours)
- **T03**: Implement category hierarchy system (3-4 hours)
- **T04**: Create trending tags analytics (4-5 hours)
- **T05**: Build tag management Livewire components (4-5 hours)
- **T06**: Create tag policies and tests (3-4 hours)

### Integration Points
- Uses `tags` table from E01
- Uses `activity_tag` pivot table
- Integrates with E04 for tag-based discovery
- May integrate with E02 for user interest matching

### Critical Notes
- Tags have `usage_count` field - increment on activity creation
- Tags have `category` field for organization (e.g., "sports", "food", "music")
- Use Livewire for real-time tag autocomplete
- Cache trending tags for performance (Redis)
- Consider tag moderation for inappropriate tags

---

## Common Patterns for E03

### Database Migrations
```bash
# Usually don't need new migrations - tables exist
# If needed for enhancements:
php artisan make:migration add_waitlist_to_rsvps_table --no-interaction
php artisan make:migration add_check_in_code_to_activities_table --no-interaction
```

### Service Classes
```bash
php artisan make:class Services/ActivityService --no-interaction
php artisan make:class Services/ActivityConversionService --no-interaction
php artisan make:class Services/RsvpService --no-interaction
php artisan make:class Services/CapacityService --no-interaction
php artisan make:class Services/AttendanceService --no-interaction
php artisan make:class Services/TagService --no-interaction
```

### Policies
```bash
php artisan make:policy ActivityPolicy --model=Activity --no-interaction
php artisan make:policy RsvpPolicy --model=Rsvp --no-interaction
php artisan make:policy TagPolicy --model=Tag --no-interaction
```

### Livewire Components
```bash
php artisan make:livewire Activity/CreateActivity --no-interaction
php artisan make:livewire Activity/EditActivity --no-interaction
php artisan make:livewire Activity/ActivityTemplates --no-interaction
php artisan make:livewire Rsvp/RsvpButton --no-interaction
php artisan make:livewire Rsvp/AttendanceCheckIn --no-interaction
php artisan make:livewire Tags/TagAutocomplete --no-interaction
```

### Jobs (for async processing)
```bash
php artisan make:job ProcessActivityConversion --no-interaction
php artisan make:job SendRsvpNotifications --no-interaction
php artisan make:job UpdateTagUsageCount --no-interaction
```

### Tests
```bash
php artisan make:test --pest Feature/ActivityCrudTest --no-interaction
php artisan make:test --pest Feature/PostToEventConversionTest --no-interaction
php artisan make:test --pest Feature/RsvpManagementTest --no-interaction
php artisan make:test --pest Feature/CapacityManagementTest --no-interaction
php artisan make:test --pest Feature/TaggingSystemTest --no-interaction
```

---

## Key Packages for E03

- **matanyadaev/laravel-eloquent-spatial**: PostGIS integration for activity locations
- **spatie/laravel-qr-code** (optional): QR code generation for check-in
- **intervention/image** (optional): Image processing for activity photos
- **spatie/laravel-medialibrary** (optional): Advanced media management

---

## Testing Checklist for E03

### Activity CRUD
- [ ] Activity can be created with all fields
- [ ] Post-to-event conversion creates activity correctly
- [ ] Activity image upload works
- [ ] Activity templates create activities correctly
- [ ] Activity status workflow transitions correctly
- [ ] Activity policies enforce permissions

### RSVP & Attendance
- [ ] RSVP can be created/updated/deleted
- [ ] Capacity limits are enforced
- [ ] Waitlist logic works correctly
- [ ] Attendance check-in marks attended
- [ ] RSVP notifications are sent
- [ ] Payment tracking works correctly

### Tagging & Categories
- [ ] Tags can be created/updated/deleted
- [ ] Tag autocomplete returns relevant suggestions
- [ ] Category hierarchy works correctly
- [ ] Trending tags are calculated correctly
- [ ] Tag usage count increments correctly
- [ ] Tag-based discovery works

