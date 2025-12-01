# Groups Feature - Technical Implementation Details

## Backend Architecture

### Service Layer (Production Ready)

**GroupService** (`app/Services/GroupService.php`)
- `createGroup(User $user, array $data): Group` - Creates group with creator as admin
- `updateGroup(Group $group, array $data): Group` - Updates group details
- `deleteGroup(Group $group): bool` - Soft deletes group
- `addMember(Group $group, User $user, string $role): GroupMember` - Adds member
- `removeMember(Group $group, User $user): bool` - Removes member
- `approveMember(GroupMember $member): GroupMember` - Approves pending member
- `rejectMember(GroupMember $member): bool` - Rejects pending member

**GroupContentService** (`app/Services/GroupContentService.php`)
- `createPost(User $user, Group $group, array $data): Post` - Creates group post
- `createActivity(User $user, Group $group, array $data): Activity` - Creates group activity
- `getGroupFeed(Group $group, int $limit): Collection` - Fetches group feed

**GroupChatService** (`app/Services/GroupChatService.php`)
- `getOrCreateConversation(Group $group): Conversation` - Gets/creates group chat
- `sendMessage(User $user, Group $group, string $message): Message` - Sends message

### Authorization Layer (Production Ready)

**GroupPolicy** (`app/Policies/GroupPolicy.php`)
- `viewAny(User $user)` - Can view group directory
- `view(User $user, Group $group)` - Can view if public or member
- `create(User $user)` - Any authenticated user
- `update(User $user, Group $group)` - Creator/admin only
- `delete(User $user, Group $group)` - Creator/admin only
- `manageMembers(User $user, Group $group)` - Creator/admin only
- `createPost(User $user, Group $group)` - Members only

### Event System (Production Ready)

**Events Dispatched**:
- `GroupCreated` - When group created
- `GroupMemberJoined` - When user joins
- `GroupMemberRemoved` - When user removed
- `GroupPostCreated` - When post created in group
- `GroupEventCreated` - When activity created in group
- `GroupJoinRequestReceived` - When join request submitted
- `GroupJoinRequestApproved` - When request approved

**Listeners**:
- `SendGroupNotification` - Sends notifications
- `BroadcastGroupUpdate` - Broadcasts via Laravel Echo
- `UpdateGroupMemberCount` - Updates member count

### Database Schema

**groups table**
- `id` (ULID), `name`, `slug`, `description`, `avatar_url`, `cover_image_url`
- `privacy` (enum: public/private), `auto_approve_members` (boolean)
- `created_by` (FK to users), `timestamps`, `soft_deletes`

**group_members table**
- `id` (ULID), `group_id` (FK), `user_id` (FK)
- `role` (enum: admin/member), `status` (enum: pending/approved/banned)
- `timestamps`

**group_join_requests table**
- `id` (ULID), `group_id` (FK), `user_id` (FK)
- `status` (enum: pending/approved/rejected), `timestamps`

**group_tag table** (pivot)
- `group_id` (FK), `tag_id` (FK)

---

## Frontend Architecture

### Component Structure

**GroupsIndex** (`app/Livewire/Groups/GroupsIndex.php`)
- Properties: `$search`, `$selectedTags`, `$myGroups`, `$searchResults`, `$popularTags`
- Methods: `updatedSearch()`, `toggleTag()`
- **TODO**: Implement search, tag filtering, data fetching

**GroupShow** (`app/Livewire/Groups/GroupShow.php`)
- Properties: `$group`, `$currentUserRole`, `$isMember`
- Methods: `joinGroup()`, `leaveGroup()`, `approveRequest()`
- **TODO**: Implement all methods, fetch group data

**CreateGroup** (`app/Livewire/Groups/CreateGroup.php`)
- Properties: `$name`, `$description`, `$privacy`, `$avatar`, `$tags`
- Methods: `submit()`, `uploadAvatar()`
- **TODO**: Implement form submission, validation

**GroupTimeline** (`app/Livewire/Groups/GroupTimeline.php`)
- Properties: `$group`, `$posts`, `$activities`
- Methods: `loadMore()`, `handlePostCreated()`, `handleActivityCreated()`
- Echo listeners: `group.{group.id}` channel
- **TODO**: Implement infinite scroll, real-time updates

