# Frontend Integration Tasks - Sub-Agent Prompts

**Date**: 2025-12-01  
**Phase**: 2d - Frontend Integration  
**Estimated Time**: 4-6 hours with parallel execution

---

## Task 1: Fix GroupTimeline Service Integration

### Sub-Agent Prompt

```
Read the following files for context:
- app/Livewire/Groups/GroupTimeline.php
- app/Services/GroupContentService.php
- app/Models/Group.php
- app/Models/Post.php
- app/Models/Activity.php

Fix the GroupTimeline component to use GroupContentService instead of direct model queries.

**Current Issue**:
The component queries `$this->group->posts()` and `$this->group->activities()` directly in the `loadMore()` method (lines 34-50). This bypasses business logic and is inconsistent with backend architecture.

**Required Changes**:

1. **Inject GroupContentService** in the component:
   ```php
   protected GroupContentService $contentService;
   
   public function boot(GroupContentService $contentService)
   {
       $this->contentService = $contentService;
   }
   ```

2. **Replace loadMore() method** to use service:
   ```php
   public function loadMore(): void
   {
       if (!$this->hasMore) {
           return;
       }
       
       $newItems = $this->contentService->getGroupTimeline($this->group, $this->page, $this->perPage);
       
       $this->items = $this->items->concat($newItems);
       $this->page++;
       $this->hasMore = ($newItems->count() === $this->perPage);
   }
   ```

3. **Keep existing methods** (createPost, createEvent, likeItem, deleteItem, Echo listeners) unchanged

**Output format**: Complete updated GroupTimeline.php file with proper formatting
```

---

## Task 2: Create Post/Event Modal Components

### Sub-Agent Prompt

```
Read the following files for context:
- context-engine/domain-contexts/ui-design-standards.md
- resources/views/livewire/auth/login.blade.php (form example)
- app/Services/GroupContentService.php
- app/Models/Post.php
- app/Models/Activity.php

Create two Livewire modal components for creating group posts and events.

**Component 1: CreateGroupPost**

File: `app/Livewire/Groups/CreateGroupPost.php`

Properties:
```php
public Group $group;
public string $title = '';
public string $description = '';
public array $selectedTags = [];
public ?string $locationName = null;
public $expiresAt = null;
```

Methods:
```php
public function mount(Group $group): void
public function createPost(): void
protected function rules(): array
```

Validation Rules:
- title: required, max:100
- description: required, max:500
- selectedTags: nullable, array
- locationName: nullable, max:255
- expiresAt: required, date, after:now

Use GroupContentService::createGroupPost() to create the post.

View: `resources/views/livewire/groups/create-group-post.blade.php`

UI Requirements:
- Modal overlay with glass card
- Form with all fields
- Tag multi-select
- Date/time picker for expires_at
- Gradient submit button
- Cancel button
- Real-time validation
- Galaxy theme styling

---

**Component 2: CreateGroupEvent**

File: `app/Livewire/Groups/CreateGroupEvent.php`

Properties:
```php
public Group $group;
public string $title = '';
public string $description = '';
public string $locationName = '';
public $startTime = null;
public $endTime = null;
public ?int $maxAttendees = null;
public array $selectedTags = [];
```

Methods:
```php
public function mount(Group $group): void
public function createEvent(): void
protected function rules(): array
```

Validation Rules:
- title: required, max:100
- description: required, max:500
- locationName: required, max:255
- startTime: required, date, after:now
- endTime: required, date, after:startTime
- maxAttendees: nullable, integer, min:1
- selectedTags: nullable, array

Use GroupContentService::createGroupEvent() to create the event.

View: `resources/views/livewire/groups/create-group-event.blade.php`

UI Requirements:
- Modal overlay with glass card
- Form with all fields
- Tag multi-select
- Date/time pickers for start/end
- Max attendees input
- Gradient submit button
- Cancel button
- Real-time validation
- Galaxy theme styling

**Output format**: 4 complete files (2 PHP components + 2 Blade views) with proper formatting
```

---

## Task 3: Add Error Handling & Loading States

### Sub-Agent Prompt

```
Read the following files for context:
- app/Livewire/Groups/GroupsIndex.php
- app/Livewire/Groups/GroupShow.php
- app/Livewire/Groups/GroupMembers.php
- resources/views/livewire/groups/groups-index.blade.php
- resources/views/livewire/groups/group-show.blade.php
- resources/views/livewire/groups/group-members.blade.php

Add comprehensive error handling and loading states to all group components.

**Required Changes**:

### 1. GroupsIndex Component

**PHP Changes** (app/Livewire/Groups/GroupsIndex.php):
- Wrap `joinGroup()` and `leaveGroup()` methods in try-catch blocks
- Add flash messages for success/error
- Example:
  ```php
  public function joinGroup(string $groupId): void
  {
      try {
          $group = Group::findOrFail($groupId);
          $this->groupService->addMember($group, Auth::user());
          session()->flash('success', 'Successfully joined the group!');
      } catch (\Exception $e) {
          session()->flash('error', 'Failed to join group: ' . $e->getMessage());
      }
      $this->loadUserGroups();
  }
  ```

**View Changes** (resources/views/livewire/groups/groups-index.blade.php):
- Add flash message display at top of component
- Add wire:loading spinners to join/leave buttons
- Example:
  ```blade
  @if (session()->has('success'))
      <div class="bg-green-500/20 text-green-300 p-4 rounded-xl mb-4">
          {{ session('success') }}
      </div>
  @endif
  
  <button wire:click="joinGroup('{{ $group->id }}')" wire:loading.attr="disabled" class="...">
      <span wire:loading.remove wire:target="joinGroup('{{ $group->id }}')">Join</span>
      <span wire:loading wire:target="joinGroup('{{ $group->id }}')">Joining...</span>
  </button>
  ```

### 2. GroupShow Component

**PHP Changes** (app/Livewire/Groups/GroupShow.php):
- Add try-catch to joinGroup(), leaveGroup(), deleteGroup()
- Add flash messages

**View Changes** (resources/views/livewire/groups/group-show.blade.php):
- Add flash message display
- Add wire:loading to all action buttons
- Add confirmation for delete action

### 3. GroupMembers Component

**PHP Changes** (app/Livewire/Groups/GroupMembers.php):
- Add try-catch to removeMember(), changeRole()
- Add flash messages

**View Changes** (resources/views/livewire/groups/group-members.blade.php):
- Add flash message display (already exists, verify)
- Add wire:loading to action buttons
- Add confirmation for remove member action

**Output format**: 6 complete updated files (3 PHP + 3 Blade) with proper formatting
```

---

## Execution Plan

1. **Spawn 3 sub-agents in parallel**:
   ```bash
   JOB1=$(python3 spawn_sub_agent.py gemini "Task 1 prompt...")
   JOB2=$(python3 spawn_sub_agent.py gemini "Task 2 prompt...")
   JOB3=$(python3 spawn_sub_agent.py gemini "Task 3 prompt...")
   
   echo "Jobs: $JOB1, $JOB2, $JOB3"
   ```

2. **Wait 60-90 seconds** for completion

3. **Review outputs**:
   ```bash
   cat subagent_runs/$JOB1/report.md
   cat subagent_runs/$JOB2/report.md
   cat subagent_runs/$JOB3/report.md
   ```

4. **Integrate code** into codebase

5. **Test components** manually

6. **Fix any issues** and iterate

---

## Success Criteria

✅ GroupTimeline uses GroupContentService  
✅ Post/event creation modals work  
✅ All errors display user-friendly messages  
✅ Loading states show during operations  
✅ No silent failures  
✅ All components tested and working

