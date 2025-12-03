I will begin by reading the specified files to understand the Groups feature requirements. Following this, I will create a structured checklist outlining the necessary components for implementation, including database models, service classes, UI elements, and testing requirements, differentiating between MVP and future scope items.

--- /Users/ianbruce/Herd/funlynk/context-engine/domain-contexts/service-architecture.md ---
# FunLynk Service Architecture

## Overview
This document outlines the service layer architecture for FunLynk. The service layer is responsible for encapsulating business logic, keeping controllers and Livewire components lean.

## Core Principles

1.  **Single Responsibility**: Each service class should have a single, well-defined responsibility.
2.  **Lean Controllers/Components**: Controllers and Livewire components should only be responsible for handling HTTP requests/responses and user interactions. All business logic should be delegated to service classes.
3.  **Dependency Injection**: Services should be resolved through Laravel's service container to manage dependencies.
4.  **Interface Contracts**: For complex services, define an interface to allow for multiple implementations and easier testing (e.g., `PaymentGatewayInterface`).
5.  **Statelessness**: Services should be stateless whenever possible. Any state should be stored in the database or cache, not within the service class itself.
6.  **Error Handling**: Services should use exceptions for error handling. The calling class (controller, job, etc.) is responsible for catching exceptions and providing an appropriate user response.

## Directory Structure

All service classes are located in the `app/Services` directory. They are organized by domain.

```
app/
└── Services/
    ├── Activity/
    │   ├── ActivityConversionService.php
    │   └── RsvpService.php
    ├── Auth/
    │   ├── OnboardingService.php
    │   └── ProfileService.php
    ├── Discovery/
    │   ├── FeedService.php
    │   └── RecommendationService.php
    ├── Payments/
    │   ├── StripeService.php
    │   └── SubscriptionService.php
    └── Social/
        ├── CommentService.php
        ├── FollowService.php
        └── ReactionService.php
```

## Service Class Structure

A typical service class should look like this:

```php
<?php

namespace App\Services\Domain;

use App\Models\ModelA;
use App\Models\ModelB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CustomException;

class ExampleService
{
    /**
     * The main method to perform the service's action.
     *
     * @param User $user The user performing the action.
     * @param array $data The data required for the action.
     * @return ModelA The result of the action.
     * @throws CustomException If the action fails.
     */
    public function handle(User $user, array $data): ModelA
    {
        // 1. Validate input data (optional, can be done in a FormRequest)
        $this->validateData($data);

        // 2. Perform authorization checks
        if ($user->cannot('performAction', ModelB::class)) {
            throw new CustomException('Unauthorized action.');
        }

        try {
            // 3. Execute core business logic
            $result = ModelA::create([
                'user_id' => $user->id,
                'some_data' => $data['some_key'],
            ]);

            // 4. Dispatch events or jobs
            event(new ActionWasPerformed($result));

            // 5. Log important information
            Log::info('Action performed successfully.', ['result_id' => $result->id]);

            // 6. Return the result
            return $result;

        } catch (\Exception $e) {
            // 7. Handle exceptions and re-throw as a custom exception
            Log::error('Failed to perform action.', ['error' => $e->getMessage()]);
            throw new CustomException('Failed to perform action.');
        }
    }

    /**
     * Validate the incoming data.
     *
     * @param array $data
     * @return void
     */
    private function validateData(array $data): void
    {
        // Validation logic here
    }
}
```

## When to Create a Service

Create a service class when:

-   The business logic is complex and involves multiple steps.
-   The logic needs to be reused in multiple places (e.g., a controller and an Artisan command).
-   The logic involves interacting with external APIs.
-   You need to dispatch events or jobs as part of a business process.
-   You want to improve the testability of your application's business logic.

## How to Use Services

### In a Controller
```php
<?php

namespace App\Http\Controllers;

use App\Services\Domain\ExampleService;
use Illuminate\Http\Request;

class ExampleController extends Controller
{
    public function store(Request $request, ExampleService $exampleService)
    {
        try {
            $result = $exampleService->handle($request->user(), $request->all());
            return response()->json($result, 201);
        } catch (\App\Exceptions\CustomException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
```

