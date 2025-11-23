# 3-Agent Parallel Development Coordination Plan

**Date**: 2025-11-23
**Last Updated**: 2025-11-23 (Sprint 2 assignments)
**Objective**: Implement E02-E04 features in parallel to accelerate FunLynk development
**Duration**: 2-3 weeks estimated

---

## 🎉 Sprint 1 Complete!

**Agent A**: ✅ E02/F01 Profile Management (EditProfile Livewire component, 10 tests passing)
**Agent B**: ✅ E03/F03 Tagging System (autocomplete, trending, analytics)
**Agent C**: 🔄 E05-E07 Documentation (in progress)

**Sprint 2 Now Unblocked**: E03/F01, E02/F02, E02/F03 are all ready to start!

---

## Context: E01 Foundation Status

### ✅ Available Infrastructure (E01 Complete)
**Database Tables**: `users`, `posts`, `activities`, `tags`, `activity_tag`, `post_reactions`, `post_conversions`, `rsvps`, `follows`, `notifications`, `comments`, `flares`, `reports`

**Eloquent Models**:
- `User` (with PostGIS location, interests JSON, profile fields)
- `Post` (ephemeral, 24-48h, 5-10km radius)
- `Activity` (structured events, 25-50km radius)
- `Tag` (with usage_count, category)
- `PostReaction`, `PostConversion`, `Rsvp`, `Follow`, `Notification`, `Comment`

**Filament Resources**: `UserResource`, `PostResource`, `ActivityResource` (basic CRUD), `TagResource`, `PostReactionResource`, `CommentResource`

**Auth System**: Laravel Breeze installed, registration/login working

**UI Standards**: Galaxy theme with glass morphism (see `context-engine/domain-contexts/ui-design-standards.md`)

### 🎯 Current Goal
Implement E02-E04 to enable:
1. Rich user profiles with interests and location (E02)
2. Activity/Event management with tagging (E03)
3. Discovery feeds with spatial search (E04)

### 🧩 Why These 3 Assignments?

**Assignment Rationale: Dependency Graph, Not Epic Grouping**

We're NOT assigning full epics. We're assigning based on **blocking dependencies**:

```
E02/F01 (Profiles) ← FOUNDATION (Agent A)
    ↓ BLOCKS ↓
    ├─→ E02/F02 (Privacy Settings) ← needs profiles to exist first
    ├─→ E02/F03 (User Discovery) ← needs profile data to search
    ├─→ E03/F01 (Activity CRUD) ← needs host profiles, user locations
    ├─→ E03/F02 (RSVP System) ← needs activities + user profiles
    └─→ E04/F01-F03 (Discovery) ← needs profiles + activities + tags

E03/F03 (Tagging) ← INDEPENDENT (Agent B, parallel-safe)
    ↑ NO DEPENDENCIES ↑
    - Tags are metadata only
    - Don't need activities to exist yet
    - Don't need user profiles
    - Can build autocomplete, trending, analytics in isolation
```

**Why Agent A gets E02/F01 ONLY (not F02, F03)**:
- E02/F02 (Privacy) can't be built until profiles exist - what would you make private?
- E02/F03 (Discovery) can't search for users until profile data exists
- Agent A will do F02 and F03 in Sprint 2 after F01 completes

**Why Agent B gets E03/F03 ONLY (not F01, F02)**:
- E03/F01 (Activity CRUD) needs user profiles (host info, locations)
- E03/F02 (RSVPs) needs both activities AND profiles
- E03/F03 (Tagging) is pure metadata - works independently

**Why Agent C gets Documentation**:
- No other E02-E04 features can run in parallel without blocking
- Building E04 (Discovery) without real profiles/activities = building on sand
- Forward-looking documentation unblocks Sprint 2 and Sprint 3

---

## Agent A: Coordinator + E02/F01 Profile Management

