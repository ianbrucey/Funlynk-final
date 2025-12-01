# Groups Feature (E05/F03) - Multi-Agent Workplan

## Current Status: 60% Complete

| Component | Status | Owner | Est. Time |
|-----------|--------|-------|-----------|
| **Backend** | 95% | Agent 1 | 4-6 hrs |
| **Frontend** | 0% | Agent 2 | 8-10 hrs |
| **Integration** | 0% | Agent 1 | 2-3 hrs |
| **Polish** | 0% | Agent 2 | 3-4 hrs |

---

## Agent 1 (Claude/Augment) - Backend & Integration

### Phase 2a: Backend Service Tests (4-6 hours)
**Objective**: Fix failing service tests to validate business logic

**Tests to Fix**:
1. `tests/Feature/Feature/GroupServiceTest.php` - 16 tests, ~8 failing
2. `tests/Feature/Feature/GroupChatServiceTest.php` - 6 tests, all failing
3. `tests/Feature/Feature/GroupContentServiceTest.php` - 5 tests, all failing

**Key Issues**:
- Event listeners causing transaction failures
- Group::admins() relationship not returning relationship instance
- Missing Event::fake() in test setup

**Deliverable**: All backend service tests passing

### Phase 2b: Backend Policy Tests (2-3 hours)
**Objective**: Fix authorization policy tests

**Tests to Fix**:
1. `tests/Feature/Feature/GroupPolicyTest.php` - 20 tests, ~15 failing
2. `tests/Feature/Feature/PostPolicyTest.php` - 18 tests, ~9 failing

**Key Issues**:
- GroupPolicy::create() needs nullable User parameter
- PostPolicy::create() needs nullable User parameter
- Transaction failures from event listeners

**Deliverable**: All policy tests passing

### Phase 3: Integration & Bug Fixes (2-3 hours)
**Objective**: Ensure backend works end-to-end

**Tasks**:
1. Run full backend test suite
2. Fix any remaining issues
3. Document architectural decisions
4. Prepare for frontend integration

**Deliverable**: All backend tests passing, ready for frontend

---

## Agent 2 (Other Agent) - Frontend & Polish

### Phase 2c: Frontend Components (6-8 hours)
**Objective**: Build 5 frontend components using sub-agents in parallel

**Components to Build** (spawn 5 sub-agents):

1. **GroupsIndex Component**
   - List all groups with search/filter
   - Pagination or infinite scroll
   - Join/leave buttons
   - Tag filtering

2. **GroupShow Component**
   - Display group details
   - Join/leave logic
   - Member list preview
   - Group timeline preview

3. **CreateGroup Component**
   - Group creation form
   - Name, description, privacy, tags
   - Location input with geocoding
   - Form validation

4. **GroupTimeline Component**
   - Display group posts and events
   - Infinite scroll
   - Create post/event buttons
   - Reactions and comments

5. **GroupMembers Component**
   - List group members
   - Admin controls (remove, change role)
   - Invite members
   - Member search

**Sub-Agent Prompt Template**:
```
Read context-engine/domain-contexts/ui-design-standards.md
Read context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md
Read resources/views/welcome.blade.php (galaxy theme example)

Create Livewire v3 component: {ComponentName}
Location: app/Livewire/Groups/{ComponentName}.php
View: resources/views/livewire/groups/{component-name}.blade.php

Requirements:
- Use galaxy theme with glass morphism
- DaisyUI components
- Gradient buttons (pink-500 to purple-500)
- Cyan focus glow on forms
- Responsive design
- Include specific features: {list features}

Output: PHP component + Blade view
```

**Deliverable**: 5 working Livewire components

### Phase 2d: Frontend Integration (2-3 hours)
**Objective**: Integrate components into application

**Tasks**:
1. Create routes for group pages
2. Integrate components into layout
3. Test component interactions
4. Fix any integration issues

**Deliverable**: Frontend components integrated and working

### Phase 4: Polish & Accessibility (3-4 hours)
**Objective**: Production-ready frontend

**Tasks**:
1. Responsive design verification (mobile, tablet, desktop)
2. Accessibility audit (WCAG 2.1 AA)
3. Performance optimization
4. User acceptance testing

**Deliverable**: Production-ready frontend

---

## Communication Protocol

### Daily Sync Points
- **Start of day**: Review dev-logs/YYYY-MM-DD-HH.md
- **Mid-day**: Update status in dev-logs
- **End of day**: Update dev-logs with blockers and next steps

### Blocker Escalation
If either agent encounters a blocker:
1. Document in dev-logs with specific error
2. Tag the other agent
3. Provide context and attempted solutions
4. Suggest potential fixes

### Sub-Agent Coordination
- **Agent 1**: Spawns sub-agents for backend fixes
- **Agent 2**: Spawns sub-agents for frontend components
- Both agents use `spawn_sub_agent.py` with detailed prompts
- Share job IDs in dev-logs for reference

---

## Success Criteria

✅ **Phase 1**: All backend tests passing (COMPLETE)
⏳ **Phase 2a**: All service tests passing
⏳ **Phase 2b**: All policy tests passing
⏳ **Phase 2c**: 5 frontend components built and integrated
⏳ **Phase 2d**: Frontend integrated with backend
⏳ **Phase 3**: All integration tests passing
⏳ **Phase 4**: Production-ready feature

**Target Completion**: 14-18 hours from now

