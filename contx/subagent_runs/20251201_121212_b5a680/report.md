--- /Users/ianbruce/Herd/funlynk/context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md ---

# Task: F03_Groups - Group Management Features

## Epic: E05_Social_Interaction

## Feature Overview

This feature implements comprehensive group management capabilities within FunLynk. Users will be able to create, join, manage, and interact within groups. Groups serve as persistent communities for shared interests, distinct from ephemeral Posts and structured Events.

## Implementation Tasks

This feature is broken down into 7 sub-tasks (T01-T07). Each task should be implemented sequentially.

---

### T01: Database & Model Setup (Group, GroupMember, GroupJoinRequest)

**Objective**: Create necessary database migrations and Eloquent models for Group, GroupMember, and GroupJoinRequest.

**Details**:
- **`groups` table**:
    - `id` (UUID)
    - `name` (string, unique)
    - `slug` (string, unique, generated from name)
    - `description` (text, nullable)
    - `avatar_url` (string, nullable)
    - `cover_image_url` (string, nullable)
    - `privacy` (enum: `public`, `private`, `secret` - default `public`)
    - `auto_approve_members` (boolean, default `false`)
    - `member_count` (integer, default `0`)
    - `created_by` (UUID, foreign key to `users.id`)
    - `timestamps`, `softDeletes`
- **`group_members` table**: (Pivot table for `users` and `groups`)
    - `id` (UUID)
    - `group_id` (UUID, foreign key to `groups.id`)
    - `user_id` (UUID, foreign key to `users.id`)
    - `role` (enum: `member`, `admin` - default `member`)
    - `status` (enum: `pending`, `approved`, `denied` - default `approved`)
    - `joined_at` (timestamp, default `now()`)
    - `timestamps`
    - Unique constraint on `group_id` and `user_id`
- **`group_join_requests` table**:
    - `id` (UUID)
    - `group_id` (UUID, foreign key to `groups.id`)
    - `user_id` (UUID, foreign key to `users.id`)
    - `status` (enum: `pending`, `approved`, `denied` - default `pending`)
    - `timestamps`
    - Unique constraint on `group_id` and `user_id`
- **Models**: `Group`, `GroupMember`, `GroupJoinRequest` with `HasUuids`, `HasFactory`, `SoftDeletes` (for Group), relationships, and `casts()` method.
- **Factories**: Create factories for `Group`, `GroupMember`, `GroupJoinRequest`.

**Artisan Commands**:
```bash
php artisan make:migration create_groups_table --no-interaction
php artisan make:migration create_group_members_table --no-interaction
php artisan make:migration create_group_join_requests_table --no-interaction
php artisan make:model Group --factory --no-interaction
php artisan make:model GroupMember --factory --no-interaction
php artisan make:model GroupJoinRequest --factory --no-interaction
```

**Time Estimate**: 2 hours

---

### T02: GroupService & Core Logic

**Objective**: Implement core business logic for group management within `app/Services/GroupService.php`.

**Details**:
- **`createGroup(User $user, array $data)`**: Creates a new group, adds the creator as an admin member.
- **`updateGroup(Group $group, array $data)`**: Updates group details.
- **`deleteGroup(Group $group)`**: Deletes a group and associated memberships, join requests, posts, activities, and conversation.
- **`addMember(Group $group, User $user, string $role = 'member')`**: Adds a user as a member to a group.
- **`removeMember(Group $group, User $user)`**: Removes a member from a group.
- **`updateMemberRole(Group $group, User $user, string $role)`**: Changes a member's role (member/admin).
- **`searchPublicGroups(string $query, ?array $tagIds = null)`**: Searches public groups by name/description and optional tags.
- **`getGroupsByTag(Tag $tag)`**: Retrieves public groups associated with a specific tag.
- **`getUserGroups(User $user)`**: Retrieves all groups a user is a member of.
- **`getGroupMembers(Group $group)`**: Retrieves all members of a specific group.
- **`syncGroupTags(Group $group, array $tagIds)`**: Syncs tags for a group.
- **`createJoinRequest(Group $group, User $user)`**: Creates a join request for a private/secret group.
- **`approveJoinRequest(GroupJoinRequest $request, User $admin)`**: Approves a join request, adding the user as a member.
- **`denyJoinRequest(GroupJoinRequest $request, User $admin)`**: Denies a join request.
- **`isGroupMember(Group $group, User $user)`**: Checks if a user is a member of a group.
- **`isGroupAdmin(Group $group, User $user)`**: Checks if a user is an admin of a group.
- **Events**: Dispatch `GroupCreated`, `GroupMemberJoined`, `GroupMemberRemoved`, `GroupJoinRequestApproved`, `GroupJoinRequestReceived` events.