### Assignment
**Feature**: E02/F01 Profile Creation & Management
**Documentation**: `context-engine/tasks/E02_User_Profile_Management/F01_Profile_Creation_Management/README.md`
**Estimated Time**: 30-38 hours (7 tasks)

### Responsibilities
1. **Coordinate**: Monitor Agent B/C progress, resolve integration questions
2. **Implement E02/F01**: Profile CRUD, image uploads, interest management, location picker

### Technical Context
**What You're Building**: Rich user profiles that power all discovery features

**E01 Foundation You'll Use**:
- `users` table (columns: `bio`, `profile_image_url`, `location_name`, `location_coordinates` (PostGIS), `interests` (JSON), `display_name`)
- `User` model at `app/Models/User.php` (has relationships, needs profile methods)
- `UserResource` at `app/Filament/Resources/Users/` (basic CRUD, needs profile fields)
- Laravel Breeze auth (registration/login working)

**Your 7 Tasks** (from README.md):
1. **T01**: Enhance `UserResource` with profile fields (bio, interests JSON editor, location picker, image upload)
2. **T02**: Profile image upload via Laravel filesystem (store in `public/profiles`, validate size/type)
3. **T03**: Create `ProfileService` (calculate completion %, validate interests, geocode locations)
4. **T04**: Build `EditProfile` Livewire component (user-facing profile editor with galaxy theme)
5. **T05**: Build `ProfileCompletion` Livewire component (progress indicator: "Your profile is 60% complete")
6. **T06**: Create `UserPolicy` (users can only edit their own profiles)
7. **T07**: Write Pest tests (profile CRUD, image upload, completion calculation)

**Key Technical Decisions**:
- **Interests**: Store as JSON array in `users.interests` column, use Filament `TagsInput` component
- **Location**: Use PostGIS `geography(POINT, 4326)` for `location_coordinates`, save human-readable name to `location_name`
- **Profile Completion**: Calculate as: (filled fields / total fields) * 100. Required fields: bio, interests (min 3), location, profile_image
- **Image Storage**: Use Laravel's `public` disk, generate thumbnails (200x200), validate max 2MB
- **Galaxy Theme**: All Livewire views must use glass cards, gradient buttons, cyan focus glow (see `ui-design-standards.md`)

### Key Files to Create/Modify
```
app/Services/ProfileService.php                    # Profile completion logic
app/Livewire/Profile/EditProfile.php               # User-facing profile editor
app/Livewire/Profile/ProfileCompletion.php         # Completion indicator
app/Policies/UserPolicy.php                        # Profile edit authorization
resources/views/livewire/profile/edit-profile.blade.php
resources/views/livewire/profile/show-profile.blade.php
tests/Feature/ProfileManagementTest.php
```

### Integration Points
- **With Agent B**: None (tagging is independent)
- **With E01**: Extends existing `User` model and `UserResource`
- **Blocks**: E02/F02 (Privacy), E02/F03 (User Discovery) - both need profiles first

### Success Criteria
- [ ] Users can edit bio, interests, location, profile image
- [ ] Profile completion percentage displays correctly
- [ ] Location picker saves to PostGIS `location_coordinates`
- [ ] Filament `UserResource` enhanced with new fields
- [ ] All tests pass (`php artisan test --filter=Profile`)
- [ ] Galaxy theme applied to all profile views

---

## Agent B: E03/F03 Tagging & Category System

### Assignment
**Feature**: E03/F03 Tagging & Category System
**Documentation**: `context-engine/tasks/E03_Activity_Management/F03_Tagging_Category_System/README.md`
**Estimated Time**: 28-36 hours (7 tasks)

### Why This Feature?
✅ **Parallel-safe**: Works with existing `tags` table, no dependency on Agent A's profiles
✅ **High value**: Enables activity categorization and discovery filtering
✅ **Independent**: Doesn't touch `users` or profile-related code

### Technical Context
**What You're Building**: A complete tagging infrastructure that will power activity discovery

