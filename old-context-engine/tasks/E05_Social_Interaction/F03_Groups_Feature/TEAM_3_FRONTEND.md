# Team 3: Frontend Components & UI

## 🎯 Your Mission

Build all user-facing Livewire components and Blade views for the Groups feature. You will work in parallel with Team 2 (Backend) once Team 1 completes the database schema.

---

## 📋 Required Reading

**MUST READ FIRST**:
1. `context-engine/tasks/E05_Social_Interaction/F03_Groups_Feature/REQUIREMENTS.md`
2. `context-engine/tasks/E05_Social_Interaction/F03_Groups_Feature/ARCHITECTURE.md`
3. `context-engine/domain-contexts/ui-design-standards.md` (Galaxy theme - CRITICAL!)
4. Team 2's `INTEGRATION_CONTRACTS.md` (API endpoints)
5. Existing component examples:
   - `resources/views/livewire/chat/chat-component.blade.php`
   - `resources/views/livewire/direct-messages/messages-page.blade.php`
   - `resources/views/welcome.blade.php` (galaxy theme reference)

---

## 🎯 Your Responsibilities

### **1. Livewire Components**
Build full-page and nested Livewire components:

#### **Full-Page Components**
- `Groups/GroupsIndex` - Group discovery and search
- `Groups/GroupShow` - Group detail page with tabs
- `Groups/CreateGroup` - Group creation form
- `Groups/GroupSettings` - Group settings (admins only)
- `Dashboard/UserDashboard` - Replace profile hero for authenticated users

#### **Nested Components**
- `Groups/GroupTimeline` - Display posts and events
- `Groups/GroupMembers` - Member list with management
- `Groups/GroupChat` - Reuse existing ChatComponent
- `Groups/GroupCard` - Reusable group card for lists
- `Groups/JoinRequestsList` - Pending requests (admins only)
- `Groups/GroupTagFilter` - Tag filter chips

### **2. Blade Views**
Create Blade templates following galaxy theme:
- All views must use `<x-galaxy-layout>` component
- All content must be in glass cards
- All buttons must use gradient styles
- All forms must have cyan focus glow
- All text must be white/gray for readability

### **3. Routes**
Define web routes for all group pages:
```php
Route::middleware('auth')->group(function () {
    Route::get('/groups', GroupsIndex::class)->name('groups.index');
    Route::get('/groups/create', CreateGroup::class)->name('groups.create');
    Route::get('/groups/{group:slug}', GroupShow::class)->name('groups.show');
    Route::get('/groups/{group:slug}/settings', GroupSettings::class)->name('groups.settings');
    Route::get('/dashboard', UserDashboard::class)->name('dashboard');
});
```

### **4. Real-Time Updates**
Implement Echo listeners for real-time updates:
- Group chat messages
- New posts in group
- New events in group
- New members joining
- Join request notifications (admins)

### **5. Form Handling**
Implement forms with validation and error handling:
- Group creation form
- Group settings form
- Post creation with group selector
- Event creation with group selector
- Join request approval/denial

---

## 📐 Component Specifications

### **GroupsIndex Component**

**File**: `app/Livewire/Groups/GroupsIndex.php`

**Properties**:
```php
public string $search = '';
public array $selectedTags = [];
public Collection $myGroups;
public Collection $searchResults;
public Collection $popularTags;
```

**Methods**:
```php
public function mount(): void
public function updatedSearch(): void
public function toggleTag(string $tagId): void
public function joinGroup(string $groupId): void
```

**View**: `resources/views/livewire/groups/groups-index.blade.php`

**UI Elements**:
- Search bar
- Tag filter chips (multi-select)
- "My Groups" section (grid of group cards)
- "Discover Groups" section (search results)
- "Browse by Interest" section (popular tags)

---

### **GroupShow Component**

**File**: `app/Livewire/Groups/GroupShow.php`

**Properties**:
```php
public Group $group;
public string $activeTab = 'timeline';
public bool $isMember = false;
public bool $isAdmin = false;
```

**Methods**:
```php
public function mount(Group $group): void
public function joinGroup(): void
public function leaveGroup(): void
public function switchTab(string $tab): void
```

**View**: `resources/views/livewire/groups/group-show.blade.php`

**UI Elements**:
- Group header (avatar, name, description, tags, member count)
- Join/Leave button (conditional)
- Settings button (admins only)
- Tabs: Timeline, Members, Chat
- Tab content (nested components)

---

### **CreateGroup Component**

**File**: `app/Livewire/Groups/CreateGroup.php`

**Properties**:
```php
public string $name = '';
public string $description = '';
public ?string $avatarUrl = null;
public array $selectedTags = [];
public string $privacy = 'public';
```

**Methods**:
```php
public function createGroup(): void
public function uploadAvatar(): void
```

**View**: `resources/views/livewire/groups/create-group.blade.php`

**UI Elements**:
- Name input (required)
- Description textarea (optional)
- Avatar upload (optional)
- Tag multi-select (reuse existing tag selector)
- Privacy radio buttons (Public/Private)
- Submit button

---

### **GroupTimeline Component**

**File**: `app/Livewire/Groups/GroupTimeline.php`

**Properties**:
```php
public Group $group;
public Collection $timelineItems;
public int $page = 1;
```

**Methods**:
```php
public function mount(Group $group): void
public function loadMore(): void
public function getListeners(): array // Echo for real-time updates
```

