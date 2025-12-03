--- /Users/ianbruce/Herd/funlynk/context-engine/tasks/E05_Social_Interaction/F03_Groups_Feature/README.md ---
# E05 Social Interaction / F03 Groups Feature

## 1. Feature Description

This feature introduces **Groups** to FunLynk, creating dedicated spaces for users to connect around shared interests, activities, or identities. Groups will have their own feeds for Posts and Activities, a dedicated chat, and member management capabilities. This fosters deeper community engagement beyond individual Posts and Activities.

## 2. User Stories & Use Cases

- **As a user, I want to create a group** for my local hiking club so we can organize our weekly hikes and share photos in one place.
- **As a user, I want to search for and discover groups** based on my interests (e.g., "Board Games," "Yoga in the Park") so I can connect with like-minded people.
- **As a user, I want to join a group** to see its content, participate in discussions, and be notified of its activities.
- **As a group member, I want to create Posts and Activities that are visible only to my group**, providing a more private space for coordination.
- **As a group admin, I want to manage members** (approve requests, remove members) to maintain the community's quality and safety.
- **As a group member, I want to participate in a group-specific chat** for real-time conversations and quick coordination.

## 3. MVP Scope

### Included in MVP:
-   **Group Creation**: Users can create public or private groups with a name, description, and banner image.
-   **Group Discovery**: A searchable index of all public groups.
-   **Membership Management**:
    -   Public Groups: Users can join freely.
    -   Private Groups: Users must request to join, and an admin must approve.
    -   Admins can remove members.
    -   Members can leave a group.
-   **Group Content**:
    -   Members can create Posts and Activities scoped to the group.
    -   A dedicated feed within the group to view these Posts and Activities.
-   **Group Roles**:
    -   **Admin**: The creator of the group. Can edit group details, manage members, and delete the group.
    -   **Member**: Can view and create content within the group.
-   **Group Chat**: A single, persistent chat channel for all members of a group.

### Not Included in MVP (Future Enhancements):
-   Multiple Admins or Moderator roles.
-   Group-specific events calendar.
-   Advanced content moderation tools for admins.
-   Sub-channels within a group chat.
-   Group analytics.
-   Integration with external platforms (e.g., Discord, Facebook Groups).

## 4. Technical Implementation Plan

This feature requires new database tables, models, Livewire components for the UI, and service classes to handle the business logic.

### T01: Database & Models
1.  **Create `groups` table migration**:
    -   `id` (PK)
    -   `name` (string)
    -   `description` (text)
    -   `banner_image_url` (string, nullable)
    -   `is_private` (boolean, default: false)
    -   `created_by` (foreign key to `users`)
    -   `timestamps`
2.  **Create `group_members` pivot table migration**:
    -   `id` (PK)
    -   `group_id` (foreign key to `groups`)
    -   `user_id` (foreign key to `users`)
    -   `role` (string, e.g., 'admin', 'member')
    -   `timestamps`
3.  **Create `Group` and `GroupMember` models**:
    -   Define relationships:
        -   `Group` hasMany `GroupMember`
        -   `Group` belongsTo `User` (creator)
        -   `Group` hasMany `Post` (polymorphic or dedicated FK)
        -   `Group` hasMany `Activity` (polymorphic or dedicated FK)
        -   `User` hasMany `GroupMember`
4.  **Modify `posts` and `activities` tables**:
    -   Add a nullable `group_id` foreign key to both tables. This is the simplest approach for the MVP to scope content to a group.

### T02: Service Classes
1.  **`GroupService.php`**:
    -   `createGroup(User $user, array $data)`: Handles creation of a group and assigning the creator as the first admin.
    -   `joinGroup(User $user, Group $group)`: Handles joining a public group or creating a join request for a private one.
    -   `approveJoinRequest(User $admin, User $user, Group $group)`: Approves a request.
    -   `leaveGroup(User $user, Group $group)`: Handles leaving a group.
    -   `removeMember(User $admin, User $member, Group $group)`: Handles removing a member.

### T03: Filament Resources (for Admin Panel)
1.  **`GroupResource.php`**:
    -   Allow site administrators to view, edit, and delete any group.
    -   Manage members and their roles from the Filament panel.

### T04: Livewire Components (User-Facing UI)
1.  **`Groups/CreateGroup.php`**: A full-page component with a form to create a new group.
2.  **`Groups/GroupIndex.php`**: A page to search and browse all public groups.
3.  **`Groups/ViewGroup.php`**: The main page for a single group. It will contain nested components:
    -   A header with group info and join/leave buttons.
    -   A tabbed interface for:
        -   **Feed**: Displays group-specific Posts and Activities. (`Groups/GroupFeed.php`)
        -   **Chat**: The group chat interface. (`Groups/GroupChat.php`)
        -   **Members**: A list of group members. (`Groups/GroupMembers.php`)
4.  **`Groups/Post/Create.php`**: A modal or form to create a Post within the context of the current group.

### T05: Policies
1.  **`GroupPolicy.php`**:
    -   `view(User $user, Group $group)`: User can view if they are a member or if the group is public.
    -   `update(User $user, Group $group)`: User can update if they are an admin.
    -   `delete(User $user, Group $group)`: User can delete if they are an admin.
    -   `addMember(User $user, Group $group)`: User can add members if they are an admin.
    -   `removeMember(User $user, Group $group, User $member)`: User can remove if they are an admin.
    -   `createPost(User $user, Group $group)`: User can create content if they are a member.

### T06: Jobs & Events
-   **`UserJoinedGroup` event**: Fired when a user successfully joins a group.
-   **`SendGroupWelcomeNotification` listener**: Listens for `UserJoinedGroup` to send a notification.
-   **`GroupJoinRequested` event**: Fired when a user requests to join a private group.
-   **`NotifyAdminOfJoinRequest` listener**: Notifies group admin(s) of the new request.