### In a Livewire Component
```php
<?php

namespace App\Livewire;

use App\Services\Domain\ExampleService;
use Livewire\Component;

class ExampleComponent extends Component
{
    public $someData;

    public function save(ExampleService $exampleService)
    {
        try {
            $exampleService->handle(auth()->user(), ['some_key' => $this->someData]);
            $this->dispatch('saved');
        } catch (\App\Exceptions\CustomException $e) {
            $this->addError('someData', $e->getMessage());
        }
    }
}
```

### In an Artisan Command
```php
<?php

namespace App\Console\Commands;

use App\Services\Domain\ExampleService;
use Illuminate\Console\Command;

class ExampleCommand extends Command
{
    protected $signature = 'app:example-command';

    public function handle(ExampleService $exampleService)
    {
        // ... logic to get user and data
        $exampleService->handle($user, $data);
        $this->info('Command executed successfully.');
    }
}
```

## Best Practices

-   **Keep services focused**: Avoid creating "god" services that do everything.
-   **Use constructor injection**: For dependencies that are always required.
-   **Use method injection**: For dependencies that are only needed for a specific method.
-   **Return meaningful values**: Services should return the result of the operation (e.g., the created model) or `void` if there's no result.
-   **Don't handle HTTP logic**: Services should not be aware of HTTP requests or responses. Keep them framework-agnostic where possible.
-   **Write tests**: Every service class should have a corresponding feature or unit test.

---
**Last Updated**: 2025-11-20
**Status**: Production Standard - All new business logic must follow this architecture.

--- End of content ---
--- /Users/ianbruce/Herd/funlynk/context-engine/tasks/E05_Social_Interaction/F03_Groups_Feature/README.md ---
# E05 Social Interaction / F03 Groups Feature

## 1. Feature Overview

This feature introduces "Groups" to FunLynk, allowing users to form communities around shared interests, locations, or specific activities. Groups will serve as hubs for communication, event planning, and member interaction, fostering a stronger sense of community on the platform.

**Business Goal**: Increase user engagement and retention by providing a dedicated space for like-minded users to connect and organize.

## 2. MVP Scope

The initial implementation will focus on core group functionality.

-   **Group Creation**: Users can create public or private groups.
-   **Group Management**: Group owners can edit group details, manage members (approve/kick), and delete the group.
-   **Membership**: Users can request to join private groups or instantly join public groups.
-   **Group Feed**: A simple chronological feed of posts and events created within the group.
-   **Group Events**: Ability to create an event that is exclusive to group members.

## 3. Future Scope

-   **Group Chat**: Real-time chat functionality within groups.
-   **Member Roles**: Differentiated roles (admin, moderator, member).
-   **Group Discovery**: Enhanced search and recommendation for groups.
-   **Customization**: Group banners, custom colors, and branding.
-   **Sub-groups/Channels**: Nested channels within a larger group for specific topics.

## 4. Database Schema

### `groups` table
-   `id` (ulid, primary)
-   `name` (string)
-   `slug` (string, unique)
-   `description` (text)
-   `profile_image_path` (string, nullable)
-   `banner_image_path` (string, nullable)
-   `owner_id` (foreign ulid for `users`)
-   `type` (enum: `public`, `private`) - *MVP*
-   `location_coordinates` (point, srid 4326, nullable) - *Future Scope*
-   `timestamps`

### `group_members` table
-   `id` (ulid, primary)
-   `group_id` (foreign ulid for `groups`)
-   `user_id` (foreign ulid for `users`)
-   `role` (enum: `owner`, `admin`, `moderator`, `member`) - *Default to 'member' or 'owner' in MVP*
-   `status` (enum: `pending`, `approved`, `banned`) - *For private group requests*
-   `timestamps`

