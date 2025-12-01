# Agent 2 Quick Start Guide

## Welcome!

You're taking over the frontend for the Groups feature (E05/F03). Agent 1 is handling backend tests and integration. Your job is to build 5 frontend components and integrate them with the backend.

**Estimated Time**: 14-18 hours  
**Parallel Work**: Use 5 sub-agents to build components simultaneously

---

## 30-Second Overview

1. **Read** `GROUPS_FEATURE_STATUS.md` for current status
2. **Read** `AGENT2_FRONTEND_ASSIGNMENT.md` for your detailed work plan
3. **Spawn 5 sub-agents** using the prompts provided
4. **Integrate components** into the application
5. **Test and polish** for production

---

## Your 5 Components

| Component | Purpose | Complexity |
|-----------|---------|-----------|
| GroupsIndex | List all groups with search/filter | Medium |
| GroupShow | Display single group details | Medium |
| CreateGroup | Create new group form | Medium |
| GroupTimeline | Group posts/events feed | High |
| GroupMembers | Manage group members | Medium |

---

## Quick Start (5 minutes)

### Step 1: Read the Assignment
```bash
cat AGENT2_FRONTEND_ASSIGNMENT.md
```

### Step 2: Understand the Context
- **UI Standards**: `context-engine/domain-contexts/ui-design-standards.md`
- **Task Details**: `context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md`
- **Example Components**: `resources/views/livewire/` directory

### Step 3: Spawn Sub-Agents
Copy the 5 prompts from `AGENT2_FRONTEND_ASSIGNMENT.md` and run:
```bash
JOB1=$(python3 spawn_sub_agent.py gemini "Job 1 prompt...")
JOB2=$(python3 spawn_sub_agent.py gemini "Job 2 prompt...")
JOB3=$(python3 spawn_sub_agent.py gemini "Job 3 prompt...")
JOB4=$(python3 spawn_sub_agent.py gemini "Job 4 prompt...")
JOB5=$(python3 spawn_sub_agent.py gemini "Job 5 prompt...")

echo "Jobs spawned: $JOB1, $JOB2, $JOB3, $JOB4, $JOB5"
```

### Step 4: Wait for Completion
```bash
sleep 90  # Wait for sub-agents to complete

# Check status
cat subagent_runs/$JOB1/status.json
```

### Step 5: Review Outputs
```bash
cat subagent_runs/$JOB1/report.md
cat subagent_runs/$JOB2/report.md
# ... etc
```

### Step 6: Integrate Components
Copy component files from sub-agent outputs to:
- `app/Livewire/Groups/{ComponentName}.php`
- `resources/views/livewire/groups/{component-name}.blade.php`

### Step 7: Test Components
```bash
php artisan test tests/Feature/Feature/
```

---

## Key Files to Know

**Models** (read-only):
- `app/Models/Group.php` - Group model
- `app/Models/GroupMember.php` - Membership pivot
- `app/Models/Post.php` - Posts model
- `app/Models/Activity.php` - Activities model

**Services** (use these in components):
- `app/Services/GroupService.php` - Group CRUD
- `app/Services/GroupContentService.php` - Posts/events
- `app/Services/GroupChatService.php` - Chat

**UI Standards**:
- `context-engine/domain-contexts/ui-design-standards.md` - Galaxy theme guide
- `resources/views/welcome.blade.php` - Theme example
- `resources/views/livewire/auth/login.blade.php` - Form example

---

## Important Rules

✅ **DO**:
- Use galaxy theme with glass morphism
- Use DaisyUI components
- Use gradient buttons (pink-500 to purple-500)
- Use cyan focus glow on forms
- Make components responsive
- Test with backend services

❌ **DON'T**:
- Create custom CSS (use DaisyUI classes)
- Hardcode data (use services)
- Skip accessibility
- Forget responsive design
- Ignore the galaxy theme

---

## Dependency Chain

```
Agent 1 Phase 2a (Service Tests)
    ↓
Agent 1 Phase 2b (Policy Tests) ← You can start Phase 2c now!
    ↓
Agent 1 Phase 3 (Integration) ← You need this for Phase 2d
    ↓
You Phase 2d (Frontend Integration)
    ↓
You Phase 4 (Polish)
```

**Key Point**: You can start building components immediately while Agent 1 fixes backend tests. You'll need Phase 2b complete before integrating with backend.

---

## Communication

- **Status Updates**: Update `dev-logs/2025-12-01-17.md` every 4 hours
- **Blockers**: Document in dev-logs and notify Agent 1
- **Questions**: Check `MULTI_AGENT_COORDINATION.md` for coordination guide

---

## Success Criteria

✅ 5 components built and integrated
✅ Components work with backend services
✅ Responsive design (mobile, tablet, desktop)
✅ Accessibility compliant (WCAG 2.1 AA)
✅ Galaxy theme applied consistently
✅ All tests passing

---

## Need Help?

1. **Check documentation**: `AGENT2_FRONTEND_ASSIGNMENT.md`
2. **Review examples**: `resources/views/livewire/` directory
3. **Read UI standards**: `context-engine/domain-contexts/ui-design-standards.md`
4. **Ask Agent 1**: If you need backend clarification

---

## Let's Go! 🚀

You're ready to start. Begin with Step 1 above and follow the Quick Start guide. Good luck!

