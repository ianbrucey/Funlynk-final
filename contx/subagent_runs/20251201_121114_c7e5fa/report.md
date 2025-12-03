--- /Users/ianbruce/Herd/funlynk/context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md ---

# F03: Groups Feature Implementation

## Epic: E05 Social Interaction
## Feature: F03 Groups

### Overview
This feature introduces a comprehensive group management system, allowing users to create, discover, join, and manage groups. Groups can be public or private, have members with different roles (member, admin), and host posts and activities. This feature is critical for fostering community and enabling more structured social interactions within FunLynk.

### Dependencies
- **E01 Core Infrastructure**: User model, database migrations, basic authentication.
- **E03 Activity Management**: Groups can host activities.
- **E05 Social Interaction (F01 Posts, F02 Reactions)**: Groups can have posts and reactions.
- **E02 User & Profile Management**: User profiles will display group memberships.

### Database Schema (Relevant Tables)

- **`groups`**:
    - `id` (UUID)
    - `name` (string)
    - `slug` (string, unique)
    - `description` (text, nullable)
    - `avatar_url` (string, nullable)
    - `cover_image_url` (string, nullable)
    - `privacy` (enum: 'public', 'private')
    - `auto_approve_members` (boolean, default: false)
    - `created_by` (UUID, foreign key to `users.id`)
    - `member_count` (integer, default: 0)
    - `deleted_at` (timestamp, nullable)
    - `timestamps`

- **`group_members`**: (Pivot table for `groups` and `users`)
    - `id` (UUID)
    - `group_id` (UUID, foreign key to `groups.id`)
    - `user_id` (UUID, foreign key to `users.id`)
    - `role` (enum: 'member', 'admin')
    - `status` (enum: 'pending', 'approved', 'rejected', 'banned')
    - `timestamps`

- **`group_join_requests`**:
    - `id` (UUID)
    - `group_id` (UUID, foreign key to `groups.id`)
    - `user_id` (UUID, foreign key to `users.id`)
    - `status` (enum: 'pending', 'approved', 'denied')
    - `timestamps`

- **`group_tag`**: (Pivot table for `groups` and `tags`)
    - `group_id` (UUID, foreign key to `groups.id`)
    - `tag_id` (UUID, foreign key to `tags.id`)
    - `timestamps`

### API Contracts (Not applicable for initial Livewire components)

### Service Architecture (GroupService)
- `GroupService` will encapsulate all business logic related to groups, including creation, updates, membership management, and join requests.

### UI/UX Considerations
- **Galaxy Theme**: All group-related UI must adhere to the `ui-design-standards.md`.
- **Glass Morphism**: Group cards, forms, and modals should use glass morphism.
- **Responsive Design**: Ensure optimal experience on desktop and mobile.
- **Join/Leave Flow**: Clear feedback for joining/leaving groups, especially for private groups requiring approval.

### Tasks (T01-T07)

#### T01: Database Migrations & Models (COMPLETE - E01)
- **Status**: COMPLETE (as part of E01 foundation)
- **Files**: `database/migrations/*_create_groups_table.php`, `database/migrations/*_create_group_members_table.php`, `database/migrations/*_create_group_join_requests_table.php`, `database/migrations/*_create_group_tag_table.php`
- **Models**: `app/Models/Group.php`, `app/Models/GroupMember.php`, `app/Models/GroupJoinRequest.php`

#### T02: GroupService Implementation
- **Status**: IN PROGRESS (basic methods exist, needs expansion)
- **Description**: Implement core business logic for group creation, updates, deletion, membership management, and join requests.
- **Files**: `app/Services/GroupService.php`
- **Methods to implement/refine**:
    - `createGroup(User $user, array $data)`
    - `updateGroup(Group $group, array $data)`
    - `deleteGroup(Group $group)`
    - `addMember(Group $group, User $user, string $role = 'member')`
    - `removeMember(Group $group, User $user)`
    - `updateMemberRole(Group $group, User $user, string $role)`
    - `createJoinRequest(Group $group, User $user)`
    - `approveJoinRequest(GroupJoinRequest $request, User $admin)`
    - `denyJoinRequest(GroupJoinRequest $request, User $admin)`
    - `isGroupMember(Group $group, User $user)`
    - `isGroupAdmin(Group $group, User $user)`
    - `searchGroups(string $query, string $privacy = 'public', ?array $tagIds = null)` (for discovery)
    - `getGroupsByTag(Tag $tag)`
    - `getUserGroups(User $user)`