### `group_invitations` table - *Future Scope*
-   `id` (ulid, primary)
-   `group_id` (foreign ulid for `groups`)
-   `inviter_id` (foreign ulid for `users`)
-   `email` (string) - For inviting non-users
-   `user_id` (foreign ulid for `users`, nullable) - For inviting existing users
-   `token` (string, unique)
-   `status` (enum: `pending`, `accepted`, `declined`)
-   `expires_at` (timestamp)
-   `timestamps`

### `posts` table (Polymorphic Relationship)
-   Add `group_id` (foreign ulid for `groups`, nullable) to associate posts with a group.

### `activities` table (Polymorphic Relationship)
-   Add `group_id` (foreign ulid for `groups`, nullable) to associate events with a group.

## 5. Implementation Plan

### T01: Database & Models (2 hours)
1.  **Migration**: Create migration for `groups` table.
2.  **Migration**: Create migration for `group_members` table.
3.  **Migration**: Add `group_id` to `posts` and `activities` tables.
4.  **Model**: Create `App\Models\Group` model with relationships (`owner`, `members`, `posts`, `activities`).
5.  **Model**: Create `App\Models\GroupMember` model with relationships (`group`, `user`).
6.  **Model**: Update `User` model to include `groups` and `ownedGroups` relationships.
7.  **Model**: Update `Post` and `Activity` models to include `group` relationship.
8.  **Factory**: Create factories for `Group` and `GroupMember`.

### T02: Service Classes (3 hours)
1.  **`GroupService` (`App\Services\Social\GroupService`)**:
    -   `createGroup(User $user, array $data)`: Creates a group and sets the user as the owner.
    -   `updateGroup(Group $group, array $data)`: Updates group details.
    -   `deleteGroup(Group $group)`: Deletes a group.
2.  **`GroupMembershipService` (`App\Services\Social\GroupMembershipService`)**:
    -   `joinGroup(User $user, Group $group)`: Handles joining a public group or requesting to join a private group.
    -   `leaveGroup(User $user, Group $group)`: Handles leaving a group.
    -   `approveRequest(User $approver, GroupMember $request)`: Approves a pending membership request.
    -   `rejectRequest(User $rejector, GroupMember $request)`: Rejects a pending membership request.
    -   `removeMember(User $remover, Group $group, User $member)`: Kicks a member from a group.

### T03: Filament Resources (4 hours)
1.  **`GroupResource`**:
    -   CRUD functionality for managing groups in the admin panel.
    -   **Form**: Fields for `name`, `description`, `owner_id`, `type`.
    -   **Table**: Columns for `name`, `owner`, `type`, member count.
    -   **Relations Manager**: `GroupMemberRelationManager` to view and manage members of a group.
    -   **Actions**: View group on the frontend.

### T04: Livewire Components (8 hours)
1.  **`Groups/CreateGroup`**: A full-page component with a form to create a new group.
2.  **`Groups/EditGroup`**: A full-page component to edit group details (accessible to owner).
3.  **`Groups/GroupDirectory`**: A page to list all public groups with search and filtering.
4.  **`Groups/GroupHeader`**: Component for the group's main page, showing banner, profile image, name, description, and join/leave buttons.
5.  **`Groups/GroupFeed`**: Component to display posts and events associated with the group.
6.  **`Groups/GroupMembers`**: Component to list all members of a group. For owners, this will include options to manage members.
7.  **`Groups/ManageJoinRequests`**: A section for group owners to view and approve/deny join requests for private groups.

### T05: API & Routes (2 hours)
-   **Web Routes (`routes/web.php`)**:
    -   `/groups`: `GroupDirectory` component.
    -   `/groups/create`: `CreateGroup` component.
    -   `/groups/{group:slug}`: Group profile page (`GroupHeader`, `GroupFeed`, etc.).
    -   `/groups/{group:slug}/edit`: `EditGroup` component.
    -   `/groups/{group:slug}/members`: `GroupMembers` component.