**View**: `resources/views/livewire/groups/group-timeline.blade.php`

**UI Elements**:
- "Create Post" button
- "Create Event" button
- Timeline feed (posts and events mixed, sorted by date)
- Infinite scroll
- Real-time updates when new content is posted

---

### **GroupMembers Component**

**File**: `app/Livewire/Groups/GroupMembers.php`

**Properties**:
```php
public Group $group;
public Collection $members;
public Collection $pendingRequests; // Admins only
public bool $isAdmin = false;
```

**Methods**:
```php
public function mount(Group $group): void
public function removeMember(string $userId): void
public function approveRequest(string $requestId): void
public function denyRequest(string $requestId): void
```

**View**: `resources/views/livewire/groups/group-members.blade.php`

**UI Elements**:
- Member list (avatar, name, role badge)
- Remove button (admins only, not for self)
- Pending requests section (admins only)
- Approve/Deny buttons for requests

---

### **UserDashboard Component**

**File**: `app/Livewire/Dashboard/UserDashboard.php`

**Properties**:
```php
public User $user;
public Collection $myGroups;
public Collection $recentNotifications;
public Collection $interestedPosts;
public Collection $upcomingEvents;
```

**Methods**:
```php
public function mount(): void
```

**View**: `resources/views/livewire/dashboard/user-dashboard.blade.php`

**UI Elements**:
- Dashboard cards (glass morphism):
  - My Groups (grid of group cards)
  - Recent Notifications (list)
  - Interested Posts (posts user reacted to)
  - Upcoming Events (events user RSVP'd to)
- Profile edit button (top right)

---

## 🎨 UI/UX Requirements

### **CRITICAL: Galaxy Theme**

**Every page/component MUST follow the galaxy theme**. No exceptions.

**Step 1: Use the Galaxy Layout Component**
```blade
<x-galaxy-layout>
    <x-slot name="title">Page Title</x-slot>

    <!-- Your content here -->

</x-galaxy-layout>
```

**Step 2: Wrap Content in Glass Cards**
```blade
<div class="container mx-auto px-6 py-8">
    <div class="relative p-8 glass-card max-w-4xl mx-auto">
        <div class="top-accent-center"></div>
        <!-- Your content -->
    </div>
</div>
```

**Step 3: Use Gradient Buttons**
```blade
<!-- Primary -->
<button class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
    Submit
</button>

<!-- Secondary -->
<button class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
    Cancel
</button>
```

**Step 4: Reference Files**
Before creating UI, review:
- `resources/views/welcome.blade.php` - Full page example
- `resources/views/livewire/auth/login.blade.php` - Form example
- `context-engine/domain-contexts/ui-design-standards.md` - Complete guide

**Step 5: Verify Checklist**
- [ ] Galaxy gradient background
- [ ] Aurora layers visible
- [ ] Stars twinkling
- [ ] Content in glass cards
- [ ] Buttons have gradients
- [ ] Forms have cyan focus glow
- [ ] Text is white/gray (readable)
- [ ] Hover effects work

---

## 📦 Deliverables

### **Phase 1: UI Design** (Submit for Review)
Create a document: `FRONTEND_PROPOSAL.md` with:
1. Component structure (all components with properties and methods)
2. Route definitions
3. UI mockups or wireframes (text descriptions are fine)
4. Real-time update strategy (which components listen to which events)
5. Form validation rules
6. Any deviations from ARCHITECTURE.md (with justification)

**Submit this for approval before proceeding to Phase 2**

### **Phase 2: Implementation** (After Approval)
1. Livewire component files (10+ files)
2. Blade view files (10+ files)
3. Route definitions in `routes/web.php`
4. Update navbar to include "Groups" link
5. Update post/event creation forms to include group selector
6. Test all components render correctly
7. Test all forms submit correctly
8. Test real-time updates work

---

## ⚠️ Critical Rules

1. **Galaxy Theme Always** - Every page must follow ui-design-standards.md
2. **No Inline CSS** - Use Tailwind classes and global CSS only
3. **Responsive Design** - Test on mobile, tablet, and desktop
4. **Accessibility** - Use semantic HTML and ARIA labels
5. **Loading States** - Show loading indicators for async operations
6. **Error Handling** - Display user-friendly error messages
7. **Real-Time Updates** - Use Echo listeners for live updates

---

## 🧪 Testing Checklist

Before submitting Phase 2:
- [ ] All components render without errors
- [ ] All forms validate correctly
- [ ] All buttons work as expected
- [ ] Galaxy theme applied to all pages
- [ ] Responsive on mobile, tablet, desktop
- [ ] Real-time updates work (test with 2 browser windows)
- [ ] Loading states display correctly
- [ ] Error messages display correctly
- [ ] Navigation works (all links functional)

---

## 📞 Communication

**Report to**: Architect Agent (me)
**Coordinate with**: Team 2 (Backend) - use their API contracts
**Blockers**: Report immediately if API contracts are insufficient
**Questions**: Ask before making assumptions

**When complete**: Submit `FRONTEND_PROPOSAL.md` for review, then wait for approval before implementing.

---

**Team Lead**: Frontend Engineer Agent
**Dependencies**: Team 1 (Database) must complete first, Team 2 (Backend) for API contracts
**Priority**: P1 (High)
**Estimated Time**: 8-10 hours
**Status**: WAITING FOR TEAM 1 & TEAM 2