**Time Estimate**: 4 hours

---

### T03: Filament Resources (Group, GroupMember, GroupJoinRequest)

**Objective**: Create Filament v4 resources for managing Groups, Group Members, and Group Join Requests in the admin panel.

**Details**:
- **`GroupResource`**:
    - List, Create, Edit, View pages.
    - Fields for `name`, `slug`, `description`, `avatar_url`, `cover_image_url`, `privacy`, `auto_approve_members`, `created_by`.
    - Table columns for `name`, `privacy`, `member_count`, `created_by`, `created_at`.
    - Relationships: `members`, `joinRequests`, `posts`, `activities`, `tags`.
    - Actions: Edit, Delete.
- **`GroupMemberResource`**:
    - List, Create, Edit, View pages.
    - Fields for `group_id`, `user_id`, `role`, `joined_at`.
    - Table columns for `group.name`, `user.name`, `role`, `joined_at`.
    - Actions: Edit, Delete.
- **`GroupJoinRequestResource`**:
    - List, Create, Edit, View pages.
    - Fields for `group_id`, `user_id`, `status`.
    - Table columns for `group.name`, `user.name`, `status`, `created_at`.
    - Actions: Approve, Deny (custom actions).

**Artisan Commands**:
```bash
php artisan make:filament-resource Group --generate --no-interaction
php artisan make:filament-resource GroupMember --generate --no-interaction
php artisan make:filament-resource GroupJoinRequest --generate --no-interaction
```

**Time Estimate**: 6 hours

---

### T04: Livewire Components (User-Facing UI)

**Objective**: Develop user-facing Livewire components for group interaction.

**Details**:
- **`Groups/GroupList`**: Displays a list of public groups, with search and filter options.
- **`Groups/GroupShow`**: Displays a single group's details, including members, posts, and activities.
- **`Groups/GroupCreate`**: Form for creating a new group.
- **`Groups/GroupEdit`**: Form for editing an existing group.
- **`Groups/GroupMembers`**: Manages group members (list, search, remove, change role, invite).
- **`Groups/GroupJoinRequests`**: Manages join requests for private/secret groups (list, approve, deny).

**UI Requirements**:
- All components must adhere to `ui-design-standards.md` (galaxy theme, glass morphism, gradient buttons).
- Responsive design.

**Artisan Commands**:
```bash
php artisan make:livewire Groups/GroupList --no-interaction
php artisan make:livewire Groups/GroupShow --no-interaction
php artisan make:livewire Groups/GroupCreate --no-interaction
php artisan make:livewire Groups/GroupEdit --no-interaction
php artisan make:livewire Groups/GroupMembers --no-interaction
php artisan make:livewire Groups/GroupJoinRequests --no-interaction
```

**Time Estimate**: 10 hours

---

### T05: Policies (Group, GroupMember, GroupJoinRequest)

**Objective**: Implement authorization policies for Group, GroupMember, and GroupJoinRequest.

**Details**:
- **`GroupPolicy`**:
    - `viewAny`, `view`, `create`, `update`, `delete` (only creator or admin can update/delete).
    - `join`, `leave` (can join if not member, can leave if member).
    - `manageMembers`, `manageJoinRequests` (only admins).
- **`GroupMemberPolicy`**:
    - `viewAny`, `view` (any group member).
    - `create` (handled by `GroupPolicy::join`).
    - `update`, `delete` (only group admin can remove/change role of other members).