### T06: Authorization (2 hours)
1.  **`GroupPolicy`**:
    -   `viewAny(User $user)`: Any user can view the group directory.
    -   `view(User $user, Group $group)`: Public groups are viewable by all. Private groups are viewable only by members.
    -   `create(User $user)`: Any authenticated user can create a group.
    -   `update(User $user, Group $group)`: Only the group owner can update.
    -   `delete(User $user, Group $group)`: Only the group owner can delete.
    -   `manageMembers(User $user, Group $group)`: Only the group owner can manage members.
    -   `addPost(User $user, Group $group)`: Only group members can post.

### T07: UI/UX Implementation (5 hours)
-   All components must adhere to the **Galaxy Theme** and **Glass Morphism** standards (`ui-design-standards.md`).
-   **Group Directory**: A grid of glass cards, each representing a group.
-   **Group Page**:
    -   A large glass card for the header with a banner image background.
    -   Group profile image, name, and stats overlaid on the banner.
    -   Tabs for Feed, Members, Events.
    -   Feed items (posts/events) will be individual glass cards.
-   **Forms**: All forms (create, edit) must use the standard glass input fields with cyan focus glow.
-   **Buttons**: Use gradient buttons for primary actions (Create, Join) and secondary glass buttons for other actions (Leave, Cancel).

### T08: Testing (4 hours)
1.  **Pest Feature Tests**:
    -   Test that users can create public and private groups.
    -   Test that users can join and leave public groups.
    -   Test that users can request to join private groups and that owners can approve/deny.
    -   Test that group owners can update and delete their groups.
    -   Test that only members can see private group content.
    -   Test authorization policies (e.g., non-owners cannot edit).
2.  **Pest Component Tests**:
    -   Test that Livewire components render correctly.
    -   Test form submissions and validation in `CreateGroup` and `EditGroup`.
    -   Test the join/leave button logic in `GroupHeader`.

## 6. Artisan Commands

```bash
# T01: Migrations & Models
php artisan make:migration create_groups_table
php artisan make:migration create_group_members_table
php artisan make:migration add_group_id_to_posts_table
php artisan make:migration add_group_id_to_activities_table
php artisan make:model Group -mf
php artisan make:model GroupMember -mf

# T02: Services
# (Create files manually)

# T03: Filament
php artisan make:filament-resource Group --generate

# T04: Livewire
php artisan make:livewire Groups/CreateGroup --full
php artisan make:livewire Groups/EditGroup --full
php artisan make:livewire Groups/GroupDirectory
php artisan make:livewire Groups/GroupHeader
php artisan make:livewire Groups/GroupFeed
php artisan make:livewire Groups/GroupMembers
php artisan make:livewire Groups/ManageJoinRequests

# T06: Policy
php artisan make:policy GroupPolicy --model=Group

# T08: Tests
php artisan make:test Feature/GroupManagementTest --pest
php artisan make:test Feature/GroupMembershipTest --pest
php artisan make:test Livewire/Groups/CreateGroupTest --pest
```

--- End of content ---
Here is the analysis and structured checklist for the Groups feature implementation:

### 1. Database Tables & Models

*   **`groups` table** (MVP)
    *   Model: `App\Models\Group`
    *   Relations: `owner()` (User), `members()` (User), `posts()` (Post), `activities()` (Activity)
*   **`group_members` table** (MVP)
    *   Model: `App\Models\GroupMember`
    *   Relations: `group()` (Group), `user()` (User)
*   **`posts` table modification** (MVP)
    *   Add nullable `group_id` foreign key.
    *   Update `App\Models\Post` to include `group()` relationship.
*   **`activities` table modification** (MVP)
    *   Add nullable `group_id` foreign key.
    *   Update `App\Models\Activity` to include `group()` relationship.
*   **`users` table modification** (MVP)
    *   Update `App\Models\User` to include `groups()` and `ownedGroups()` relationships.
*   **`group_invitations` table** (Future Scope)

### 2. Service Classes

