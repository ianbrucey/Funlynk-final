# FunLynk Agent Navigation Guide

## Project Identity
**FunLynk**: Laravel 12 web app for spontaneous, niche activity discovery. Users discover activities through ephemeral "Posts" (24-48h) that can evolve into structured "Events" based on engagement.

**Tech Stack**: Laravel 12, Filament v4, Livewire v3, PostgreSQL + PostGIS, DaisyUI, Pest v4

## Core Architecture Principle
**Posts vs Events Dual Model** - The platform's defining feature:
- **Posts**: Ephemeral (24-48h), spontaneous, 5-10km radius, reactions ("I'm down", "Join me")
- **Events**: Structured, persistent, 25-50km radius, RSVPs, payments
- **Conversion**: Posts with 5+ reactions → suggest conversion → 10+ reactions → auto-convert to Event
- **Flow**: E04 detects engagement → E03 creates Event with `originated_from_post_id`

## Project Structure

### Documentation Hierarchy
```
context-engine/
├── global-context.md           # Universal project context (READ FIRST)
├── epics/                      # 7 major modules (E01-E07)
│   └── E0X_Name/
│       ├── epic-overview.md    # Epic purpose & scope
│       ├── database-schema.md  # Tables & relationships
│       ├── api-contracts.md    # API endpoints
│       └── service-architecture.md
├── tasks/                      # Feature-level implementation docs
│   └── E0X_Name/
│       └── F0X_Feature_Name/
│           └── README.md       # 5-7 tasks, Artisan commands, time estimates
└── domain-contexts/            # Cross-cutting concerns
    ├── ui-design-standards.md  # Galaxy theme, glass morphism (CRITICAL for UI)
    ├── database-context.md     # PostGIS, spatial queries
    └── auth-context.md         # Laravel Auth, Filament
```

### Implementation Status
- ✅ **E01 Core Infrastructure**: Database, migrations, models, Filament resources COMPLETE
- 🔄 **E02-E04**: Task documentation rebuilt for Laravel (Nov 2025), ready for implementation
- ⏳ **E05-E07**: Epic planning complete, task documentation pending

## 7 Epics Overview

1. **E01 Core Infrastructure**: Database (PostGIS), Auth, Notifications - **COMPLETE**
2. **E02 User & Profile Management**: Profiles, Privacy, User Discovery
3. **E03 Activity Management**: Event CRUD, RSVPs, Tagging, **Post-to-Event Conversion (receiving)**
4. **E04 Discovery Engine**: Feeds, Recommendations, **Post-to-Event Conversion (initiating)**
5. **E05 Social Interaction**: Comments, Reactions, Communities
6. **E06 Payments & Monetization**: Stripe Connect, Subscriptions
7. **E07 Administration**: Analytics, Moderation, Monitoring

## Critical Integration Points

### E01 Foundation (Available Now)
**Tables**: users, posts, activities, post_reactions, post_conversions, rsvps, tags, follows, notifications, comments, flares, reports
**Models**: User, Post, Activity, PostReaction, PostConversion, Rsvp, Tag, Follow, Notification, Comment, Flare, Report
**Filament Resources**: UserResource, PostResource, ActivityResource, RsvpResource, TagResource, PostReactionResource, CommentResource

### PostGIS Spatial Queries (E02/F03, E04/F01)
```php
// Posts: 5-10km radius
Post::whereDistance('location_coordinates', $point, '<=', 10000)->get();

// Events: 25-50km radius
Activity::whereDistance('location_coordinates', $point, '<=', 50000)->get();
```

### Post-to-Event Conversion (E03/F01, E04/F03)
```php
// E04 detects engagement threshold
if ($post->reactions()->count() >= 5) {
    // E04 calls E03's service
    app(ActivityConversionService::class)->createFromPost($post);
    
    // E03 creates activity
    Activity::create([
        'originated_from_post_id' => $post->id,
        // ... copy location, time hints
    ]);
}
```

## Development Workflow