**E01 Foundation You'll Use**:
- `tags` table (columns: `id`, `name`, `slug`, `usage_count`, `category`, `is_featured`, `created_at`)
- `activity_tag` pivot table (many-to-many: activities ↔ tags)
- `Tag` model at `app/Models/Tag.php` (basic, needs enhancement)
- `TagResource` at `app/Filament/Resources/Tags/` (basic CRUD only)

**Your 7 Tasks** (from README.md):
1. **T01**: Enhance `TagResource` with analytics columns (usage_count, category filters, bulk moderation)
2. **T02**: Build `TagAutocomplete` Livewire component (suggest tags as users type, create new tags)
3. **T03**: Create `TagService` (business logic: trending calculation, analytics, moderation rules)
4. **T04**: Build `TrendingTags` Livewire component (display popular tags with usage counts)
5. **T05**: Create `TagPolicy` (who can create/moderate tags)
6. **T06**: Create `UpdateTagAnalytics` job (background task to recalculate usage_count, trending scores)
7. **T07**: Write Pest tests (autocomplete, trending algorithm, analytics job)

**Key Technical Decisions**:
- **Trending Algorithm**: Use `usage_count` + recency weighting (tags used in last 7 days score higher)
- **Caching**: Cache trending tags for 1 hour (use Laravel Cache with `tags:trending` key)
- **Autocomplete**: Search `tags.name` with ILIKE, limit 10 results, order by `usage_count DESC`
- **Galaxy Theme**: All Livewire components must use glass cards, gradient buttons (see `ui-design-standards.md`)

### Key Files to Create/Modify
```
app/Services/TagService.php                        # Tag analytics, trending
app/Livewire/Tags/TagAutocomplete.php              # Autocomplete component
app/Livewire/Tags/TrendingTags.php                 # Trending display
app/Jobs/UpdateTagAnalytics.php                    # Background analytics
app/Policies/TagPolicy.php                         # Tag moderation
resources/views/livewire/tags/tag-autocomplete.blade.php
tests/Feature/TagManagementTest.php
```

