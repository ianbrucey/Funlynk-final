# Documentation Validation Checklist

## Purpose
Use this checklist to validate that all 9 README files (E02, E03, E04) have been properly rebuilt to align with Laravel 12 + Filament v4 architecture.

---

## Global Validation (All 9 Files)

### Structure Compliance
- [ ] All files follow LARAVEL-DOCUMENTATION-TEMPLATE.md structure exactly
- [ ] All files have these sections in order:
  - [ ] Feature Overview
  - [ ] Feature Scope (In Scope / Out of Scope)
  - [ ] Tasks Breakdown (T01-T07)
  - [ ] Success Criteria
  - [ ] Dependencies
  - [ ] Technical Notes
  - [ ] Feature Status footer

### Content Quality
- [ ] Feature Overview is 2-4 paragraphs with clear purpose
- [ ] Feature Scope clearly defines what's included and excluded
- [ ] Each feature has 5-7 tasks (not more, not less)
- [ ] Total estimated time per feature is 25-40 hours
- [ ] Each task has: Estimated Time, Dependencies, Artisan Commands, Description, Deliverables
- [ ] Success Criteria has 4-6 categories with specific checkboxes
- [ ] Dependencies section lists E01 prerequisites and blocking features
- [ ] Technical Notes includes Laravel 12 and Filament v4 conventions

### Laravel 12 + Filament v4 Compliance
- [ ] NO React Native references (search for: "React Native", "Expo", "React Navigation")
- [ ] NO Supabase references (search for: "Supabase", "RPC", "Supabase Storage", "Supabase Auth")
- [ ] NO TypeScript references (search for: "TypeScript", "interface", "type", ".ts", ".tsx")
- [ ] NO Firebase references (search for: "Firebase", "FCM", "Firestore")
- [ ] All Artisan commands use `--no-interaction` flag
- [ ] Uses `casts()` method not `$casts` property
- [ ] Uses `->components([])` not `->schema([])` for Filament forms
- [ ] References `bootstrap/app.php` for middleware/exceptions (not separate Kernel files)

### E01 Integration
- [ ] References E01's completed database tables (users, posts, activities, rsvps, tags, follows, etc.)
- [ ] References E01's completed models (User, Post, Activity, Rsvp, Tag, Follow, etc.)
- [ ] References E01's completed Filament resources where applicable
- [ ] Acknowledges that basic Filament resources already exist (for E03, E04)

---

## E02 User & Profile Management Validation

### F01: Profile Creation & Management
- [ ] File exists: `context-engine/tasks/E02_User_Profile_Management/F01_Profile_Creation_Management/README.md`
- [ ] No old T0X subdirectories exist
- [ ] References `users` table with profile fields (bio, interests, location_coordinates, profile_image_url)
- [ ] Includes profile image upload using Laravel filesystem (not Supabase Storage)
- [ ] Includes profile completion tracking and gamification
- [ ] Includes interest management (JSON field in users table)
- [ ] Includes location management with PostGIS integration
- [ ] References matanyadaev/laravel-eloquent-spatial package
- [ ] Estimated time: 30-40 hours total

### F02: Privacy Settings
- [ ] File exists: `context-engine/tasks/E02_User_Profile_Management/F02_Privacy_Settings/README.md`
- [ ] No old T0X subdirectories exist
- [ ] Includes Laravel policies for privacy enforcement
- [ ] Includes Filament forms for privacy configuration
- [ ] Includes database-level privacy controls (visibility columns)
- [ ] Includes notification preferences (integration with E01 notifications table)
- [ ] Includes blocked users management
- [ ] May reference new migration for privacy columns in users table
- [ ] Estimated time: 25-35 hours total

### F03: User Discovery & Search
- [ ] File exists: `context-engine/tasks/E02_User_Profile_Management/F03_User_Discovery_Search/README.md`
- [ ] No old T0X subdirectories exist
- [ ] Includes PostGIS-powered location-based user discovery
- [ ] Includes interest matching using JSON field queries
- [ ] Includes Laravel Scout integration (optional/future enhancement)
- [ ] Includes social graph queries using `follows` table
- [ ] Includes discovery feed Livewire component
- [ ] References matanyadaev/laravel-eloquent-spatial for spatial queries
- [ ] Estimated time: 30-40 hours total

---

## E03 Activity Management Validation

### F01: Activity CRUD Operations
- [ ] File exists: `context-engine/tasks/E03_Activity_Management/F01_Activity_CRUD_Operations/README.md`
- [ ] No old T0X subdirectories exist
- [ ] **CRITICAL**: Documents post-to-event conversion flow (E04 initiates, E03 creates activity)
- [ ] References `activities` table with `originated_from_post_id` field
- [ ] References `post_conversions` table for tracking conversions
- [ ] Includes ActivityResource enhancement (already exists in Filament)
- [ ] Includes activity image upload using Laravel filesystem
- [ ] Includes activity templates system
- [ ] Includes activity status workflow (draft → published → completed → cancelled)
- [ ] Includes ActivityConversionService for post-to-event conversion
- [ ] Estimated time: 35-45 hours total

### F02: RSVP & Attendance System
- [ ] File exists: `context-engine/tasks/E03_Activity_Management/F02_RSVP_Attendance_System/README.md`
- [ ] No old T0X subdirectories exist (note: F02 has no README currently - needs creation)
- [ ] References `rsvps` table with payment tracking fields
- [ ] Includes RsvpResource enhancement (already exists in Filament)
- [ ] Includes capacity management service
- [ ] Includes waitlist logic and notifications
- [ ] Includes attendance check-in system (QR codes, location-based)
- [ ] Includes RSVP notifications (integration with E01 notifications)
- [ ] Estimated time: 30-40 hours total

