# Frontend Proposal: Groups Feature

This document outlines the frontend implementation plan for the Groups feature, based on the requirements specified in `TEAM_3_FRONTEND.md`.

---

## 1. Component Structure

Below is the planned structure for all Livewire components.

### **Full-Page Components**

- **`Groups/GroupsIndex.php`**
  - **Properties**:
    ```php
    public string $search = '';
    public array $selectedTags = [];
    public Collection $myGroups;
    public Collection $searchResults;
    public Collection $popularTags;
    ```
  - **Methods**:
    ```php
    public function mount(): void
    public function updatedSearch(): void
    public function toggleTag(string $tagId): void
    public function joinGroup(string $groupId): void
    ```

- **`Groups/GroupShow.php`**
  - **Properties**:
    ```php
    public Group $group;
    public string $activeTab = 'timeline';
    public bool $isMember = false;
    public bool $isAdmin = false;
    ```
  - **Methods**:
    ```php
    public function mount(Group $group): void
    public function joinGroup(): void
    public function leaveGroup(): void
    public function switchTab(string $tab): void
    ```

- **`Groups/CreateGroup.php`**
  - **Properties**:
    ```php
    public string $name = '';
    public string $description = '';
    public ?string $avatarUrl = null;
    public array $selectedTags = [];
    public string $privacy = 'public';
    ```
  - **Methods**:
    ```php
    public function createGroup(): void
    public function uploadAvatar(): void
    ```

- **`Groups/GroupSettings.php`**
  - **Properties**:
    ```php
    public Group $group;
    // Form properties for settings
    ```
  - **Methods**:
    ```php
    public function mount(Group $group): void
    public function saveSettings(): void
    ```

- **`Dashboard/UserDashboard.php`**
  - **Properties**:
    ```php
    public User $user;
    public Collection $myGroups;
    public Collection $recentNotifications;
    public Collection $interestedPosts;
    public Collection $upcomingEvents;
    ```
  - **Methods**:
    ```php
    public function mount(): void
    ```

### **Nested Components**

- **`Groups/GroupTimeline.php`**
  - **Properties**:
    ```php
    public Group $group;
    public Collection $timelineItems;
    public int $page = 1;
    ```
  - **Methods**:
    ```php
    public function mount(Group $group): void
    public function loadMore(): void
    public function getListeners(): array
    ```

- **`Groups/GroupMembers.php`**
  - **Properties**:
    ```php
    public Group $group;
    public Collection $members;
    public Collection $pendingRequests;
    public bool $isAdmin = false;
    ```
  - **Methods**:
    ```php
    public function mount(Group $group): void
    public function removeMember(string $userId): void
    public function approveRequest(string $requestId): void
    public function denyRequest(string $requestId): void
    ```

- **`Groups/GroupChat.php`**
  - This will reuse the existing `ChatComponent`.

- **`Groups/GroupCard.php`**
  - **Properties**:
    ```php
    public Group $group;
    ```
  - **Methods**:
    ```php
    public function mount(Group $group): void
    ```

- **`Groups/JoinRequestsList.php`**
  - **Properties**:
    ```php
    public Group $group;
    public Collection $pendingRequests;
    ```
  - **Methods**:
    ```php
    public function mount(Group $group): void
    public function approveRequest(string $requestId): void
    public function denyRequest(string $requestId): void
    ```

- **`Groups/GroupTagFilter.php`**
  - **Properties**:
    ```php
    public Collection $tags;
    public array $selectedTags = [];
    ```
  - **Methods**:
    ```php
    public function toggleTag(string $tagId): void
    ```

---

## 2. Route Definitions

The following routes will be added to `routes/web.php`:

```php
use App\Livewire\Groups\GroupsIndex;
use App\Livewire\Groups\CreateGroup;
use App\Livewire\Groups\GroupShow;
use App\Livewire\Groups\GroupSettings;
use App\Livewire\Dashboard\UserDashboard;

Route::middleware('auth')->group(function () {
    Route::get('/groups', GroupsIndex::class)->name('groups.index');
    Route::get('/groups/create', CreateGroup::class)->name('groups.create');
    Route::get('/groups/{group:slug}', GroupShow::class)->name('groups.show');
    Route::get('/groups/{group:slug}/settings', GroupSettings::class)->name('groups.settings');
    Route::get('/dashboard', UserDashboard::class)->name('dashboard');
});
```

A "Groups" link will be added to the main navigation bar.

---

## 3. UI Mockups (Text Descriptions)

All UI will strictly adhere to the **Galaxy Theme** as defined in `ui-design-standards.md`.

- **Groups Index Page (`/groups`)**:
  - A full-page layout with the galaxy background.
  - A central glass card will contain:
    - A search bar at the top.
    - A section for "My Groups" displaying a horizontal scroll of `GroupCard` components.
    - A section for "Discover Groups" displaying a grid of `GroupCard` components based on search results.
    - A "Browse by Interest" section with tag filter chips.

- **Group Show Page (`/groups/{group:slug}`)**:
  - A full-page layout.
  - A header glass card with the group's avatar, name, description, member count, and tags. It will also contain "Join/Leave" and "Settings" buttons.
  - A tab navigation glass card with tabs for "Timeline", "Members", and "Chat".
  - The content for the active tab will be displayed in a glass card below the navigation.

- **Create Group Page (`/groups/create`)**:
  - A full-page layout with a central glass card containing the creation form.
  - The form will have fields for name, description, avatar upload, tag selection, and privacy settings (public/private).
  - All inputs will have the cyan focus glow.
  - The submit button will be a primary gradient button.

- **User Dashboard (`/dashboard`)**:
  - A full-page layout with a grid of glass cards for:
    - "My Groups"
    - "Recent Notifications"
    - "Interested Posts"
    - "Upcoming Events"

---

## 4. Real-Time Update Strategy

Laravel Echo will be used to listen for broadcasted events.

- **`GroupTimeline` Component**:
  - Will listen on a private channel `groups.{group.id}`.
  - Events: `NewGroupPost`, `NewGroupEvent`.
  - On receiving an event, it will prepend the new item to the `$timelineItems` collection.

- **`GroupMembers` Component**:
  - Will listen on `groups.{group.id}`.
  - Events: `UserJoinedGroup`, `UserLeftGroup`.
  - On receiving an event, it will refresh the member list.

- **`GroupChat` Component**:
  - Will reuse the existing chat component's Echo implementation.

- **Notifications**:
  - A global notification component in the layout will listen on the user's private channel `users.{auth.id}` for `NewJoinRequest` notifications (for admins).

---

## 5. Form Validation Rules

- **Create Group Form**:
  - `name`: `required|string|max:255`
  - `description`: `nullable|string`
  - `avatar`: `nullable|image|max:1024`
  - `tags`: `required|array|min:1`
  - `privacy`: `required|in:public,private`

- **Group Settings Form**:
  - Similar validation rules as the creation form, applied to the settings fields.

---

## 6. Deviations from Architecture

There are no planned deviations from the provided architecture. The plan aligns with the component structure, routing, and UI standards outlined in the brief.

---

This proposal is now ready for review.