### T07: Tests (Pest)
-   **Feature Tests**:
    -   Test that a user can create a group.
    -   Test that a user can join a public group.
    -   Test that a user must be approved to join a private group.
    -   Test that a group member can create a post in the group.
    -   Test that a non-member cannot see private group content.
    -   Test that an admin can remove a member.
-   **Unit Tests**:
    -   Test relationships on the `Group` and `User` models.

## 5. Integration Points

-   **E01 Core Infrastructure**: Uses `users` table, `notifications` system.
-   **E03 Activity Management**: `activities` table will be modified to include a `group_id`. The creation flow for activities will need a context-switcher to specify if it's a public or group activity.
-   **E04 Discovery Engine**: The main feed should *exclude* group-specific posts and activities. The group feed will be separate.
-   **E05 Social Interaction (Chat)**: The group chat will be a key component of this feature, likely leveraging the real-time chat infrastructure.

---
**Status**: `Pending`
**Time Estimate**: 25 hours
**Dependencies**: E01, E03, E04 (for exclusion logic)


--- End of content ---
Here is a comprehensive summary of the Groups feature requirements based on the provided documents.

### 1. Feature Purpose and Scope

The **Groups** feature aims to transform FunLynk from a simple activity platform into a vibrant social community. It provides dedicated, semi-private spaces where users can connect and form communities around shared interests, activities, or identities. The core purpose is to foster deeper engagement and connection beyond the platform's public, ephemeral Posts and Activities.

-   **Scope:** The feature includes the ability for users to create, discover, and join groups. Once part of a group, members can create group-specific Posts and Activities, view a dedicated content feed, and communicate in a real-time group chat.

### 2. User Stories & Use Cases

-   **Creation:** As a user, I want to create a group for my local hiking club to organize weekly hikes and share photos in one place.
-   **Discovery:** As a user, I want to search for and discover groups based on my interests (e.g., "Board Games," "Yoga in the Park") to connect with like-minded people.
-   **Joining:** As a user, I want to join a group to see its content, participate in discussions, and be notified of its activities.
-   **Content Scoping:** As a group member, I want to create Posts and Activities that are visible only to my group, providing a more private space for coordination.
-   **Administration:** As a group admin, I want to manage members (approve requests, remove members) to maintain the community's quality and safety.
-   **Communication:** As a group member, I want to participate in a group-specific chat for real-time conversations and quick coordination.

### 3. MVP Scope

**Included in MVP:**

-   **Group Creation:** Users can create public or private groups with a name, description, and banner image.
-   **Group Discovery:** A searchable index of all public groups.
-   **Membership Management:**
    -   Public groups allow users to join freely.
    -   Private groups require admin approval for new members.
    -   Admins can remove members, and members can leave groups.
-   **Group-Scoped Content:** Members can create Posts and Activities that are only visible within the group's dedicated feed.
-   **Basic Roles:** Initial implementation will include two primary roles.
-   **Group Chat:** A single, persistent real-time chat channel for all members of a group.

**Not Included in MVP (Future Enhancements):**

-   Multiple Admins or Moderator roles.
-   Advanced content moderation tools.
-   Group-specific event calendars or analytics.
-   Sub-channels within group chats.
-   Integration with external platforms like Discord or Facebook.

### 4. Role Definitions

-   **Admin:** The creator of the group. This role has full control over the group, including the ability to edit group details, manage member join requests, remove existing members, and delete the group entirely.
-   **Member:** A standard user who has joined a group. Members can view all group content, create new Posts and Activities within the group, and participate in the group chat.

### 5. Key Features

-   **Create:** Any user can create a new group, defining its name, description, and privacy setting (public or private).
-   **Search & Discover:** A central directory allows users to browse and search for public groups to join.
-   **Join & Leave:** Users can instantly join public groups or request to join private ones. Members can leave a group at any time.
-   **Group Posts & Activities:** Members can create content that is exclusively visible on the group's feed, separate from the main public discovery feed.
-   **Group Chat:** Each group has a dedicated, real-time chat room for members to communicate directly.

### 6. Integration Points

-   **E01 Core Infrastructure:** Leverages the existing `users` table for membership and the `notifications` system for join requests and other group-related alerts.
-   **E03 Activity Management:** The `activities` table will be modified to include a `group_id`, allowing activities to be scoped to a specific group.
-   **E04 Discovery Engine:** The main public discovery feed must be updated to *exclude* all group-specific Posts and Activities to maintain their privacy.
-   **E05 Social Interaction (Chat):** The group chat functionality is a core component of this feature and will utilize the real-time infrastructure established in this epic.

### 7. Task Breakdown (T01-T07)

The technical implementation is broken down into the following tasks:

-   **T01: Database & Models:** Create the `groups` and `group_members` tables and corresponding Eloquent models. Modify the `posts` and `activities` tables to link to a group.
-   **T02: Service Classes:** Develop the backend logic (`GroupService`) for managing group creation, joining, leaving, and member administration.
-   **T03: Filament Resources:** Create an admin panel (`GroupResource`) for site administrators to manage all groups from the backend.
-   **T04: Livewire Components:** Build all user-facing UI components for creating, browsing, and viewing groups, including the group feed, chat, and member list.
-   **T05: Policies:** Implement authorization rules (`GroupPolicy`) to control who can view, update, delete, or post within a group.
-   **T06: Jobs & Events:** Set up events and listeners for key actions like a user joining a group or requesting to join a private group, primarily for sending notifications.
-   **T07: Tests:** Write Pest tests to cover all feature and unit test cases, ensuring functionality from group creation to content posting and member management.