### F03: Tagging & Category System
- [ ] File exists: `context-engine/tasks/E03_Activity_Management/F03_Tagging_Category_System/README.md`
- [ ] No old T0X subdirectories exist
- [ ] References `tags` table with `usage_count` field
- [ ] References `activity_tag` pivot table
- [ ] Includes TagResource enhancement (already exists in Filament)
- [ ] Includes tag autocomplete Livewire component
- [ ] Includes category hierarchy system
- [ ] Includes trending tags analytics
- [ ] Includes tag-based discovery (integration with E04)
- [ ] Estimated time: 25-35 hours total

---

## E04 Discovery Engine Validation

### F01: Discovery Feed Service
- [ ] File exists: `context-engine/tasks/E04_Discovery_Engine/F01_Search_Service/README.md` (or renamed to F01_Discovery_Feed_Service)
- [ ] No old T0X subdirectories exist
- [ ] **CRITICAL**: Documents different radii (Posts 5-10km, Events 25-50km)
- [ ] Includes PostGIS-powered nearby feed with spatial queries
- [ ] Includes For You feed with personalization
- [ ] Includes map view with Leaflet/Mapbox
- [ ] Includes temporal decay scoring (posts expire 24-48h, events persist)
- [ ] Includes feed caching strategy with Redis
- [ ] References matanyadaev/laravel-eloquent-spatial for spatial queries
- [ ] Includes example spatial queries in Technical Notes
- [ ] Estimated time: 35-45 hours total

### F02: Recommendation Engine
- [ ] File exists: `context-engine/tasks/E04_Discovery_Engine/F02_Recommendation_Engine/README.md`
- [ ] No old T0X subdirectories exist
- [ ] Includes multi-factor scoring algorithm (recency × proximity × interests × social)
- [ ] Includes temporal intelligence (posts decay quickly, events persist)
- [ ] Includes interest matching with JSON field queries
- [ ] Includes social boost calculation (followed users, reactions)
- [ ] Includes cold start handling for new users
- [ ] Includes recommendation caching with Redis
- [ ] Includes scoring formula in Technical Notes
- [ ] Estimated time: 30-40 hours total

### F03: Social Resonance & Post Evolution
- [ ] File exists: `context-engine/tasks/E04_Discovery_Engine/F03_Feed_Generation_Service/README.md` (or renamed to F03_Social_Resonance_Post_Evolution)
- [ ] No old T0X subdirectories exist
- [ ] **CRITICAL**: Documents post-to-event conversion flow (E04 initiates, E03 creates activity)
- [ ] References `post_reactions` table (im_down, join_me, interested)
- [ ] References `post_conversions` table for tracking conversions
- [ ] Includes conversion detection logic with thresholds (5+ reactions → suggest, 10+ → auto-convert)
- [ ] Includes SocialResonanceService for reaction tracking
- [ ] Includes ConversionDetectionService for monitoring thresholds
- [ ] Includes reaction Livewire components
- [ ] Includes conversion analytics and notifications
- [ ] Documents E04→E03 handoff clearly
- [ ] Estimated time: 30-40 hours total

---

## Post-Validation Actions

### After All Files Pass Validation
1. [ ] Run search for remaining React Native references: `grep -r "React Native" context-engine/tasks/E0[234]*/`
2. [ ] Run search for remaining Supabase references: `grep -r "Supabase" context-engine/tasks/E0[234]*/`
3. [ ] Run search for remaining TypeScript references: `grep -r "interface.*{" context-engine/tasks/E0[234]*/`
4. [ ] Verify all old T0X subdirectories are removed: `find context-engine/tasks/E0[234]*/ -type d -name "T0*"`
5. [ ] Verify all 9 README files exist: `find context-engine/tasks/E0[234]*/ -name "README.md" | wc -l` (should be 9)
6. [ ] Commit changes to git with descriptive message
7. [ ] Update project documentation index if needed

### If Validation Fails
1. Document specific issues found
2. Provide corrective guidance to the agent
3. Re-validate after corrections

---

## Quick Validation Commands

```bash
# Count README files (should be 9)
find context-engine/tasks/E0[234]*/ -maxdepth 2 -name "README.md" | wc -l

# Check for old subdirectories (should be empty)
find context-engine/tasks/E0[234]*/ -type d -name "T0*"

# Search for React Native references (should be empty)
grep -r "React Native\|Expo\|React Navigation" context-engine/tasks/E0[234]*/

# Search for Supabase references (should be empty)
grep -r "Supabase\|RPC\|Supabase Storage" context-engine/tasks/E0[234]*/

# Search for TypeScript references (should be empty)
grep -r "interface.*{" context-engine/tasks/E0[234]*/ | grep -v "Integration Points"

# Verify total estimated hours (should be ~270-360 hours for all 9 features)
grep -h "Estimated Total Time" context-engine/tasks/E0[234]*/F*/README.md
```

---

## Success Criteria

All 9 README files are considered complete when:
- ✅ All global validation checks pass
- ✅ All epic-specific validation checks pass
- ✅ No React Native, Supabase, or TypeScript references remain
- ✅ All files follow LARAVEL-DOCUMENTATION-TEMPLATE.md structure
- ✅ All files reference E01's completed implementation
- ✅ Post-to-event conversion is documented in E03/F01 and E04/F03
- ✅ PostGIS spatial queries are emphasized in E02/F03 and E04/F01
- ✅ All old React Native subdirectories are removed
- ✅ Total estimated time is realistic (270-360 hours for all 9 features)

