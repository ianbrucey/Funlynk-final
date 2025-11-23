# Quick Reference for Documentation Rebuild

## File Locations

### Templates & Guides
- **Template**: `context-engine/tasks/LARAVEL-DOCUMENTATION-TEMPLATE.md`
- **E02 Guide**: `context-engine/tasks/E02-DOCUMENTATION-GUIDE.md`
- **E03 Guide**: `context-engine/tasks/E03-DOCUMENTATION-GUIDE.md`
- **E04 Guide**: `context-engine/tasks/E04-DOCUMENTATION-GUIDE.md`
- **Example**: `context-engine/tasks/E01_Core_Infrastructure/F01_Database_Foundation/README.md`
- **Validation**: `context-engine/tasks/DOCUMENTATION-VALIDATION-CHECKLIST.md`

### Epic Overviews
- **E02**: `context-engine/epics/E02_User_Profile_Management/epic-overview.md`
- **E03**: `context-engine/epics/E03_Activity_Management/epic-overview.md`
- **E04**: `context-engine/epics/E04_Discovery_Engine/epic-overview.md`

### Files to Rewrite (9 total)
```
E02:
- context-engine/tasks/E02_User_Profile_Management/F01_Profile_Creation_Management/README.md
- context-engine/tasks/E02_User_Profile_Management/F02_Privacy_Settings/README.md
- context-engine/tasks/E02_User_Profile_Management/F03_User_Discovery_Search/README.md

E03:
- context-engine/tasks/E03_Activity_Management/F01_Activity_CRUD_Operations/README.md
- context-engine/tasks/E03_Activity_Management/F02_RSVP_Attendance_System/README.md (NO README EXISTS - CREATE NEW)
- context-engine/tasks/E03_Activity_Management/F03_Tagging_Category_System/README.md

E04:
- context-engine/tasks/E04_Discovery_Engine/F01_Search_Service/README.md
- context-engine/tasks/E04_Discovery_Engine/F02_Recommendation_Engine/README.md
- context-engine/tasks/E04_Discovery_Engine/F03_Feed_Generation_Service/README.md
```

---

## Standard README Structure (Copy This)

```markdown
# [Feature ID]: [Feature Name]

## Feature Overview

[2-4 paragraphs describing the feature's purpose, how it uses Laravel 12 + Filament v4, and how it builds on E01's foundation]

**Key Architecture**: [If relevant, mention Posts vs Events dual model]

## Feature Scope

### In Scope
- **[Component 1]**: [Description]
- **[Component 2]**: [Description]
- **[Component 3]**: [Description]
- **[Component 4]**: [Description]

### Out of Scope
- **[Feature]**: [Reason - handled by which epic]
- **[Feature]**: [Reason - Phase 2 or future]

## Tasks Breakdown

### T01: [Task Name]
**Estimated Time**: [X-Y] hours
**Dependencies**: [None or task IDs]
**Artisan Commands**:
```bash
php artisan make:filament-resource [Name] --generate --no-interaction
php artisan make:model [Name] --no-interaction
```

**Description**: [3-5 sentences describing what needs to be built]

**Deliverables**:
- [ ] [Specific deliverable 1]
- [ ] [Specific deliverable 2]
- [ ] [Specific deliverable 3]

---

[Repeat T02-T07]

---

## Success Criteria

### Database & Models
- [ ] [Criterion]

### Filament Resources
- [ ] [Criterion]

### Business Logic & Services
- [ ] [Criterion]

### User Experience
- [ ] [Criterion]

### Integration
- [ ] [Criterion]

## Dependencies

### Blocks
- **[Epic/Feature]**: [Description]

### External Dependencies
- **E01 Core Infrastructure**: [Specific tables, models, services]
- **[Package Name]**: [Purpose]

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method instead of `$casts` property
- Use `php artisan make:` commands with `--no-interaction` flag
- Middleware configured in `bootstrap/app.php`

### Filament v4 Conventions
- Use `->components([])` instead of `->schema([])`
- Use `relationship()` method for relationship fields
- File visibility defaults to `private`

### [Additional sections as needed]

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: [P0/P1/P2]
**Epic**: [E0X Epic Name]
**Estimated Total Time**: [XX-YY] hours
**Dependencies**: [List]
```

---

## Common Artisan Commands by Task Type

### T01: Database/Model Setup
```bash
php artisan make:migration add_[columns]_to_[table]_table --no-interaction
php artisan make:model [ModelName] --no-interaction
php artisan make:factory [ModelName]Factory --no-interaction
```

### T02: Service Classes
```bash
php artisan make:class Services/[ServiceName] --no-interaction
```

### T03: Filament Resources
```bash
php artisan make:filament-resource [ResourceName] --generate --no-interaction
# Note: Often enhancing existing resources, not creating new ones
```

### T04: Livewire Components
```bash
php artisan make:livewire [Namespace]/[ComponentName] --no-interaction
```

### T05: Policies
```bash
php artisan make:policy [PolicyName] --model=[ModelName] --no-interaction
```

### T06: Jobs (Async Processing)
```bash
php artisan make:job [JobName] --no-interaction
```

### T07: Tests
```bash
php artisan make:test --pest Feature/[TestName] --no-interaction
```

---

## E01 Completed Foundation (Reference This)

