--- /Users/ianbruce/Herd/funlynk/context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md ---

# Task: F03 - Group Management & Timeline

## Epic: E05 - Social Interaction

## Feature: Group Management & Timeline

### Overview
This feature enables users to create, manage, and interact within groups. It includes group creation, membership management (join requests, approvals), and a dedicated group timeline for posts and events.

### Business Logic
- **Group Creation**: Users can create public or private groups. Private groups require admin approval for new members.
- **Membership**:
    - Public groups: Users can join directly.
    - Private groups: Users send join requests, which group admins can approve/reject.
    - Roles: Creator (owner), Admin, Member.
- **Group Timeline**: Displays a chronological feed of posts and events specific to the group.
- **Post/Event Creation**: Group members can create posts and events within the group.
- **Moderation**: Group admins can delete posts/events and manage members.

### Technical Details
- **Models**: `Group`, `GroupMember`, `GroupJoinRequest`, `Post`, `Activity`.
- **Relationships**:
    - `Group` has many `GroupMember`s, `GroupJoinRequest`s, `Post`s, `Activity`s.
    - `User` belongs to many `Group`s through `GroupMember`.
    - `Post` and `Activity` belong to `Group`.
- **Livewire Components**:
    - `Groups/GroupCreateForm.php`
    - `Groups/GroupShow.php` (main group page)
    - `Groups/GroupTimeline.php` (for displaying posts/events)
    - `Groups/GroupMembers.php`
    - `Groups/GroupJoinRequests.php`
- **Filament Resources**: `GroupResource` (for admin management)
- **Policies**: `GroupPolicy`, `PostPolicy`, `ActivityPolicy` (for group context)
- **Database**: New tables: `groups`, `group_members`, `group_join_requests`. Add `group_id` to `posts` and `activities` tables.

### UI/UX Considerations
- **Galaxy Theme**: All group-related UI must adhere to the existing galaxy theme and glass morphism standards.
- **Responsive**: Ensure layouts are responsive for mobile and desktop.
- **Infinite Scroll**: The group timeline should implement infinite scrolling for performance.
- **Member/Admin Actions**: Clearly distinguish actions available only to members or admins (e.g., create post, approve members).

### Tasks (Implementation Steps)

**T01: Database Migrations & Model Updates**
- Create `groups` table: `id`, `name`, `slug`, `description`, `avatar_url`, `cover_image_url`, `privacy` (public/private), `auto_approve_members`, `created_by` (foreign key to users), `deleted_at`, timestamps.
- Create `group_members` pivot table: `group_id`, `user_id`, `role` (member/admin), `status` (active/pending), timestamps.
- Create `group_join_requests` table: `id`, `group_id`, `user_id`, `status` (pending/approved/rejected), timestamps.
- Add `group_id` (nullable) to `posts` table.
- Add `group_id` (nullable) to `activities` table.
- Update `Group`, `GroupMember`, `GroupJoinRequest`, `Post`, `Activity` models with relationships and casts.

**T02: Group Service & Business Logic**
- Create `app/Services/GroupService.php` to handle:
    - `createGroup(array $data, User $creator)`
    - `joinGroup(Group $group, User $user)` (handles auto-approval vs. join request)
    - `approveJoinRequest(GroupJoinRequest $request)`
    - `rejectJoinRequest(GroupJoinRequest $request)`
    - `addMember(Group $group, User $user, string $role = 'member')`
    - `removeMember(Group $group, User $user)`
    - `updateMemberRole(GroupMember $member, string $newRole)`
    - `getGroupTimeline(Group $group, int $perPage = 10, int $page = 1)` (combines posts and activities)

**T03: Filament Resources**
- Create `GroupResource` for admin CRUD operations on groups.
- Implement `GroupMemberRelationManager` for managing members within `GroupResource`.
- Implement `GroupJoinRequestRelationManager` for managing join requests.

**T04: Livewire Components (User-Facing)**
- `app/Livewire/Groups/GroupCreateForm.php` & `resources/views/livewire/groups/group-create-form.blade.php`:
    - Form for creating a new group.
    - Validation, save to DB via `GroupService`.
- `app/Livewire/Groups/GroupShow.php` & `resources/views/livewire/groups/group-show.blade.php`:
    - Displays group details (name, description, members, cover image).
    - Buttons for "Join Group" / "Leave Group" / "Manage Members" (conditional).
    - Embeds `GroupTimeline` component.
- `app/Livewire/Groups/GroupTimeline.php` & `resources/views/livewire/groups/group-timeline.blade.php`:
    - **This is the current task.**
    - Displays a chronological feed of `Post`s and `Activity`s belonging to the group.
    - Implements infinite scroll (load 10 items at a time).
    - Includes "Create Post" and "Create Event" buttons (visible to members only).
    - Each item (post/event) should show reactions/RSVPs, comment count, and actions (like/react, delete - conditional).
- `app/Livewire/Groups/GroupMembers.php` & `resources/views/livewire/groups/group-members.blade.php`:
    - Lists group members.
    - Admin actions: change role, remove member.
- `app/Livewire/Groups/GroupJoinRequests.php` & `resources/views/livewire/groups/group-join-requests.blade.php`:
    - Lists pending join requests for private groups.
    - Admin actions: approve, reject.

**T05: Policies**
- `GroupPolicy`: Define authorization for `viewAny`, `view`, `create`, `update`, `delete`, `join`, `leave`, `manageMembers`, `manageJoinRequests` on `Group` model.
- Update `PostPolicy` and `ActivityPolicy` to include group-specific authorization (e.g., only group members can create posts in a group).

**T06: Jobs & Events (Optional for F03)**
- Consider jobs for notifying admins of new join requests, or members of new posts/events.

**T07: Tests**
- Write Pest tests for all new functionality:
    - Group creation and management.
    - Membership logic (join, leave, roles, requests).
    - Group timeline display and infinite scroll.
    - Authorization policies.

### Time Estimates
- T01: 4 hours
- T02: 6 hours
- T03: 8 hours
- T04: 12 hours (GroupTimeline: 4 hours)
- T05: 4 hours
- T06: 2 hours
- T07: 8 hours

**Total Estimated Time**: 44 hours

---
**Current Focus**: Implementing `app/Livewire/Groups/GroupTimeline.php` and `resources/views/livewire/groups/group-timeline.blade.php` as part of T04.The context files have been read. I have a good understanding of the UI design standards, the requirements for the `GroupTimeline` component, and the `Group`, `Post`, and `Activity` models.

Now, I will proceed with creating the Livewire component and its view.

First, I'll create the Livewire component PHP file.

I have created the Livewire v3 component for displaying group timeline (posts and events) with infinite scroll and applied the specified UI standards.

The PHP component is located at `/Users/ianbruce/Herd/funlynk/app/Livewire/Groups/GroupTimeline.php` and the Blade view at `/Users/ianbruce/Herd/funlynk/resources/views/livewire/groups/group-timeline.blade.php`.