**GroupMembers** (`app/Livewire/Groups/GroupMembers.php`)
- Properties: `$group`, `$members`, `$joinRequests`
- Methods: `approveMember()`, `removeMember()`, `rejectRequest()`
- Echo listeners: `group.{group.id}` channel
- **TODO**: Implement member management, real-time updates

### Routes

```
GET  /groups                    → GroupsIndex
GET  /groups/create             → CreateGroup
POST /groups                    → GroupService@createGroup (via form)
GET  /groups/{group:slug}       → GroupShow
GET  /groups/{group:slug}/edit  → GroupSettings
POST /groups/{group:slug}       → GroupService@updateGroup (via form)
GET  /dashboard                 → UserDashboard
```

### UI/UX Standards

- **Theme**: Galaxy theme with glass morphism
- **Cards**: All content in `.glass-card` containers
- **Buttons**: Gradient buttons for primary actions (pink-500 to purple-500)
- **Forms**: Glass inputs with cyan focus glow
- **Layout**: Responsive grid for group cards, tabbed interface for group details

---

## Current Blockers

### 🔴 CRITICAL: Test Failures
**File**: `tests/Feature/Feature/ActivityPolicyTest.php`
**Issue**: "Null email" errors in User factory calls
**Impact**: Backend tests won't pass; feature marked incomplete
**Fix**: Ensure unique emails in all `User::factory()->create()` calls

### ⚠️ FRONTEND INTEGRATION NEEDED
1. **Data Fetching**: Components need to call services to fetch real data
2. **Form Submission**: All forms need submit handlers
3. **Real-time Updates**: Echo listeners need to update component state
4. **Error Handling**: Need try-catch blocks and user feedback
5. **Validation**: Need form validation and error messages

---

## Testing Status

### Backend Tests (Mostly Passing)
- ✅ `GroupServiceTest.php` - Service logic
- ✅ `GroupContentServiceTest.php` - Content service
- ✅ `GroupChatServiceTest.php` - Chat service
- ✅ `GroupPolicyTest.php` - Authorization
- ⚠️ `ActivityPolicyTest.php` - **FAILING** (null email errors)

### Frontend Tests (Not Yet Written)
- ❌ Component tests for all Livewire components
- ❌ Form validation tests
- ❌ Real-time update tests

---

## Next Steps for Integration

### Immediate (Fix Backend)
```bash
# Fix null email errors in ActivityPolicyTest.php
# Run tests
php artisan test

# Verify migrations
php artisan migrate:fresh --seed
```

### Short Term (Frontend Integration)
1. Implement `GroupsIndex` data fetching and search
2. Implement `GroupShow` with join/leave logic
3. Implement `CreateGroup` form submission
4. Implement `GroupTimeline` with infinite scroll
5. Implement `GroupMembers` with member management

### Medium Term (Feature Completion)
1. Add error handling and validation
2. Add loading states and spinners
3. Implement real-time updates via Echo
4. Add responsive design verification
5. Write Pest tests for components

---

## Key Files Reference

**Backend**:
- Models: `app/Models/Group.php`, `GroupMember.php`, `GroupJoinRequest.php`
- Services: `app/Services/GroupService.php`, `GroupContentService.php`, `GroupChatService.php`
- Policies: `app/Policies/GroupPolicy.php`
- Events: `app/Events/Group*.php` (7 files)
- Listeners: `app/Listeners/SendGroupNotification.php`, etc.
- Tests: `tests/Feature/Feature/Group*.php` (4 files)

**Frontend**:
- Components: `app/Livewire/Groups/*.php` (10 files)
- Views: `resources/views/livewire/groups/*.blade.php` (10 files)
- Routes: `routes/web.php`
- Navigation: `resources/views/components/navbar.blade.php`

**Database**:
- Migrations: `database/migrations/2025_*_create_groups_table.php` (7 files)
- Factories: `database/factories/Group*.php` (3 files)
- Seeders: `database/seeders/GroupSeeder.php`