### Before Starting Any Task
1. **Read epic-overview.md** for business context
2. **Read task README.md** for implementation details (5-7 tasks with Artisan commands)
3. **Check E01 foundation** for available tables/models/resources
4. **Review domain-contexts/** for UI standards, database patterns, auth patterns

### Implementation Pattern
```bash
# Task structure (from README.md)
T01: Database/Model Setup (migrations, models, factories)
T02: Service Classes (business logic)
T03: Filament Resources (admin CRUD)
T04: Livewire Components (user-facing UI)
T05: Policies (authorization)
T06: Jobs (async processing)
T07: Tests (Pest v4)
```

### Always Use
- `php artisan make:*` commands with `--no-interaction` flag
- `casts()` method (not `$casts` property) - Laravel 12
- `->components([])` (not `->schema([])`) - Filament v4
- PostGIS for location queries via matanyadaev/laravel-eloquent-spatial
- DaisyUI classes for UI components
- Galaxy theme with glass morphism for all pages (see ui-design-standards.md)

### UI Styling - CRITICAL RULES

**EVERY page/component MUST follow the galaxy theme**. No exceptions.

**Step 1: Use the Galaxy Layout Component**
```blade
<x-galaxy-layout>
    <x-slot name="title">Page Title</x-slot>

    <!-- Your content here -->

</x-galaxy-layout>
```

**Step 2: Wrap Content in Glass Cards**
```blade
<div class="container mx-auto px-6 py-8">
    <div class="relative p-8 glass-card max-w-4xl mx-auto">
        <div class="top-accent-center"></div>
        <!-- Your content -->
    </div>
</div>
```

**Step 3: Use Gradient Buttons**
```blade
<!-- Primary -->
<button class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
    Submit
</button>

<!-- Secondary -->
<button class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
    Cancel
</button>
```

**Step 4: Reference Files**
Before creating UI, review:
- `resources/views/welcome.blade.php` - Full page example
- `resources/views/livewire/auth/login.blade.php` - Form example
- `context-engine/domain-contexts/ui-design-standards.md` - Complete guide

**Step 5: Verify Checklist**
- [ ] Galaxy gradient background
- [ ] Aurora layers visible
- [ ] Stars twinkling
- [ ] Content in glass cards
- [ ] Buttons have gradients
- [ ] Forms have cyan focus glow
- [ ] Text is white/gray (readable)
- [ ] Hover effects work

## Development Progress Tracking

### Purpose
Maintain timestamped progress logs to serve as a development journal. These logs help new agents quickly understand project history, current state, and planned work without re-reading entire conversation history.

### Log File Management
- **Location**: `dev-logs/` directory at project root
- **Naming**: `YYYY-MM-DD-HH.md` (e.g., `2025-01-20-14.md` for January 20, 2025 at 2 PM)
- **Structure**: Each log file must contain exactly 3 sections:
  1. **Previously Completed** - Recent accomplishments (last 2-3 sessions)
  2. **Currently Working On** - Active tasks and current focus
  3. **Next Steps** - Planned upcoming work and priorities

### When to Update
- At the beginning of each new work session
- After completing major tasks or milestones
- Before ending a work session
- When switching between major features or epics

### Format Guidelines
- Use clear, concise bullet points
- Keep each section to 5-10 bullets maximum for readability
- Include specific file paths, feature names, and epic references
- Note any blockers or important decisions made

## Quick Reference Commands

```bash
# Documentation
cat context-engine/global-context.md                    # Start here
cat context-engine/epics/E0X_Name/epic-overview.md      # Epic context
cat context-engine/tasks/E0X_Name/F0X_Feature/README.md # Task details

# Implementation
php artisan make:filament-resource Name --generate --no-interaction
php artisan make:livewire Namespace/Component --no-interaction
php artisan make:test --pest Feature/TestName --no-interaction

# Testing
php artisan test --filter=TestName
vendor/bin/pint --dirty  # Format code before committing
```

## Critical Rules
1. **NO React Native/Supabase/TypeScript** - This is Laravel only
2. **Filament First** - Use Filament for CRUD, custom views only when necessary
3. **PostGIS for Location** - All spatial queries use PostGIS geography columns
4. **Galaxy Theme** - All UI must follow ui-design-standards.md (glass cards, aurora effects)
5. **Posts vs Events** - Always respect the dual model architecture
6. **E01 Foundation** - Always reference completed tables/models/resources
7. **Test Everything** - Write Pest tests for all features

## When Lost
1. Check `context-engine/global-context.md` for big picture
2. Check epic `epic-overview.md` for module context
3. Check task `README.md` for specific implementation steps
4. Check `domain-contexts/` for cross-cutting patterns
5. Check E01 implementation for working examples