### Database Tables
- **users**: id, name, email, bio, interests (json), location_coordinates (geography), profile_image_url, is_host
- **posts**: id, user_id, content, location_coordinates (geography), expires_at, status, reaction_count
- **activities**: id, user_id, title, description, location_coordinates (geography), start_time, capacity, is_paid, price, originated_from_post_id
- **post_reactions**: id, post_id, user_id, reaction_type (im_down/join_me/interested)
- **post_conversions**: id, post_id, activity_id, conversion_trigger, conversion_score
- **rsvps**: id, activity_id, user_id, status, payment_status, payment_amount, attended
- **tags**: id, name, slug, category, usage_count
- **follows**: id, follower_id, following_id
- **notifications**: id, user_id, type, data (json), read_at
- **comments**: id, commentable_type, commentable_id, user_id, content
- **flares**: id, user_id, content, location_coordinates (geography), expires_at
- **reports**: id, reportable_type, reportable_id, user_id, reason

### Models Available
- User, Post, Activity, PostReaction, PostConversion, Rsvp, Tag, Follow, Notification, Comment, Flare, Report

### Filament Resources Available
- UserResource, PostResource, ActivityResource, RsvpResource, TagResource, PostReactionResource, CommentResource

---

## Critical Architecture Notes

### Posts vs Events Dual Model
- **Posts**: Ephemeral (24-48h), spontaneous, tight radius (5-10km)
- **Events**: Structured, planned, wider radius (25-50km)
- **Conversion**: Posts can evolve into events based on engagement

### Post-to-Event Conversion Flow
1. **E04 detects** high engagement on post (5+ "I'm down" reactions)
2. **E04 calls** E03's `ActivityConversionService::createFromPost($post)`
3. **E03 creates** activity with `originated_from_post_id = $post->id`
4. **E04 records** conversion in `post_conversions` table
5. **E04 notifies** post creator to complete activity details

**Document this in**: E03/F01 and E04/F03

### PostGIS Spatial Queries
```php
// Example: Nearby posts (5-10km)
Post::whereDistance('location_coordinates', $userLocation, '<=', 10000)
    ->where('expires_at', '>', now())
    ->get();

// Example: Nearby events (25-50km)
Activity::whereDistance('location_coordinates', $userLocation, '<=', 50000)
    ->where('start_time', '>', now())
    ->get();
```

**Emphasize in**: E02/F03, E04/F01

---

## Time Estimate Guidelines

### Per Task
- Simple Filament enhancement: 2-3 hours
- Service class with logic: 3-4 hours
- Complex Livewire component: 4-5 hours
- Policy with tests: 2-3 hours
- External integration: 4-6 hours
- Performance optimization: 3-4 hours
- Comprehensive testing: 2-3 hours

### Per Feature
- **Minimum**: 25 hours (5 tasks × 5 hours avg)
- **Target**: 30-35 hours (6 tasks × 5-6 hours avg)
- **Maximum**: 40 hours (7 tasks × 5-6 hours avg)

### Total for All 9 Features
- **Expected**: 270-360 hours

---

## Things to AVOID

### ❌ DO NOT Include
- React Native, Expo, React Navigation references
- Supabase RPC, Supabase Storage, Supabase Auth references
- TypeScript interfaces or type definitions
- Firebase, FCM, Firestore references
- Vague task descriptions ("Implement feature X")
- Unrealistic time estimates (1 hour for complex feature)
- Missing Artisan commands
- Generic deliverables ("Feature works")

### ✅ DO Include
- Specific Laravel 12 + Filament v4 implementations
- Exact Artisan commands with `--no-interaction` flag
- References to E01's completed tables/models/resources
- Realistic time estimates (2-8 hours per task)
- Specific, measurable deliverables
- Laravel conventions (casts() method, bootstrap/app.php)
- Filament v4 conventions (->components([]))
- PostGIS spatial query examples where relevant

---

## Workflow Recommendation

1. **Read all reference files first** (template, guides, epic overviews, E01 example)
2. **Start with E02** (simplest, no post-to-event conversion complexity)
3. **Then E03** (includes post-to-event conversion on receiving end)
4. **Finally E04** (includes post-to-event conversion on initiating end)
5. **Validate each file** using DOCUMENTATION-VALIDATION-CHECKLIST.md
6. **Run quick validation commands** to catch any missed references

---

## Quick Validation Commands

```bash
# Count README files (should be 9)
find context-engine/tasks/E0[234]*/ -maxdepth 2 -name "README.md" | wc -l

# Check for React Native references (should be empty)
grep -r "React Native\|Expo" context-engine/tasks/E0[234]*/

# Check for Supabase references (should be empty)
grep -r "Supabase" context-engine/tasks/E0[234]*/

# Check for TypeScript references (should be empty)
grep -r "interface.*{" context-engine/tasks/E0[234]*/ | grep -v "Integration Points"
```

---

## Need Help?

- **Structure questions**: Check LARAVEL-DOCUMENTATION-TEMPLATE.md
- **Feature-specific questions**: Check E02/E03/E04-DOCUMENTATION-GUIDE.md
- **Example reference**: Check E01/F01/README.md
- **Validation**: Check DOCUMENTATION-VALIDATION-CHECKLIST.md

