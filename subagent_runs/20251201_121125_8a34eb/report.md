--- /Users/ianbruce/Herd/funlynk/context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md ---

# Task: F03_Groups - Group Management and Interaction

## Epic: E05 Social Interaction

## Feature Overview

This feature enables users to create, manage, discover, and interact within groups. Groups provide a persistent space for communities around specific interests or activities, offering a more structured and long-term interaction model compared to ephemeral Posts.

## Key Functionality

1.  **Group Creation**: Users can create new groups, defining name, description, privacy settings (public/private), and initial tags.
2.  **Group Details & Display**: View a single group's profile, including members, admins, recent activity (posts/events within the group), and a join/leave mechanism.
3.  **Group Discovery**: Search and browse public groups by name, description, and tags.
4.  **Membership Management**:
    *   Users can join public groups directly.
    *   For private groups, users must send a join request, which group admins can approve or deny.
    *   Admins can invite users, approve/deny join requests, and manage member roles (promote to admin, demote to member, remove member).
5.  **Group Interaction**:
    *   Group members can create posts and events directly within the group.
    *   A dedicated group feed displays content relevant to the group.
    *   Group-specific chat/conversation.
6.  **Admin Tools**: Admins have elevated privileges for group settings, content moderation, and member management.

## Implementation Tasks (T01-T07)

### T01: Database & Model Setup (Complete)

-   **Migrations**: `groups`, `group_members`, `group_join_requests`, `group_tag` (pivot)
-   **Models**: `Group`, `GroupMember`, `GroupJoinRequest`
-   **Relationships**: Defined in `app/Models/Group.php`, `app/Models/User.php`, etc.
-   **Factories**: `GroupFactory`, `GroupMemberFactory`, `GroupJoinRequestFactory`

### T02: Service Classes (GroupService)

-   **`GroupService.php`**: Centralized business logic for group operations.
    -   `createGroup(User $user, array $data)`: Creates a new group and adds the creator as an admin.
    -   `updateGroup(Group $group, array $data)`: Updates group details.
    -   `deleteGroup(Group $group)`: Deletes a group and associated data.
    -   `addMember(Group $group, User $user, string $role = 'member')`: Adds a user to a group.
    -   `removeMember(Group $group, User $user)`: Removes a user from a group.
    -   `updateMemberRole(Group $group, User $user, string $role)`: Changes a member's role.
    -   `searchPublicGroups(string $query, ?array $tagIds = null)`: Searches public groups.
    -   `getGroupsByTag(Tag $tag)`: Retrieves public groups associated with a tag.
    -   `getUserGroups(User $user)`: Retrieves all groups a user is a member of.
    -   `getGroupMembers(Group $group)`: Retrieves all members of a group.
    -   `syncGroupTags(Group $group, array $tagIds)`: Syncs tags for a group.
    -   `createJoinRequest(Group $group, User $user)`: Creates a join request for a private group.
    -   `approveJoinRequest(GroupJoinRequest $request, User $admin)`: Approves a join request.
    -   `denyJoinRequest(GroupJoinRequest $request, User $admin)`: Denies a join request.
    -   `isGroupMember(Group $group, User $user)`: Checks if a user is a member.
    -   `isGroupAdmin(Group $group, User $user)`: Checks if a user is an admin.

### T03: Filament Resources (Admin CRUD)

-   **`GroupResource.php`**: Filament resource for managing groups in the admin panel.
    -   Forms for creating/editing groups (name, description, privacy, avatar, cover, tags).
    -   Table for listing groups with search, filters, and actions.
    -   Relation managers for members, join requests, posts, activities.
-   **`GroupMemberResource.php`**: For managing group memberships.
-   **`GroupJoinRequestResource.php`**: For managing join requests.

### T04: Livewire Components (User-Facing UI)

-   **`Groups/GroupIndex.php`**: Displays a list of groups (user's groups, public groups).
    -   Search and filter by tags.
    -   Pagination.
    -   "Create Group" button.
-   **`Groups/GroupShow.php`**: Displays a single group's details.
    -   Group name, description, privacy, tags.
    -   Member count, admin list.
    -   Conditional Join/Leave button.
    -   Conditional Edit/Delete button (for admins).
    -   Group timeline preview (3 latest posts/events).
    -   Member list preview (6 members).
    -   Link to full timeline and member list.
-   **`Groups/GroupCreate.php`**: Form for creating a new group.
-   **`Groups/GroupEdit.php`**: Form for editing an existing group.
-   **`Groups/GroupJoinRequests.php`**: Lists pending join requests for admins.
-   **`Groups/GroupMembers.php`**: Lists all members of a group with role management for admins.
-   **`Groups/GroupPostCreate.php`**: Form for creating a post within a group.
-   **`Groups/GroupEventCreate.php`**: Form for creating an event within a group.

### T05: Policies (Authorization)

-   **`GroupPolicy.php`**: Defines authorization logic for groups.
    -   `viewAny`, `view`, `create`, `update`, `delete`, `join`, `leave`, `manageMembers`, `manageRequests`.
-   **`GroupMemberPolicy.php`**: For managing individual memberships.
-   **`GroupJoinRequestPolicy.php`**: For managing join requests.

### T06: Jobs (Async Processing)

-   **`ProcessGroupAvatarUpload.php`**: Handles avatar/cover image uploads.
-   **`NotifyGroupMembers.php`**: Notifies members of new group activity.

### T07: Tests (Pest v4)

-   **`Feature/GroupManagementTest.php`**: Tests for group CRUD, membership, join requests.
-   **`Livewire/Groups/GroupShowTest.php`**: Tests for the `GroupShow` component.
-   **`Policy/GroupPolicyTest.php`**: Tests for group authorization.

## Time Estimates

-   T01: 2 hours (Complete)
-   T02: 6 hours
-   T03: 8 hours
-   T04: 12 hours
-   T05: 4 hours
-   T06: 3 hours
-   T07: 10 hours

## Dependencies

-   **E01 Core Infrastructure**: User model, Auth system, Notifications.
-   **E03 Activity Management**: Posts, Activities, Tags (for group content).
-   **E04 Discovery Engine**: For group search and recommendations.

## UI/UX Notes

-   Follow `ui-design-standards.md` for all UI elements.
-   Use Filament for admin interfaces, Livewire for user-facing.
-   Ensure responsive design for all components.

## Artisan Commands to Use

```bash
php artisan make:livewire Groups/GroupShow --no-interaction
php artisan make:filament-resource Group --generate --no-interaction
php artisan make:policy GroupPolicy --model=Group --no-interaction
php artisan make:job ProcessGroupAvatarUpload --no-interaction
php artisan make:test Feature/GroupManagementTest --pest --no-interaction
```

--- End of content ---
The context files have been read. I have a good understanding of the UI standards, the Group model, GroupService, and the specific requirements for the `GroupShow` Livewire component.

Now, I will create the Livewire component PHP file and the Blade view file.

The Livewire component `app/Livewire/Groups/GroupShow.php` and its corresponding Blade view `resources/views/livewire/groups/group-show.blade.php` have been created and updated to meet all specified requirements, including UI standards, conditional rendering, and integration with the `GroupService`. The `member_count` column was confirmed to exist in the `groups` table migration.