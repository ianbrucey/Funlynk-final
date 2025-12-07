# Backend Proposal v2: Groups Feature

## 1. Service Class Signatures

### GroupService (`app/Services/GroupService.php`)

```php
public function createGroup(User $user, array $data): Group
public function updateGroup(Group $group, array $data): Group
public function deleteGroup(Group $group): bool
public function addMember(Group $group, User $user, string $role = 'member'): GroupMember
public function removeMember(Group $group, User $user): bool
public function updateMemberRole(Group $group, User $user, string $role): GroupMember
public function searchPublicGroups(string $query, ?array $tagIds = null): Collection
public function getGroupsByTag(Tag $tag): Collection
public function getUserGroups(User $user): Collection
public function getGroupMembers(Group $group): Collection
public function syncGroupTags(Group $group, array $tagIds): void
public function createJoinRequest(Group $group, User $user): GroupJoinRequest
public function approveJoinRequest(GroupJoinRequest $request, User $admin): bool
public function denyJoinRequest(GroupJoinRequest $request, User $admin): bool
public function isGroupMember(Group $group, User $user): bool
public function isGroupAdmin(Group $group, User $user): bool
```

### GroupContentService (`app/Services/GroupContentService.php`)

```php
public function createGroupPost(Group $group, User $user, array $data): Post
public function createGroupEvent(Group $group, User $user, array $data): Activity
public function getGroupTimeline(Group $group, int $page = 1, int $perPage = 20): Collection
public function getGroupPosts(Group $group): Collection
public function getGroupEvents(Group $group): Collection
```

### GroupChatService (`app/Services/GroupChatService.php`)

```php
public function getOrCreateGroupChat(Group $group): Conversation
public function sendGroupMessage(Group $group, User $user, string $message): Message
public function getGroupChatMessages(Group $group, int $limit = 50): Collection
public function addParticipantToGroupChat(Group $group, User $user): void
public function removeParticipantFromGroupChat(Group $group, User $user): void
```

## 2. Policy Rules

### GroupPolicy (`app/Policies/GroupPolicy.php`)

-   `viewAny(User $user)`: Anyone can search for groups.
-   `view(?User $user, Group $group)`: Members can view private groups; anyone can view public groups.
-   `create(User $user)`: Authenticated users can create groups.
-   `update(User $user, Group $group)`: Only group administrators can update a group.
-   `delete(User $user, Group $group)`: Only group administrators can delete a group.
-   `join(User $user, Group $group)`: Non-members can join a group.
-   `leave(User $user, Group $group)`: Members can leave a group (except the last administrator).
-   `invite(User $user, Group $group)`: Group members can invite others.
-   `removeMember(User $user, Group $group)`: Only group administrators can remove members.
-   `approveRequest(User $user, Group $group)`: Only group administrators can approve join requests.

### GroupPostPolicy (extends PostPolicy)

-   `create(User $user, Group $group)`: Only group members can create posts in a group.
-   `view(User $user, Post $post, Group $group)`: Only group members can view posts within a group.
-   `update(User $user, Post $post, Group $group)`: Only the post creator or group administrators can update a group post.
-   `delete(User $user, Post $post, Group $group)`: Only the post creator or group administrators can delete a group post.

### GroupActivityPolicy (extends ActivityPolicy)

-   `create(User $user, Group $group)`: Only group members can create events in a group.
-   `view(User $user, Activity $activity, Group $group)`: Only group members can view events within a group.
-   `update(User $user, Activity $activity, Group $group)`: Only the event creator or group administrators can update a group event.
-   `delete(User $user, Activity $activity, Group $group)`: Only the event creator or group administrators can delete a group event.

## 3. Event/Listener Mapping

### Events

-   `GroupCreated`
-   `GroupMemberJoined`
-   `GroupMemberRemoved`
-   `GroupPostCreated`
-   `GroupEventCreated`
-   `GroupJoinRequestReceived`
-   `GroupJoinRequestApproved`

### Listeners

