# Multi-Agent Coordination Guide - Groups Feature

## Quick Status

| Phase | Component | Owner | Status | Est. Time |
|-------|-----------|-------|--------|-----------|
| 1 | Backend Tests | Agent 1 | ✅ COMPLETE | 2 hrs |
| 2a | Service Tests | Agent 1 | 🔄 IN PROGRESS | 4-6 hrs |
| 2b | Policy Tests | Agent 1 | ⏳ PENDING | 2-3 hrs |
| 2c | Frontend Components | Agent 2 | ⏳ PENDING | 6-8 hrs |
| 2d | Frontend Integration | Agent 2 | ⏳ PENDING | 2-3 hrs |
| 3 | Backend Integration | Agent 1 | ⏳ PENDING | 2-3 hrs |
| 4 | Polish & Accessibility | Agent 2 | ⏳ PENDING | 3-4 hrs |

**Total Estimated Time**: 14-18 hours (parallel work reduces actual time)

---

## Agent 1 (Claude/Augment) - Backend

**Assigned Work**:
- Phase 2a: Fix GroupService, GroupChatService, GroupContentService tests
- Phase 2b: Fix GroupPolicy and PostPolicy tests
- Phase 3: Integration testing and bug fixes

**Key Files**:
- `AGENT1_BACKEND_ASSIGNMENT.md` - Detailed work plan
- `tests/Feature/Feature/GroupServiceTest.php`
- `tests/Feature/Feature/GroupPolicyTest.php`
- `app/Services/GroupService.php`
- `app/Policies/GroupPolicy.php`

**Success Criteria**:
- All backend tests passing
- No transaction failures
- Ready for frontend integration

---

## Agent 2 (Other Agent) - Frontend

**Assigned Work**:
- Phase 2c: Build 5 frontend components (parallel sub-agents)
- Phase 2d: Integrate components with backend
- Phase 4: Polish, responsive design, accessibility

**Key Files**:
- `AGENT2_FRONTEND_ASSIGNMENT.md` - Detailed work plan with sub-agent prompts
- `resources/views/livewire/groups/` - Component views
- `app/Livewire/Groups/` - Component classes
- `context-engine/domain-contexts/ui-design-standards.md` - UI guidelines

**Success Criteria**:
- 5 components built and integrated
- Responsive design verified
- Accessibility compliant
- Galaxy theme applied

---

## Dependency Chain

```
Phase 1 (COMPLETE)
    ↓
Phase 2a (Agent 1) ← Phase 2c (Agent 2) can start in parallel
    ↓
Phase 2b (Agent 1) ← Phase 2d (Agent 2) waits for Phase 2b
    ↓
Phase 3 (Agent 1) ← Phase 2d (Agent 2) can proceed
    ↓
Phase 4 (Agent 2)
```

**Key Point**: Agent 2 can start Phase 2c immediately while Agent 1 works on Phase 2a. Phase 2d requires Phase 2b to be complete.

---

## Daily Sync Protocol

### Morning (Start of Day)
1. Both agents read `dev-logs/2025-12-01-17.md`
2. Check for blockers from previous day
3. Plan day's work

### Mid-Day (Every 4 hours)
1. Update dev-logs with progress
2. Note any blockers
3. Coordinate if dependencies are affected

### Evening (End of Day)
1. Update dev-logs with final status
2. Document blockers and solutions
3. Prepare for next day

---

## Blocker Escalation

If you encounter a blocker:

1. **Document it**:
   - What is the blocker?
   - What have you tried?
   - What's the error message?

2. **Update dev-logs**:
   ```markdown
   ## Blockers
   - [BLOCKER] {Description}
     - Error: {Error message}
     - Attempted: {What you tried}
     - Needs: {What's needed to unblock}
   ```

3. **Notify other agent**:
   - If it affects their work, mention it
   - Suggest potential solutions
   - Ask for help if needed

---

## Sub-Agent Coordination

### Agent 1 Sub-Agent Jobs
- Spawn for backend test fixes
- Use detailed prompts from `AGENT1_BACKEND_ASSIGNMENT.md`
- Document job IDs in dev-logs

### Agent 2 Sub-Agent Jobs
- Spawn 5 components in parallel
- Use prompts from `AGENT2_FRONTEND_ASSIGNMENT.md`
- Document job IDs in dev-logs

### Job Tracking
```bash
# Record job IDs
echo "JOB1=$JOB1" >> dev-logs/subagent_jobs.txt
echo "JOB2=$JOB2" >> dev-logs/subagent_jobs.txt

# Check status
cat subagent_runs/$JOB1/status.json

# Review output
cat subagent_runs/$JOB1/report.md
```

---

## Integration Points

### Backend → Frontend
- **Models**: Group, GroupMember, Post, Activity
- **Services**: GroupService, GroupContentService, GroupChatService
- **Policies**: GroupPolicy, PostPolicy
- **Routes**: `/groups`, `/groups/{id}`, `/groups/{id}/members`

### Frontend → Backend
- **API Endpoints**: RESTful endpoints for CRUD operations
- **Livewire Actions**: Wire methods for real-time updates
- **Validation**: Server-side validation in policies/services

---

## Key Resources

- **Project Context**: `context-engine/global-context.md`
- **Epic Overview**: `context-engine/epics/E05_Social_Interaction/epic-overview.md`
- **Task Details**: `context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md`
- **UI Standards**: `context-engine/domain-contexts/ui-design-standards.md`
- **Database Schema**: `context-engine/epics/E05_Social_Interaction/database-schema.md`

---

## Success Metrics

**Backend (Agent 1)**:
- ✅ 100% of backend tests passing
- ✅ No transaction failures
- ✅ All services working correctly
- ✅ All policies enforcing correctly

**Frontend (Agent 2)**:
- ✅ 5 components built and integrated
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Accessibility compliant (WCAG 2.1 AA)
- ✅ Galaxy theme applied consistently

**Overall**:
- ✅ Feature complete and production-ready
- ✅ All tests passing
- ✅ Documentation updated
- ✅ Ready for deployment

