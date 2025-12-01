# Agent 2 Assignment: Groups Feature Frontend (E05/F03)

## Overview

You are responsible for building the frontend for the Groups feature. Agent 1 is handling backend tests and integration. Your work will begin after Agent 1 completes Phase 2b (backend policy tests).

**Estimated Duration**: 14-18 hours
**Parallel Work**: Use 5 sub-agents to build components simultaneously

---

## Your Responsibilities

### Phase 2c: Frontend Components (6-8 hours)
Build 5 Livewire v3 components using sub-agents in parallel

### Phase 2d: Frontend Integration (2-3 hours)
Integrate components into the application

### Phase 4: Polish & Accessibility (3-4 hours)
Make frontend production-ready

---

## Sub-Agent Job Assignments

Spawn these 5 sub-agents in parallel. Each prompt is self-contained and ready to use.

### Job 1: GroupsIndex Component

```
Read the following files for context:
- context-engine/domain-contexts/ui-design-standards.md
- context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md
- resources/views/welcome.blade.php
- app/Models/Group.php
- app/Services/GroupService.php

Create a Livewire v3 component for displaying all groups with search and filtering.

Requirements:
- Component: app/Livewire/Groups/GroupsIndex.php
- View: resources/views/livewire/groups/groups-index.blade.php
- Features:
  * Display paginated list of groups (12 per page)
  * Search by group name
  * Filter by privacy (public/private)
  * Filter by tags
  * Join/leave buttons for each group
  * Show member count and description
- UI:
  * Use galaxy theme with glass morphism
  * Glass cards for each group
  * Gradient buttons (pink-500 to purple-500)
  * Cyan focus glow on search input
  * DaisyUI components
- Properties: $groups, $search, $privacyFilter, $selectedTags
- Methods: search(), filterByPrivacy(), filterByTags(), joinGroup(), leaveGroup()

Output: Complete PHP component and Blade view with proper formatting
```

### Job 2: GroupShow Component

```
Read the following files for context:
- context-engine/domain-contexts/ui-design-standards.md
- context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md
- resources/views/welcome.blade.php
- app/Models/Group.php
- app/Services/GroupService.php

Create a Livewire v3 component for displaying a single group's details.

Requirements:
- Component: app/Livewire/Groups/GroupShow.php
- View: resources/views/livewire/groups/group-show.blade.php
- Features:
  * Display group name, description, privacy, tags
  * Show member count and admin list
  * Join/leave button (conditional)
  * Edit button (admin only)
  * Delete button (admin only)
  * Show group timeline preview (3 latest posts/events)
  * Show member list preview (6 members)
- UI:
  * Use galaxy theme with glass morphism
  * Glass card for group info
  * Gradient buttons
  * Responsive layout
- Properties: $groupId, $group, $isAdmin, $isMember
- Methods: joinGroup(), leaveGroup(), editGroup(), deleteGroup()

Output: Complete PHP component and Blade view
```

### Job 3: CreateGroup Component

```
Read the following files for context:
- context-engine/domain-contexts/ui-design-standards.md
- context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md
- resources/views/livewire/auth/login.blade.php (form example)
- app/Models/Group.php
- app/Services/GroupService.php

Create a Livewire v3 component for creating a new group.

Requirements:
- Component: app/Livewire/Groups/CreateGroup.php
- View: resources/views/livewire/groups/create-group.blade.php
- Form Fields:
  * Name (required, max 100)
  * Description (required, max 500)
  * Privacy (public/private, required)
  * Tags (multi-select, optional)
  * Location (with geocoding, optional)
- UI:
  * Use galaxy theme with glass morphism
  * Glass card for form
  * Gradient submit button
  * Cyan focus glow on inputs
  * DaisyUI form components
  * Real-time validation
- Properties: $name, $description, $privacy, $selectedTags, $location
- Methods: createGroup(), validateForm(), addTag(), removeTag()
- Validation: Use Laravel validator with custom messages

Output: Complete PHP component and Blade view with validation
```