*   **`App\Services\Social\GroupService`** (MVP)
    *   `createGroup(User $user, array $data)`
    *   `updateGroup(Group $group, array $data)`
    *   `deleteGroup(Group $group)`
*   **`App\Services\Social\GroupMembershipService`** (MVP)
    *   `joinGroup(User $user, Group $group)`
    *   `leaveGroup(User $user, Group $group)`
    *   `approveRequest(User $approver, GroupMember $request)`
    *   `rejectRequest(User $rejector, GroupMember $request)`
    *   `removeMember(User $remover, Group $group, User $member)`

### 3. Filament Resources

*   **`GroupResource`** (MVP)
    *   Manages `Group` model CRUD.
    *   Includes a `GroupMemberRelationManager` to manage members within the group resource view.

### 4. Livewire Components

*   **`Groups/CreateGroup`** (MVP) - Full-page component for new group creation.
*   **`Groups/EditGroup`** (MVP) - Full-page component for editing group details.
*   **`Groups/GroupDirectory`** (MVP) - Page to list and search public groups.
*   **`Groups/GroupHeader`** (MVP) - Displays group banner, profile, name, and action buttons (join/leave).
*   **`Groups/GroupFeed`** (MVP) - Displays posts and events from within the group.
*   **`Groups/GroupMembers`** (MVP) - Lists group members with management options for owners.
*   **`Groups/ManageJoinRequests`** (MVP) - A view for group owners to handle pending membership requests.

### 5. API Endpoints & Routes

*   **`/groups`** (MVP) -> `Groups/GroupDirectory`
*   **`/groups/create`** (MVP) -> `Groups/CreateGroup`
*   **`/groups/{group:slug}`** (MVP) -> Group profile page shell, loading `GroupHeader`, `GroupFeed`, etc.
*   **`/groups/{group:slug}/edit`** (MVP) -> `Groups/EditGroup`
*   **`/groups/{group:slug}/members`** (MVP) -> `Groups/GroupMembers`

### 6. UI/UX Requirements

*   **Overall Theme**: Must strictly adhere to the **Galaxy Theme** as defined in `ui-design-standards.md`. (MVP)
*   **Glass Morphism**: All content containers, cards, and forms must use the `glass-card` style with blurred backgrounds and subtle borders. (MVP)
*   **Color Palette**: Use the defined galaxy/aurora color palette for backgrounds, text, and accents. (MVP)
*   **Background Effects**: All pages must implement the galaxy gradient background with animated aurora layers and twinkling stars. (MVP)
*   **Buttons**: Primary actions (e.g., "Create Group", "Join") must use gradient buttons. Secondary actions (e.g., "Cancel", "Leave") must use secondary glass buttons. (MVP)
*   **Forms**: All inputs must have the standard glass style with a cyan border glow on focus. (MVP)
*   **Layouts**:
    *   The `GroupDirectory` will be a responsive grid of `glass-card`s. (MVP)
    *   The main group page will feature a large `glass-card` header with a banner image, with other components like the feed appearing below in separate cards. (MVP)

### 7. Authorization & Policy Requirements

*   **`GroupPolicy`** (MVP)
    *   `viewAny`: All users.
    *   `view`: All users for public groups, members only for private groups.
    *   `create`: Any authenticated user.
    *   `update`: Group owner only.
    *   `delete`: Group owner only.
    *   `manageMembers`: Group owner only.
    *   `addPost`: Group members only.

### 8. Testing Requirements

*   **Feature Tests (Pest)** (MVP)
    *   Verify group creation (public/private), updating, and deletion by owners.
    *   Verify membership logic: joining/leaving public groups, requesting/approving/denying private group access.
    *   Verify authorization policies prevent unauthorized actions.
*   **Livewire Component Tests (Pest)** (MVP)
    *   Test form validation and submission for `CreateGroup` and `EditGroup`.
    *   Test interactive elements like the join/leave buttons in `GroupHeader`.
    *   Ensure components render correctly with appropriate data.