#### T03: Filament Resources for Group Management
- **Status**: PENDING
- **Description**: Create Filament resources for `Group`, `GroupMember`, and `GroupJoinRequest` to allow administrators to manage groups.
- **Files**: `app/Filament/Resources/GroupResource.php`, `app/Filament/Resources/GroupMemberResource.php`, `app/Filament/Resources/GroupJoinRequestResource.php`
- **Artisan Commands**:
    ```bash
    php artisan make:filament-resource Group --generate --no-interaction
    php artisan make:filament-resource GroupMember --generate --no-interaction
    php artisan make:filament-resource GroupJoinRequest --generate --no-interaction
    ```

#### T04: Livewire Components for User-Facing UI
- **Status**: PENDING (This is the current task)
- **Description**: Develop Livewire components for group discovery, viewing individual groups, and managing user memberships.
- **Files**:
    - `app/Livewire/Groups/GroupsIndex.php` (Display all groups with search/filter)
    - `resources/views/livewire/groups/groups-index.blade.php`
    - `app/Livewire/Groups/ShowGroup.php` (Display single group details)
    - `resources/views/livewire/groups/show-group.blade.php`
    - `app/Livewire/Groups/CreateGroup.php` (Form for creating new groups)
    - `resources/views/livewire/groups/create-group.blade.php`
    - `app/Livewire/Groups/ManageGroupMembers.php` (Admin component for members)
    - `resources/views/livewire/groups/manage-group-members.blade.blade.php`
- **Artisan Commands**:
    ```bash
    php artisan make:livewire Groups/GroupsIndex --no-interaction
    php artisan make:livewire Groups/ShowGroup --no-interaction
    php artisan make:livewire Groups/CreateGroup --no-interaction
    php artisan make:livewire Groups/ManageGroupMembers --no-interaction
    ```

#### T05: Policies for Authorization
- **Status**: PENDING
- **Description**: Implement policies to control user access to group actions (e.g., who can create, update, delete groups, manage members, approve join requests).
- **Files**: `app/Policies/GroupPolicy.php`, `app/Policies/GroupMemberPolicy.php`, `app/Policies/GroupJoinRequestPolicy.php`
- **Artisan Commands**:
    ```bash
    php artisan make:policy Group --model=Group --no-interaction
    php artisan make:policy GroupMember --model=GroupMember --no-interaction
    php artisan make:policy GroupJoinRequest --model=GroupJoinRequest --no-interaction
    ```

#### T06: Jobs & Events (Asynchronous Processing)
- **Status**: PENDING
- **Description**: Implement jobs for background processing (e.g., sending notifications for new join requests, member approvals). Define events for group-related actions.
- **Files**: `app/Jobs/ProcessGroupJoinRequest.php`, `app/Events/GroupCreated.php`, `app/Events/GroupMemberJoined.php`, `app/Events/GroupJoinRequestReceived.php`
- **Artisan Commands**:
    ```bash
    php artisan make:job ProcessGroupJoinRequest --no-interaction
    php artisan make:event GroupCreated --no-interaction
    php artisan make:event GroupMemberJoined --no-interaction
    php artisan make:event GroupJoinRequestReceived --no-interaction
    ```

#### T07: Tests (Pest v4)
- **Status**: PENDING
- **Description**: Write comprehensive Pest tests for all group-related functionality, including service methods, Livewire components, and policies.
- **Files**: `tests/Feature/GroupsTest.php`, `tests/Unit/GroupServiceTest.php`, `tests/Livewire/Groups/GroupsIndexTest.php`
- **Artisan Commands**:
    ```bash
    php artisan make:test Feature/GroupsTest --pest --no-interaction
    php artisan make:test Unit/GroupServiceTest --pest --no-interaction
    php artisan make:test Livewire/Groups/GroupsIndexTest --pest --no-interaction
    ```