### DO NOT TOUCH
- ❌ `app/Models/User.php` (Agent A is modifying)
- ❌ `app/Filament/Resources/Users/` (Agent A's territory)
- ❌ Any profile-related Livewire components

### Integration Points
- **With E01**: Uses existing `Tag` model, `TagResource`, `activity_tag` pivot
- **With Agent A**: None (no conflicts)
- **Future**: E04/F01 (Search) will use tags for filtering

### Success Criteria
- [ ] Tag autocomplete works in activity forms
- [ ] Trending tags display with usage counts
- [ ] Tag analytics job runs successfully
- [ ] Filament `TagResource` enhanced with analytics columns
- [ ] All tests pass (`php artisan test --filter=Tag`)
- [ ] Galaxy theme applied to tag components

---

## Agent C: E05-E07 Task Documentation Completion

### Assignment
**Task**: Complete E05-E07 README file rewrites (12 files)
**Reference**: `dev-logs/2025-11-20-12.md` (lines 29-56)
**Estimated Time**: 6-8 hours

### Why Documentation Instead of Code?
❌ **E02/F02-F03**: Blocked by Agent A's E02/F01 (need profiles first)
❌ **E03/F01-F02**: Blocked by Agent A's E02/F01 (activities need user profiles)
❌ **E04/F01-F03**: Blocked by both Agent A and B (need profiles + tags)
✅ **E05-E07 Docs**: Forward-looking work, unblocks future sprints

### Technical Context
**What You're Building**: Implementation-ready task documentation for 12 features across 3 epics

**Why This Matters**:
- Sprint 2 will implement E03/F01-F02, E04/F01-F03 - they need docs NOW
- Sprint 3 will implement E05 (social features) - docs must be ready
- Without these docs, future agents waste hours figuring out architecture

**Your Documentation Standards** (CRITICAL):
1. **Use the template**: `context-engine/tasks/LARAVEL-DOCUMENTATION-TEMPLATE.md` (7-task structure)
2. **Follow epic guides**: Read `E05-DOCUMENTATION-GUIDE.md`, `E06-DOCUMENTATION-GUIDE.md`, `E07-DOCUMENTATION-GUIDE.md` first
3. **Reference E02-E04 examples**: Look at completed README files for structure/tone
4. **Laravel 12 conventions**:
   - Use `casts()` method (NOT `$casts` property)
   - Use Filament v4 `->components([])` (NOT `->schema([])`)
   - All Artisan commands must have `--no-interaction` flag
5. **No React Native/Supabase/TypeScript**: This is Laravel only
6. **Time estimates**: Include for each task (be realistic: 3-8 hours per task)

**Each README Must Include**:
- Feature Overview (2-3 paragraphs explaining business value)
- Feature Scope (In Scope / Out of Scope bullets)
- 7 Tasks with this structure:
  - T01: Database/Model Setup
  - T02: Service Classes
  - T03: Filament Resources
  - T04: Livewire Components
  - T05: Policies
  - T06: Jobs (async processing)
  - T07: Tests (Pest v4)
- Artisan commands for each task
- Integration points with E01 foundation
- Testing strategy

### Scope
Rebuild 12 README files following Laravel 12 conventions:

**E05 Social Interaction** (4 files):
- `F01_Comment_Discussion_System/README.md`
- `F02_Social_Sharing_Engagement/README.md`
- `F03_Community_Features/README.md`
- `F04_Realtime_Social_Features/README.md`

**E06 Payments & Monetization** (4 files):
- `F01_Payment_Processing_System/README.md`
- `F02_Revenue_Sharing_Payouts/README.md`
- `F03_Subscription_Premium_Features/README.md`
- `F04_Marketplace_Monetization_Tools/README.md`

**E07 Administration** (4 files):
- `F01_Platform_Analytics_BI/README.md`
- `F02_Content_Moderation_Safety/README.md`
- `F03_User_Community_Management/README.md`
- `F04_System_Monitoring_Operations/README.md`

### Template & Guidelines
- **Template**: `context-engine/tasks/LARAVEL-DOCUMENTATION-TEMPLATE.md`
- **Epic Guides**: `context-engine/epics/E05_Social_Interaction/E05-DOCUMENTATION-GUIDE.md` (and E06, E07)
- **Reference Examples**: Any E02-E04 README files (already rebuilt)

### Success Criteria
- [ ] All 12 README files created with 7-task structure
- [ ] No React Native/Supabase/TypeScript references
- [ ] Artisan commands use `--no-interaction` flag
- [ ] Laravel 12 conventions (`casts()`, Filament v4 `->components([])`)
- [ ] Time estimates included for each task
- [ ] Files validated with: `grep -r "React Native\|Supabase\|TypeScript" context-engine/tasks/E05* E06* E07*`

---

## Coordination & Communication

### Daily Sync Points
- **Morning**: Each agent posts progress update in shared doc
- **Evening**: Each agent commits code with descriptive messages

### Conflict Resolution
- **Code conflicts**: Agent A (coordinator) resolves
- **Blocking issues**: Agent A reassigns work if needed

### Integration Testing
After all agents complete:
1. Agent A runs full test suite
2. Agent A verifies galaxy theme consistency
3. Agent A updates `dev-logs/` with completion status

---

## Timeline

**Week 1**:
- Agent A: E02/F01 Tasks T01-T04 (profile CRUD, images)
- Agent B: E03/F03 Tasks T01-T04 (tag autocomplete, analytics)
- Agent C: E05 documentation (4 files)

**Week 2**:
- Agent A: E02/F01 Tasks T05-T07 (policies, jobs, tests)
- Agent B: E03/F03 Tasks T05-T07 (trending, caching, tests)
- Agent C: E06 documentation (4 files)

**Week 3**:
- Agent A: Integration testing, bug fixes
- Agent B: Integration testing, bug fixes
- Agent C: E07 documentation (4 files), validation

---

**Last Updated**: 2025-11-23
**Status**: Ready for parallel execution



## 🚀 Sprint 2 Assignments (Updated 2025-11-23)

### Agent B: E03/F01 Activity CRUD Operations

**Assignment**: Build the core activity/event creation and management system
**Documentation**: `context-engine/tasks/E03_Activity_Management/F01_Activity_CRUD_Operations/README.md`
**Estimated Time**: 35-45 hours (7 tasks)

**Why This Feature?**
✅ **Dependencies met**: Profiles (Agent A) ✅ + Tags (Agent B Sprint 1) ✅
✅ **Critical path**: Unblocks E03/F02 (RSVPs) and E04 (Discovery feeds)
✅ **Core functionality**: Users can create/edit/delete activities and events

**What You're Building**:
- Activity creation form (title, description, location, time, capacity, tags)
- Activity editing and deletion
- Post-to-Event conversion (when Posts get 10+ reactions)
- Activity detail view with galaxy theme
- Activity list/management for hosts

**E01 Foundation You'll Use**:
- `activities` table (all fields ready: title, description, location_coordinates, start_time, end_time, capacity, etc.)
- `Activity` model at `app/Models/Activity.php`
- `ActivityResource` at `app/Filament/Resources/Activities/` (basic CRUD)
- `activity_tag` pivot table (for tagging activities)
- `posts` table and `Post` model (for Post-to-Event conversion)

**Your 7 Tasks** (from README.md):
1. **T01**: Enhance `ActivityResource` with all fields (location picker, tag selector, capacity, pricing)
2. **T02**: Create `ActivityService` (business logic: validation, Post conversion, capacity checks)
3. **T03**: Build `CreateActivity` Livewire component (user-facing creation form with galaxy theme)
4. **T04**: Build `EditActivity` Livewire component (edit existing activities)
5. **T05**: Build `ActivityDetail` Livewire component (public activity view)
6. **T06**: Create `ActivityPolicy` (who can edit/delete activities)
7. **T07**: Write Pest tests (CRUD, Post conversion, capacity validation)

**Key Technical Decisions**:
- **Location**: Use PostGIS for `location_coordinates`, 25-50km radius for events
- **Post Conversion**: When Post gets 10+ reactions, create Activity with `originated_from_post_id`
- **Capacity**: Validate RSVPs don't exceed `max_attendees`
- **Tags**: Use Agent B's autocomplete component from Sprint 1
- **Galaxy Theme**: Glass cards, gradient buttons, cyan focus glow

**Key Files to Create/Modify**:
```
app/Services/ActivityService.php
app/Livewire/Activities/CreateActivity.php
app/Livewire/Activities/EditActivity.php
app/Livewire/Activities/ActivityDetail.php
app/Policies/ActivityPolicy.php
resources/views/livewire/activities/create-activity.blade.php
resources/views/livewire/activities/activity-detail.blade.php
tests/Feature/ActivityManagementTest.php
```

**Integration Points**:
- **With Agent A (E02/F01)**: Use User profiles for host info, user locations
- **With Agent B Sprint 1 (E03/F03)**: Use TagAutocomplete component for activity tagging
- **With E01**: Extends Activity model, uses PostGIS spatial queries
- **Blocks**: E03/F02 (RSVPs need activities), E04/F01 (Discovery needs activities)

**Success Criteria**:
- [ ] Users can create activities with all fields (title, description, location, time, capacity, tags)
- [ ] Users can edit/delete their own activities
- [ ] Post-to-Event conversion works (10+ reactions → create Activity)
- [ ] Activity detail page displays with galaxy theme
- [ ] Location picker saves to PostGIS coordinates
- [ ] All tests pass (`php artisan test --filter=Activity`)

---

### Agent A: Next Assignment TBD
**Options**: E02/F02 (Privacy Settings) or E02/F03 (User Discovery)
**Waiting for**: User decision on priority

### Agent C: Continue E05-E07 Documentation
**Status**: In progress, no changes