### Job 4: GroupTimeline Component

```
Read the following files for context:
- context-engine/domain-contexts/ui-design-standards.md
- context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md
- resources/views/welcome.blade.php
- app/Models/Group.php
- app/Models/Post.php
- app/Models/Activity.php

Create a Livewire v3 component for displaying group timeline (posts and events).

Requirements:
- Component: app/Livewire/Groups/GroupTimeline.php
- View: resources/views/livewire/groups/group-timeline.blade.php
- Features:
  * Display group posts and events in chronological order
  * Infinite scroll (load 10 items at a time)
  * Create post button (members only)
  * Create event button (members only)
  * Show reactions and comment count
  * Like/react to posts
  * Delete post/event (creator/admin only)
- UI:
  * Use galaxy theme with glass morphism
  * Glass cards for each post/event
  * Gradient buttons
  * Responsive layout
- Properties: $groupId, $items, $page, $hasMore
- Methods: loadMore(), createPost(), createEvent(), likeItem(), deleteItem()

Output: Complete PHP component and Blade view with infinite scroll
```

### Job 5: GroupMembers Component

```
Read the following files for context:
- context-engine/domain-contexts/ui-design-standards.md
- context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md
- resources/views/welcome.blade.php
- app/Models/Group.php
- app/Models/GroupMember.php
- app/Services/GroupService.php

Create a Livewire v3 component for managing group members.

Requirements:
- Component: app/Livewire/Groups/GroupMembers.php
- View: resources/views/livewire/groups/group-members.blade.php
- Features:
  * Display list of group members with roles
  * Search members by name
  * Admin controls: remove member, change role (admin only)
  * Invite members button (admin only)
  * Show join date and role badge
  * Pagination (20 per page)
- UI:
  * Use galaxy theme with glass morphism
  * Glass cards for each member
  * Role badges (admin/member)
  * Gradient buttons for actions
  * Responsive layout
- Properties: $groupId, $members, $search, $page
- Methods: searchMembers(), removeMember(), changeRole(), inviteMembers()

Output: Complete PHP component and Blade view with pagination
```

---

## Execution Steps

1. **Spawn all 5 sub-agents in parallel**:
   ```bash
   JOB1=$(python3 spawn_sub_agent.py gemini "Job 1 prompt...")
   JOB2=$(python3 spawn_sub_agent.py gemini "Job 2 prompt...")
   JOB3=$(python3 spawn_sub_agent.py gemini "Job 3 prompt...")
   JOB4=$(python3 spawn_sub_agent.py gemini "Job 4 prompt...")
   JOB5=$(python3 spawn_sub_agent.py gemini "Job 5 prompt...")
   ```

2. **Wait 60-90 seconds for completion**

3. **Review outputs**:
   ```bash
   cat subagent_runs/$JOB1/report.md
   cat subagent_runs/$JOB2/report.md
   # ... etc
   ```

4. **Integrate components** into the codebase

5. **Test components** and fix any issues

6. **Create routes** for group pages

7. **Polish and optimize** for production

---

## Key Resources

- **UI Standards**: `context-engine/domain-contexts/ui-design-standards.md`
- **Task Details**: `context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md`
- **Example Components**: `resources/views/livewire/` directory
- **Models**: `app/Models/Group.php`, `app/Models/Post.php`, `app/Models/Activity.php`
- **Services**: `app/Services/GroupService.php`, `app/Services/GroupContentService.php`

---

## Success Criteria

✅ All 5 components built and integrated
✅ Components work with backend services
✅ Responsive design (mobile, tablet, desktop)
✅ Accessibility compliant (WCAG 2.1 AA)
✅ Galaxy theme applied consistently
✅ All tests passing

---

## Communication

- Check `dev-logs/2025-12-01-17.md` for current status
- Update dev-logs at end of each phase
- Escalate blockers to Agent 1 if needed
- Share sub-agent job IDs for reference