### Time Estimates
- T01: 0 hours (Complete)
- T02: 4 hours (Refinement)
- T03: 3 hours
- T04: 8 hours (Current Focus)
- T05: 3 hours
- T06: 2 hours
- T07: 6 hours

---

--- /Users/ianbruce/Herd/funlynk/context-engine/global-context.md ---

# FunLynk Global Context

## Project Identity

**FunLynk**: A Laravel 12 web application designed for spontaneous, niche activity discovery. The platform facilitates connections between users based on shared interests, enabling them to discover and participate in local activities.

**Core Concept**: The platform operates on a dual model of "Posts" and "Events".
- **Posts**: Ephemeral (24-48h lifespan), spontaneous, localized (5-10km radius), and engagement-driven (reactions like "I'm down", "Join me").
- **Events**: Structured, persistent, broader reach (25-50km radius), with features like RSVPs and potential payments.
- **Conversion Mechanism**: Posts with sufficient engagement (e.g., 5+ reactions) are suggested for conversion to Events. If engagement continues (e.g., 10+ reactions), the conversion becomes automatic. This flow is managed by the E04 Discovery Engine (initiating) and E03 Activity Management (receiving).

**Tech Stack**:
- **Backend**: Laravel 12
- **Frontend**: Livewire v3, Filament v4 (for admin panels)
- **Database**: PostgreSQL with PostGIS extension for spatial queries
- **UI Framework**: DaisyUI (integrated with Tailwind CSS)
- **Testing**: Pest v4

## Core Architecture Principles

### 1. Posts vs. Events Dual Model
- **Ephemeral Posts**: Encourage spontaneity and real-time interaction.
- **Structured Events**: Provide stability and planning for more significant gatherings.
- **Seamless Conversion**: A key differentiator, allowing organic growth from casual interest to organized activities.
    - **E04 (Discovery Engine)**: Responsible for detecting engagement thresholds on Posts and initiating the conversion suggestion.
    - **E03 (Activity Management)**: Handles the actual creation of an Event, linking it back to the originating Post via `originated_from_post_id`.

### 2. Location-Based Discovery (PostGIS)
- Leveraging PostgreSQL's PostGIS extension for efficient spatial queries.
- **Posts**: Filtered within a 5-10km radius.
- **Events**: Filtered within a 25-50km radius.
- **Implementation**: Use `matanyadaev/laravel-eloquent-spatial` package for Eloquent integration.

### 3. Filament-First for Admin & CRUD
- Filament v4 is the primary tool for building administrative interfaces and managing core data (CRUD operations).
- Custom Livewire components are used for complex user-facing interactions that require more bespoke UI/UX.

### 4. Livewire for Dynamic Frontend
- Livewire v3 is used for building dynamic, reactive user interfaces with Laravel. This minimizes JavaScript and keeps development within the PHP ecosystem.

### 5. Galaxy Theme & Glass Morphism
- A unique, visually distinctive UI/UX is critical.
- **Galaxy Theme**: Dark, space-inspired background with aurora borealis effects and twinkling stars.
- **Glass Morphism**: Semi-transparent, blurred elements with subtle borders, creating a "frosted glass" effect.
- **DaisyUI**: Used as a base UI component library, but heavily customized to fit the galaxy theme.
- **CRITICAL**: All UI must strictly adhere to `context-engine/domain-contexts/ui-design-standards.md`.

## Project Structure & Documentation Hierarchy