- **`GroupJoinRequestPolicy`**:
    - `viewAny`, `view` (only group admin or requesting user).
    - `create` (only non-members).
    - `approve`, `deny` (only group admin).

**Artisan Commands**:
```bash
php artisan make:policy GroupPolicy --model=Group --no-interaction
php artisan make:policy GroupMemberPolicy --model=GroupMember --no-interaction
php artisan make:policy GroupJoinRequestPolicy --model=GroupJoinRequest --no-interaction
```

**Time Estimate**: 3 hours

---

### T06: Jobs & Notifications

**Objective**: Implement background jobs and notifications for group-related actions.

**Details**:
- **Jobs**:
    - `ProcessGroupMemberCount` (updates `member_count` on group after member changes).
    - `SendGroupNotification` (generic job for sending various group notifications).
- **Notifications**:
    - `GroupCreatedNotification` (to creator).
    - `GroupMemberJoinedNotification` (to group admins).
    - `GroupMemberRemovedNotification` (to removed user).
    - `GroupJoinRequestReceivedNotification` (to group admins).
    - `GroupJoinRequestApprovedNotification` (to requesting user).
    - `GroupJoinRequestDeniedNotification` (to requesting user).

**Artisan Commands**:
```bash
php artisan make:job ProcessGroupMemberCount --no-interaction
php artisan make:notification GroupCreatedNotification --no-interaction
# ... other notifications
```

**Time Estimate**: 4 hours

---

### T07: Tests (Pest v4)

**Objective**: Write comprehensive Pest tests for all new group features.

**Details**:
- **Unit Tests**: For `GroupService` methods.
- **Feature Tests**: For Livewire components and Filament resources.
- **Policy Tests**: For authorization logic.
- **Event/Job Tests**: Ensure events are dispatched and jobs are queued correctly.

**Artisan Commands**:
```bash
php artisan make:test --pest Unit/Services/GroupServiceTest --no-interaction
php artisan make:test --pest Feature/Livewire/Groups/GroupMembersTest --no-interaction
# ... other tests
```

**Time Estimate**: 8 hours

---

## Total Estimated Time: 38 hours

---

## Dependencies

- **E01 Core Infrastructure**: Requires `User` model, basic authentication.
- **E04 Discovery Engine**: Will integrate with group search and recommendations.
- **E05 Social Interaction**: Posts, Comments, Reactions will be integrated within groups.

## UI/UX Notes

- Group pages should have a clear hierarchy: Group details, then tabs for Members, Posts, Activities, Settings.
- Admin controls should be clearly visible but distinct from regular member actions.
- Use toasts/notifications for feedback on actions (e.g., "Member removed successfully").
- Empty states for member lists, posts, etc., should be handled gracefully.

## Definition of Done

- All 7 tasks completed and reviewed.
- All tests passing.
- UI adheres to `ui-design-standards.md`.
- Code is clean, well-documented, and follows Laravel conventions.
- No regressions introduced.
- Performance is acceptable.
- Security considerations addressed (policies, input validation).

---

## Agent Notes

- **Prioritize UI/UX**: The `ui-design-standards.md` is critical. Ensure every component looks and feels like FunLynk.
- **Filament First**: For admin CRUD, always use Filament. Only build custom Livewire forms for user-facing interactions.
- **Service Layer**: All business logic must reside in `GroupService.php`.
- **Authorization**: Implement policies rigorously.
- **Events**: Dispatch events for all significant group actions to allow for future extensibility.
- **Testing**: Write tests for everything. This is a complex feature, and robust testing is essential.
- **Iterative Development**: Tackle one sub-task at a time, ensuring it's fully working and tested before moving to the next.
- **PostGIS**: While not directly in this task, remember that location-based features will use PostGIS. Ensure group models are compatible if location data is ever added.
- **Laravel 12 Specifics**: Use `casts()` method, not `$casts` property. Use `->components([])` for Filament, not `->schema([])`.

---
The context files provide a good understanding of the project's UI standards, the `Group` and `GroupMember` models, and the `GroupService`. I can now proceed with creating the Livewire component and its view.

First, I'll create the PHP component file.
I have created the Livewire component and its view, and updated the development log.