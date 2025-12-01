# Handoff Prompt for Agent 2 - Groups Feature Frontend

## Your Mission

You are taking over the **frontend development** for the Groups feature (E05/F03) in the FunLynk Laravel 12 application. Agent 1 (Claude/Augment) is handling backend tests and integration in parallel. Your job is to build 5 frontend components and integrate them with the backend.

**Estimated Duration**: 14-18 hours total (parallel with Agent 1)  
**Your Phases**: 2c (6-8 hrs), 2d (2-3 hrs), 4 (3-4 hrs)

---

## Current Status

✅ **Phase 1 Complete**: Backend tests fixed (22/22 passing)
🔄 **Phase 2a In Progress**: Agent 1 fixing service tests
⏳ **Phase 2c Ready**: You can start immediately building frontend components

**Key Point**: You don't need to wait for Agent 1. Start building components now while they fix backend tests.

---

## Your Responsibilities

### Phase 2c: Build 5 Frontend Components (6-8 hours)
Build these Livewire v3 components using sub-agents in parallel:

1. **GroupsIndex** - List all groups with search/filter
2. **GroupShow** - Display single group details
3. **CreateGroup** - Create new group form
4. **GroupTimeline** - Group posts/events feed
5. **GroupMembers** - Manage group members

### Phase 2d: Frontend Integration (2-3 hours)
- Integrate components with backend services
- Create routes for group pages
- Test component interactions

### Phase 4: Polish & Accessibility (3-4 hours)
- Responsive design verification
- Accessibility audit (WCAG 2.1 AA)
- Performance optimization

---

## Getting Started (5 Minutes)

### Step 1: Read Your Assignment
```bash
cat AGENT2_QUICK_START.md
cat AGENT2_FRONTEND_ASSIGNMENT.md
```

### Step 2: Understand the Context
- **UI Standards**: `context-engine/domain-contexts/ui-design-standards.md`
- **Task Details**: `context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md`
- **Example Components**: `resources/views/livewire/` directory
- **Models**: `app/Models/Group.php`, `app/Models/Post.php`, `app/Models/Activity.php`
- **Services**: `app/Services/GroupService.php`, `app/Services/GroupContentService.php`

### Step 3: Spawn 5 Sub-Agents in Parallel
All 5 prompts are in `AGENT2_FRONTEND_ASSIGNMENT.md`. Copy each prompt and run:

```bash
JOB1=$(python3 spawn_sub_agent.py gemini "PROMPT_1_HERE")
JOB2=$(python3 spawn_sub_agent.py gemini "PROMPT_2_HERE")
JOB3=$(python3 spawn_sub_agent.py gemini "PROMPT_3_HERE")
JOB4=$(python3 spawn_sub_agent.py gemini "PROMPT_4_HERE")
JOB5=$(python3 spawn_sub_agent.py gemini "PROMPT_5_HERE")

echo "Jobs: $JOB1, $JOB2, $JOB3, $JOB4, $JOB5"
```

### Step 4: Wait 60-90 Seconds
Sub-agents will complete in parallel.

### Step 5: Review Outputs
```bash
cat subagent_runs/$JOB1/report.md
cat subagent_runs/$JOB2/report.md
# ... etc
```

### Step 6: Integrate Components
Copy files to:
- `app/Livewire/Groups/{ComponentName}.php`
- `resources/views/livewire/groups/{component-name}.blade.php`

### Step 7: Test
```bash
php artisan test tests/Feature/Feature/
```

---

## Key Resources

**Documentation** (in project root):
- `AGENT2_QUICK_START.md` - Quick start guide
- `AGENT2_FRONTEND_ASSIGNMENT.md` - Detailed plan with sub-agent prompts
- `GROUPS_FEATURE_STATUS.md` - Current status
- `MULTI_AGENT_COORDINATION.md` - Coordination with Agent 1
- `HANDOFF_PACKAGE.md` - Document index

**Project Context**:
- `context-engine/global-context.md` - Project overview
- `context-engine/epics/E05_Social_Interaction/epic-overview.md` - Epic details
- `context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md` - Task details
- `context-engine/domain-contexts/ui-design-standards.md` - UI standards (CRITICAL)

**Code Examples**:
- `resources/views/welcome.blade.php` - Galaxy theme example
- `resources/views/livewire/auth/login.blade.php` - Form example
- `resources/views/livewire/` - Other components

---

## Critical Rules

✅ **MUST DO**:
- Use galaxy theme with glass morphism
- Use DaisyUI components
- Use gradient buttons (pink-500 to purple-500)
- Use cyan focus glow on forms
- Make components responsive (mobile, tablet, desktop)
- Test with backend services
- Follow Laravel 12 conventions
- Use Livewire v3 syntax

❌ **MUST NOT DO**:
- Create custom CSS (use DaisyUI classes)
- Hardcode data (use services)
- Skip accessibility
- Forget responsive design
- Ignore the galaxy theme
- Use old Livewire v2 syntax

---

## Dependency Chain

```
Agent 1 Phase 2a (Service Tests) - In Progress
    ↓
Agent 1 Phase 2b (Policy Tests) ← You can start Phase 2c NOW!
    ↓
Agent 1 Phase 3 (Integration) ← You need this for Phase 2d
    ↓
You Phase 2d (Frontend Integration)
    ↓
You Phase 4 (Polish)
```

**Key Point**: Start Phase 2c immediately. You'll need Phase 2b complete before Phase 2d.

---

## Communication Protocol

**Daily Sync**:
- Morning: Read `dev-logs/2025-12-01-17.md`
- Mid-day: Update progress in dev-logs
- Evening: Document blockers and next steps

**Blockers**:
- Document in dev-logs with error details
- Notify Agent 1 if it affects their work
- Suggest potential solutions

**Dev Log**: `dev-logs/2025-12-01-17.md`

---

## Success Criteria

✅ 5 components built and integrated
✅ Components work with backend services
✅ Responsive design (mobile, tablet, desktop)
✅ Accessibility compliant (WCAG 2.1 AA)
✅ Galaxy theme applied consistently
✅ All tests passing

---

## Next Steps

1. Read `AGENT2_QUICK_START.md` (5 minutes)
2. Read `AGENT2_FRONTEND_ASSIGNMENT.md` (10 minutes)
3. Spawn 5 sub-agents (2 minutes)
4. Wait for completion (90 seconds)
5. Integrate components (2-3 hours)
6. Test and iterate (2-3 hours)

**Total Time to First Working Component**: ~3 hours

---

## Questions?

- **UI Standards**: See `context-engine/domain-contexts/ui-design-standards.md`
- **Component Examples**: See `resources/views/livewire/` directory
- **Backend Integration**: See `app/Services/GroupService.php`
- **Coordination**: See `MULTI_AGENT_COORDINATION.md`

---

## Let's Go! 🚀

You're ready to start. Begin with `AGENT2_QUICK_START.md` and follow the 5-minute quick start guide. Good luck!