```
context-engine/
├── global-context.md           # Universal project context (THIS FILE - READ FIRST)
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

## Implementation Status

- ✅ **E01 Core Infrastructure**: Database, migrations, models, Filament resources **COMPLETE**. This forms the foundation.
- 🔄 **E02-E04**: Task documentation rebuilt for Laravel (Nov 2025), ready for implementation.
- ⏳ **E05-E07**: Epic planning complete, task documentation pending.

## 7 Epics Overview

1.  **E01 Core Infrastructure**: Database (PostGIS), Auth, Notifications - **COMPLETE**
2.  **E02 User & Profile Management**: Profiles, Privacy, User Discovery
3.  **E03 Activity Management**: Event CRUD, RSVPs, Tagging, **Post-to-Event Conversion (receiving)**
4.  **E04 Discovery Engine**: Feeds, Recommendations, **Post-to-Event Conversion (initiating)**
5.  **E05 Social Interaction**: Comments, Reactions, Communities (Groups, Follows)
6.  **E06 Payments & Monetization**: Stripe Connect, Subscriptions
7.  **E07 Administration**: Analytics, Moderation, Monitoring

## Critical Integration Points

### E01 Foundation (Available Now)

**Tables**: `users`, `posts`, `activities`, `post_reactions`, `post_conversions`, `rsvps`, `tags`, `follows`, `notifications`, `comments`, `flares`, `reports`, `groups`, `group_members`, `group_join_requests`, `group_tag`.

**Models**: `User`, `Post`, `Activity`, `PostReaction`, `PostConversion`, `Rsvp`, `Tag`, `Follow`, `Notification`, `Comment`, `Flare`, `Report`, `Group`, `GroupMember`, `GroupJoinRequest`.

**Filament Resources**: `UserResource`, `PostResource`, `ActivityResource`, `RsvpResource`, `TagResource`, `PostReactionResource`, `CommentResource`.

### PostGIS Spatial Queries (E02/F03, E04/F01)

-   **Posts**: 5-10km radius
    ```php
    Post::whereDistance('location_coordinates', $point, '<=', 10000)->get();
    ```
-   **Events**: 25-50km radius
    ```php
    Activity::whereDistance('location_coordinates', $point, '<=', 50000)->get();
    ```

### Post-to-Event Conversion (E03/F01, E04/F03)

-   **E04 detects engagement threshold**:
    ```php
    if ($post->reactions()->count() >= 5) {
        // E04 calls E03's service
        app(ActivityConversionService::class)->createFromPost($post);
    }
    ```
-   **E03 creates activity**:
    ```php
    Activity::create([
        'originated_from_post_id' => $post->id,
        // ... copy location, time hints
    ]);
    ```

## Development Workflow

### Before Starting Any Task

1.  **Read `epic-overview.md`**: Understand the business context and scope of the epic.
2.  **Read `task README.md`**: Get detailed implementation steps, including required files, Artisan commands, and time estimates.
3.  **Check E01 foundation**: Verify available tables, models, and Filament resources that can be leveraged.
4.  **Review `domain-contexts/`**: Pay special attention to `ui-design-standards.md` for UI/UX, `database-context.md` for PostGIS patterns, and `auth-context.md` for authentication/authorization.

### Implementation Pattern

Tasks are broken down into a standard set of steps:

```bash
T01: Database/Model Setup (migrations, models, factories)
T02: Service Classes (business logic)
T03: Filament Resources (admin CRUD)
T04: Livewire Components (user-facing UI)
T05: Policies (authorization)
T06: Jobs (async processing)
T07: Tests (Pest v4)
```

### Always Use

-   `php artisan make:*` commands with the `--no-interaction` flag.
-   `casts()` method (not the `$casts` property) for model attribute casting in Laravel 12.
-   `->components([])` (not `->schema([])`) for Filament v4 forms.
-   PostGIS for location queries via `matanyadaev/laravel-eloquent-spatial`.
-   DaisyUI classes for UI components, customized to the galaxy theme.
-   Galaxy theme with glass morphism for all pages (refer to `ui-design-standards.md`).

### UI Styling - CRITICAL RULES

**EVERY page/component MUST follow the galaxy theme**. No exceptions.

**Step 1: Use the Galaxy Layout Component**
```blade
<x-galaxy-layout>
    <x-slot name="title">Page Title</x-slot>
    <!-- Your content here -->
