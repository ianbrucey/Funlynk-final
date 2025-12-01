# 🚀 Agent 2: Start Here

**Your Mission**: Complete Groups Feature Frontend Integration (Phase 2d)  
**Time**: 3-4 hours  
**Status**: Phase 2c Complete ✅ → Phase 2d Ready ⏳

---

## ⚡ Quick Start (5 Minutes)

### Step 1: Read the Handoff Document
```bash
cat AGENT2_INTEGRATION_HANDOFF.md
```
This tells you everything you need to know.

### Step 2: Review Your Work
Your Phase 2c components are excellent! Here's what you built:
- ✅ All 5 components (GroupsIndex, GroupShow, CreateGroup, GroupTimeline, GroupMembers)
- ✅ Galaxy theme applied correctly
- ✅ Routes configured
- ✅ Basic functionality working

### Step 3: Understand What's Left
5 integration tasks (3 can be delegated to sub-agents):
1. Fix GroupTimeline service integration (sub-agent)
2. Create post/event modals (sub-agent)
3. Add error handling & loading states (sub-agent)
4. Fix nested component rendering (you)
5. End-to-end testing (you)

---

## 🎯 Execute Now (Copy/Paste Ready)

### Spawn 3 Sub-Agents in Parallel

```bash
# Task 1: Fix GroupTimeline Service Integration
JOB1=$(python3 spawn_sub_agent.py gemini "Read the following files for context:
- app/Livewire/Groups/GroupTimeline.php
- app/Services/GroupContentService.php
- app/Models/Group.php
- app/Models/Post.php
- app/Models/Activity.php

Fix the GroupTimeline component to use GroupContentService instead of direct model queries.

**Current Issue**:
The component queries \$this->group->posts() and \$this->group->activities() directly in the loadMore() method (lines 34-50). This bypasses business logic and is inconsistent with backend architecture.

**Required Changes**:

1. **Inject GroupContentService** in the component:
   \`\`\`php
   protected GroupContentService \$contentService;
   
   public function boot(GroupContentService \$contentService)
   {
       \$this->contentService = \$contentService;
   }
   \`\`\`

2. **Replace loadMore() method** to use service:
   \`\`\`php
   public function loadMore(): void
   {
       if (!\$this->hasMore) {
           return;
       }
       
       \$newItems = \$this->contentService->getGroupTimeline(\$this->group, \$this->page, \$this->perPage);
       
       \$this->items = \$this->items->concat(\$newItems);
       \$this->page++;
       \$this->hasMore = (\$newItems->count() === \$this->perPage);
   }
   \`\`\`

3. **Keep existing methods** (createPost, createEvent, likeItem, deleteItem, Echo listeners) unchanged

**Output format**: Complete updated GroupTimeline.php file with proper formatting")

echo "Task 1 job: $JOB1"

# Task 2: Create Post/Event Modals
JOB2=$(python3 spawn_sub_agent.py gemini "Read the following files for context:
- context-engine/domain-contexts/ui-design-standards.md
- resources/views/livewire/auth/login.blade.php (form example)
- app/Services/GroupContentService.php
- app/Models/Post.php
- app/Models/Activity.php

Create two Livewire modal components for creating group posts and events.

**Component 1: CreateGroupPost**

File: app/Livewire/Groups/CreateGroupPost.php

Properties: public Group \$group; public string \$title = ''; public string \$description = ''; public array \$selectedTags = []; public ?string \$locationName = null; public \$expiresAt = null;

Methods: mount(Group \$group), createPost(), rules()

Validation: title (required, max:100), description (required, max:500), selectedTags (nullable, array), locationName (nullable, max:255), expiresAt (required, date, after:now)

Use GroupContentService::createGroupPost() to create the post.

View: resources/views/livewire/groups/create-group-post.blade.php with modal overlay, glass card, form fields, tag multi-select, date/time picker, gradient submit button, galaxy theme.

**Component 2: CreateGroupEvent**

File: app/Livewire/Groups/CreateGroupEvent.php

Properties: public Group \$group; public string \$title = ''; public string \$description = ''; public string \$locationName = ''; public \$startTime = null; public \$endTime = null; public ?int \$maxAttendees = null; public array \$selectedTags = [];

Methods: mount(Group \$group), createEvent(), rules()

Validation: title (required, max:100), description (required, max:500), locationName (required, max:255), startTime (required, date, after:now), endTime (required, date, after:startTime), maxAttendees (nullable, integer, min:1), selectedTags (nullable, array)

Use GroupContentService::createGroupEvent() to create the event.

View: resources/views/livewire/groups/create-group-event.blade.php with modal overlay, glass card, form fields, tag multi-select, date/time pickers, max attendees input, gradient submit button, galaxy theme.

**Output format**: 4 complete files (2 PHP components + 2 Blade views) with proper formatting")

echo "Task 2 job: $JOB2"

# Task 3: Add Error Handling & Loading States
JOB3=$(python3 spawn_sub_agent.py gemini "Read the following files for context:
- app/Livewire/Groups/GroupsIndex.php
- app/Livewire/Groups/GroupShow.php
- app/Livewire/Groups/GroupMembers.php
- resources/views/livewire/groups/groups-index.blade.php
- resources/views/livewire/groups/group-show.blade.php
- resources/views/livewire/groups/group-members.blade.php

Add comprehensive error handling and loading states to all group components.

For each component, wrap all service calls in try-catch blocks with flash messages, and add wire:loading directives to all action buttons.

Example for GroupsIndex joinGroup():
\`\`\`php
public function joinGroup(string \$groupId): void
{
    try {
        \$group = Group::findOrFail(\$groupId);
        \$this->groupService->addMember(\$group, Auth::user());
        session()->flash('success', 'Successfully joined the group!');
    } catch (\Exception \$e) {
        session()->flash('error', 'Failed to join group: ' . \$e->getMessage());
    }
    \$this->loadUserGroups();
}
\`\`\`

Add flash message display and loading states to all views.

**Output format**: 6 complete updated files (3 PHP + 3 Blade) with proper formatting")

echo "Task 3 job: $JOB3"

echo ""
echo "✅ Spawned 3 sub-agent jobs: $JOB1, $JOB2, $JOB3"
echo "⏳ Wait 90 seconds, then check outputs..."
```

---

## 📚 Full Documentation

- **AGENT2_INTEGRATION_HANDOFF.md** - Complete instructions
- **FRONTEND_INTEGRATION_TASKS.md** - Detailed sub-agent prompts
- **FRONTEND_INTEGRATION_ASSESSMENT.md** - What needs fixing
- **BACKEND_VERIFICATION_REPORT.md** - Backend is ready

---

## ✅ Success = Groups Feature 100% Complete

You're 70% there. Just 3-4 hours to go! 🚀