-   `SendGroupNotification`: Generic listener for all group events to send notifications.
-   `BroadcastGroupUpdate`: Listener for Reverb broadcasting of group updates.
-   `UpdateGroupMemberCount`: Listener to increment/decrement group member count.

## 4. Routing Strategy

### Web Routes (`routes/web.php`)
For Livewire page loads.
```php
Route::middleware('auth')->group(function () {
    Route::get('/groups', GroupsIndex::class)->name('groups.index');
    Route::get('/groups/create', CreateGroup::class)->name('groups.create');
    Route::get('/groups/{group:slug}', GroupShow::class)->name('groups.show');
    Route::get('/groups/{group:slug}/settings', GroupSettings::class)->name('groups.settings');
    Route::get('/dashboard', UserDashboard::class)->name('dashboard');
});
```

### API Routes (`routes/api.php`)
For AJAX operations from Livewire components.
```php
Route::middleware('auth')->prefix('api')->group(function () {
    Route::post('/groups', [GroupController::class, 'store']);
    Route::put('/groups/{group}', [GroupController::class, 'update']);
    Route::delete('/groups/{group}', [GroupController::class, 'destroy']);
    Route::post('/groups/{group}/join', [GroupController::class, 'join']);
    Route::post('/groups/{group}/leave', [GroupController::class, 'leave']);
    Route::get('/groups/search', [GroupController::class, 'search']);
    Route::get('/groups/{group}/members', [GroupController::class, 'members']);
    Route::post('/groups/{group}/members/{user}/remove', [GroupController::class, 'removeMember']);
    Route::post('/groups/{group}/join-requests/{request}/approve', [GroupController::class, 'approveRequest']);
    Route::post('/groups/{group}/join-requests/{request}/deny', [GroupController::class, 'denyRequest']);
    Route::post('/groups/{group}/posts', [GroupPostController::class, 'store']);
    Route::post('/groups/{group}/events', [GroupEventController::class, 'store']);
    Route::get('/groups/{group}/timeline', [GroupController::class, 'timeline']);
    Route::get('/groups/{group}/chat', [GroupChatController::class, 'show']);
    Route::post('/groups/{group}/chat/messages', [GroupChatController::class, 'sendMessage']);
    Route::get('/groups/{group}/chat/messages', [GroupChatController::class, 'messages']);
});
```

## 5. Validation Rules for Form Requests

### CreateGroupRequest
```php
public function rules(): array
{
    return [
        'name' => 'required|string|max:100|unique:groups,name',
        'description' => 'nullable|string|max:1000',
        'avatar_url' => 'nullable|url|max:255',
        'cover_image_url' => 'nullable|url|max:255',
        'privacy' => 'required|in:public,private',
        'tags' => 'nullable|array',
        'tags.*' => 'exists:tags,id',
    ];
}
```

### UpdateGroupRequest
```php
public function rules(): array
{
    return [
        'name' => 'sometimes|string|max:100|unique:groups,name,' . $this->group->id,
        'description' => 'nullable|string|max:1000',
        'avatar_url' => 'nullable|url|max:255',
        'cover_image_url' => 'nullable|url|max:255',
        'privacy' => 'sometimes|in:public,private',
        'tags' => 'nullable|array',
        'tags.*' => 'exists:tags,id',
    ];
}
```

### CreateGroupPostRequest
```php
public function rules(): array
{
    return [
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'location_name' => 'nullable|string|max:255',
        'location_coordinates' => 'nullable', // PostGIS point
        'expires_at' => 'nullable|date|after:now',
        'tags' => 'nullable|array',
        'tags.*' => 'exists:tags,id',
    ];
}
```

### CreateGroupEventRequest
```php
public function rules(): array
{
    return [
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'location_name' => 'required|string|max:255',
        'location_coordinates' => 'required', // PostGIS point
        'start_time' => 'required|date|after:now',
        'end_time' => 'required|date|after:start_time',
        'max_attendees' => 'nullable|integer|min:1',
        'tags' => 'nullable|array',
        'tags.*' => 'exists:tags,id',
    ];
}
```

## 6. Deviations from ARCHITECTURE.md

No deviations. This proposal aligns with the architecture.