</x-galaxy-layout>
```
*(Note: `x-galaxy-layout` is a conceptual component. In practice, this means ensuring the base layout (e.g., `resources/views/layouts/app.blade.php` or `auth.blade.php`) includes the galaxy background, aurora, and stars, and your component extends it or includes these elements.)*

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
-   `resources/views/welcome.blade.php` - Full page example
-   `resources/views/livewire/auth/login.blade.php` - Form example
-   `context-engine/domain-contexts/ui-design-standards.md` - Complete guide

**Step 5: Verify Checklist**
-   [ ] Galaxy gradient background
-   [ ] Aurora layers visible
-   [ ] Stars twinkling
-   [ ] Content in glass cards
-   [ ] Buttons have gradients
-   [ ] Forms have cyan focus glow
-   [ ] Text is white/gray (readable)
-   [ ] Hover effects work

## Development Progress Tracking

### Purpose
Maintain timestamped progress logs to serve as a development journal. These logs help new agents quickly understand project history, current state, and planned work without re-reading entire conversation history.

### Log File Management
-   **Location**: `dev-logs/` directory at project root
-   **Naming**: `YYYY-MM-DD-HH.md` (e.g., `2025-01-20-14.md` for January 20, 2025 at 2 PM)
-   **Structure**: Each log file must contain exactly 3 sections:
    1.  **Previously Completed** - Recent accomplishments (last 2-3 sessions)
    2.  **Currently Working On** - Active tasks and current focus
    3.  **Next Steps** - Planned upcoming work and priorities

### When to Update
-   At the beginning of each new work session
-   After completing major tasks or milestones
-   Before ending a work session
-   When switching between major features or epics

### Format Guidelines
-   Use clear, concise bullet points
-   Keep each section to 5-10 bullets maximum for readability
-   Include specific file paths, feature names, and epic references
-   Note any blockers or important decisions made

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

1.  **NO React Native/Supabase/TypeScript** - This is Laravel only.
2.  **Filament First** - Use Filament for CRUD, custom views only when necessary.
3.  **PostGIS for Location** - All spatial queries use PostGIS geography columns.
4.  **Galaxy Theme** - All UI must follow `ui-design-standards.md` (glass cards, aurora effects).
5.  **Posts vs Events** - Always respect the dual model architecture.
6.  **E01 Foundation** - Always reference completed tables/models/resources.
7.  **Test Everything** - Write Pest tests for all features.

## When Lost

1.  Check `context-engine/global-context.md` for big picture.
2.  Check epic `epic-overview.md` for module context.
3.  Check task `README.md` for specific implementation steps.
4.  Check `domain-contexts/` for cross-cutting patterns.
5.  Check E01 implementation for working examples.
The context files provide a good understanding of the project's UI standards, the `Group` model, and the `GroupService`. The `ui-design-standards.md` is particularly detailed about the galaxy theme and glass morphism, which I'll need to apply to the Livewire component's view. The `F03_Groups/README.md` confirms the task and outlines the required Livewire components.

Now I will proceed with creating the Livewire component and its corresponding Blade view.

**Plan:**
1.  Create the Livewire component `app/Livewire/Groups/GroupsIndex.php`.
2.  Implement the `render` method to fetch paginated groups, applying search and privacy filters.
3.  Implement `search()`, `filterByPrivacy()`, `filterByTags()`, `joinGroup()`, and `leaveGroup()` methods.
4.  Create the Blade view `resources/views/livewire/groups/groups-index.blade.php`.
5.  Apply the galaxy theme, glass cards, gradient buttons, and cyan focus glow as per `ui-design-standards.md`.
6.  Ensure pagination, search input, privacy filter, and tag filter are present in the view.
7.  Display group details including name, description, member count, and join/leave buttons.

Let's start by creating the Livewire component file.

I have successfully created the Livewire v3 component for displaying all groups with search and filtering, and its corresponding Blade view.

The component `app/Livewire/Groups/GroupsIndex.php` and the view `resources/views/livewire/groups/groups-index.blade.php` have been updated to include:

*   Paginated list of groups (12 per page).
*   Search by group name and description.
*   Filter by privacy (public/private/all).
*   Filter by tags.
*   Join/leave buttons for each group, dynamically displayed based on user membership.
*   Display of member count and description for each group.
*   The UI adheres to the galaxy theme with glass morphism, gradient buttons, and cyan focus glow on the search input.
*   The component utilizes `GroupService` for joining and leaving groups.