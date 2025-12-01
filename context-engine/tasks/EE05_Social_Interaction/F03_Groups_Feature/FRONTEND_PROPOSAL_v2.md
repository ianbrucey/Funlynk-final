# Frontend Proposal: Groups Feature (v2)

This document outlines the revised frontend implementation plan for the Groups feature, incorporating feedback from the proposal review.

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
    use Livewire\WithFileUploads;

    public string $name = '';
    public string $description = '';
    public $avatar; // File upload
    public array $selectedTags = [];
    public string $privacy = 'public';
    ```
  - **Methods**:
    ```php
    public function createGroup(): void
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
use App\Models\User;

Route::middleware('auth')->group(function () {
    Route::get('/groups', GroupsIndex::class)->name('groups.index');
    Route::get('/groups/create', CreateGroup::class)->name('groups.create');
    Route::get('/groups/{group:slug}', GroupShow::class)->name('groups.show');
    Route::get('/groups/{group:slug}/settings', GroupSettings::class)->name('groups.settings');
    Route::get('/dashboard', UserDashboard::class)->name('dashboard');
});

Route::get('/profile/{user:username}', function (User $user) {
    // If viewing own profile, redirect to dashboard
    if (auth()->check() && auth()->id() === $user->id) {
        return redirect()->route('dashboard');
    }
    
    // Otherwise show public profile
    return app(\App\Livewire\Profile\ProfileShow::class);
})->name('profile.show');
```

A "Groups" link will be added to the main navigation bar.

---

## 3. UI Mockups (Text Descriptions)

All UI will strictly adhere to the **Galaxy Theme** as defined in `ui-design-standards.md`. The mockups remain the same as in the original proposal.

---

## 4. Real-Time Update Strategy

Laravel Echo will be used to listen for broadcasted events on the correct channels with the correct event names.

- **`GroupTimeline` Component**:
  - Will listen on a private channel `group.{group.id}`.
  - Events: `GroupPostCreated`, `GroupEventCreated`.
  - On receiving an event, it will prepend the new item to the `$timelineItems` collection.
    ```php
    public function getListeners(): array
    {
        return [
            "echo-private:group.{$this->group->id},GroupPostCreated" => 'onPostCreated',
            "echo-private:group.{$this->group->id},GroupEventCreated" => 'onEventCreated',
        ];
    }
    ```

- **`GroupMembers` Component**:
  - Will listen on `group.{group.id}`.
  - Events: `GroupMemberJoined`, `GroupMemberRemoved`.
  - On receiving an event, it will refresh the member list.

- **`GroupChat` Component**:
  - Will reuse the existing chat component's Echo implementation.

- **Notifications**:
  - A global notification component in the layout will listen on the user's private channel `user.{auth.id}` for `GroupJoinRequestReceived` notifications (for admins).

---

## 5. Form Validation and Avatar Upload

- **Create Group Form**:
  - `name`: `required|string|max:100|unique:groups,name`
  - `description`: `nullable|string|max:1000`
  - `avatar`: `nullable|image|max:1024`
  - `tags`: `nullable|array`
  - `tags.*`: `exists:tags,id`
  - `privacy`: `required|in:public,private`

- **Avatar Upload Implementation**:
  The `CreateGroup` component will use Livewire's `WithFileUploads` trait to handle the avatar upload.

  **Component Logic**:
  ```php
  use Livewire\WithFileUploads;

  class CreateGroup extends Component
  {
      use WithFileUploads;
      
      public string $name = '';
      public string $description = '';
      public $avatar; // File upload
      public array $selectedTags = [];
      public string $privacy = 'public';
      
      public function createGroup(): void
      {
          $this->validate([
              'name' => 'required|string|max:100|unique:groups,name',
              'description' => 'nullable|string|max:1000',
              'avatar' => 'nullable|image|max:1024', // 1MB max
              'tags' => 'nullable|array',
              'tags.*' => 'exists:tags,id',
              'privacy' => 'required|in:public,private',
          ]);
          
          $avatarUrl = null;
          if ($this->avatar) {
              $avatarUrl = $this->avatar->store('group-avatars', 'public');
          }
          
          // ... call to backend service to create group
      }
  }
  ```

  **Blade View**:
  ```blade
  <input type="file" wire:model="avatar" accept="image/*">
  @error('avatar') <span class="error">{{ $message }}</span> @enderror

  @if ($avatar)
      <img src="{{ $avatar->temporaryUrl() }}" class="preview">
  @endif
  ```

---

## 6. Deviations from Architecture

There are no planned deviations from the provided architecture.

---

This revised proposal is now ready for review.
