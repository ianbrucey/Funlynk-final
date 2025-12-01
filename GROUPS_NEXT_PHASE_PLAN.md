# Groups Feature - Next Phase Implementation Plan
**Using Multi-Agent Workflow Pattern**

---

## Phase 1: Backend Verification (TODAY - 30 min)

### Task 1.1: Fix Test Failures
**Primary Agent**: Read and understand the null email error  
**Sub-Agent**: Fix `ActivityPolicyTest.php` null email errors

```bash
python3 spawn_sub_agent.py gemini "Read the following files:
- tests/Feature/Feature/ActivityPolicyTest.php
- database/factories/UserFactory.php

Fix all 'null email' errors in ActivityPolicyTest.php by ensuring all User::factory()->create() calls have unique email addresses. Use the pattern from UserFactory.php.

Output: Complete corrected ActivityPolicyTest.php file"
```

### Task 1.2: Verify All Tests Pass
```bash
php artisan test
php artisan migrate:fresh --seed
```

**Status**: Mark backend as COMPLETE once tests pass ✅

---

## Phase 2: Frontend Integration (NEXT SESSION - 6-8 hours)

### Task 2.1: GroupsIndex Component
**Delegate to Sub-Agent**: Implement data fetching and search

```bash
python3 spawn_sub_agent.py gemini "Read:
- app/Livewire/Groups/GroupsIndex.php
- app/Services/GroupService.php
- context-engine/domain-contexts/service-architecture.md

Implement GroupsIndex component:
1. Inject GroupService via constructor
2. Implement mount() to fetch user's groups and popular groups
3. Implement updatedSearch() to search groups by name
4. Implement toggleTag() to filter by tags
5. Add error handling and loading states

Output: Complete GroupsIndex.php with all methods implemented"
```

### Task 2.2: GroupShow Component
**Delegate to Sub-Agent**: Implement group viewing and join/leave logic

```bash
python3 spawn_sub_agent.py gemini "Read:
- app/Livewire/Groups/GroupShow.php
- app/Services/GroupService.php
- app/Policies/GroupPolicy.php

Implement GroupShow component:
1. Load group data in mount()
2. Implement joinGroup() method
3. Implement leaveGroup() method
4. Implement approveRequest() for admins
5. Add authorization checks using policies
6. Add error handling

Output: Complete GroupShow.php with all methods"
```

### Task 2.3: CreateGroup Component
**Delegate to Sub-Agent**: Implement group creation form

```bash
python3 spawn_sub_agent.py gemini "Read:
- app/Livewire/Groups/CreateGroup.php
- app/Services/GroupService.php
- app/Http/Requests/CreateGroupRequest.php

Implement CreateGroup component:
1. Implement submit() method to create group
2. Handle file upload for avatar
3. Add form validation
4. Add error handling and success messages
5. Redirect to group page after creation

Output: Complete CreateGroup.php with form submission logic"
```

### Task 2.4: GroupTimeline Component
**Delegate to Sub-Agent**: Implement group feed with infinite scroll

```bash
python3 spawn_sub_agent.py gemini "Read:
- app/Livewire/Groups/GroupTimeline.php
- app/Services/GroupContentService.php
- resources/views/livewire/groups/group-timeline.blade.php

Implement GroupTimeline component:
1. Fetch group posts/activities in mount()
2. Implement loadMore() for infinite scroll
3. Wire Echo listeners to handlePostCreated() and handleActivityCreated()
4. Add loading states
5. Add error handling

Output: Complete GroupTimeline.php with infinite scroll and real-time updates"
```

### Task 2.5: GroupMembers Component
**Delegate to Sub-Agent**: Implement member management

```bash
python3 spawn_sub_agent.py gemini "Read:
- app/Livewire/Groups/GroupMembers.php
- app/Services/GroupService.php
- app/Policies/GroupPolicy.php

Implement GroupMembers component:
1. Fetch members and join requests in mount()
2. Implement approveMember() for admins
3. Implement removeMember() for admins
4. Implement rejectRequest() for admins
5. Add authorization checks
6. Wire Echo listeners for real-time updates

Output: Complete GroupMembers.php with member management"
```

---

## Phase 3: Feature Completion (FOLLOWING SESSION - 4-6 hours)

### Task 3.1: GroupSettings Component
**Delegate to Sub-Agent**: Implement group editing

### Task 3.2: JoinRequestsList Component
**Delegate to Sub-Agent**: Implement join request management

### Task 3.3: GroupTagFilter Component
**Delegate to Sub-Agent**: Implement tag filtering

### Task 3.4: Component Tests
**Delegate to Sub-Agent**: Write Pest tests for all components

---

## Phase 4: Polish & Launch (FINAL SESSION - 3-4 hours)

### Task 4.1: Responsive Design
**Primary Agent**: Verify mobile layout

### Task 4.2: Accessibility Audit
**Delegate to Sub-Agent**: Add ARIA labels and keyboard navigation

### Task 4.3: Performance Optimization
**Primary Agent**: Implement caching and lazy loading

### Task 4.4: User Acceptance Testing
**Primary Agent**: Manual testing of all features

---

## Parallel Execution Strategy

### Session 2 (Frontend Integration)
Spawn all 5 sub-agents in parallel:
```bash
JOB1=$(python3 spawn_sub_agent.py gemini "GroupsIndex implementation...")
JOB2=$(python3 spawn_sub_agent.py gemini "GroupShow implementation...")
JOB3=$(python3 spawn_sub_agent.py gemini "CreateGroup implementation...")
JOB4=$(python3 spawn_sub_agent.py gemini "GroupTimeline implementation...")
JOB5=$(python3 spawn_sub_agent.py gemini "GroupMembers implementation...")

# Primary agent works on other tasks while sub-agents execute
# After 60-90 seconds, review all outputs
```

### Integration Workflow
1. Review each sub-agent output
2. Copy code to appropriate files
3. Run `vendor/bin/pint --dirty` to format
4. Run `php artisan test` to verify
5. Iterate if needed

---

## Success Criteria

### Phase 1 ✅
- [ ] All tests pass
- [ ] `php artisan migrate:fresh --seed` succeeds
- [ ] Backend marked COMPLETE

### Phase 2 ✅
- [ ] All components fetch real data
- [ ] All forms submit successfully
- [ ] Error handling works
- [ ] Loading states display
- [ ] No console errors

### Phase 3 ✅
- [ ] All components fully implemented
- [ ] All Pest tests pass
- [ ] Code coverage > 80%

### Phase 4 ✅
- [ ] Mobile layout verified
- [ ] Accessibility audit passed
- [ ] Performance metrics acceptable
- [ ] User acceptance testing passed
- [ ] Feature ready for production

---

## Estimated Timeline

| Phase | Duration | Status |
|-------|----------|--------|
| Phase 1: Backend Fix | 30 min | 🔴 TODO |
| Phase 2: Frontend Integration | 6-8 hrs | ⏳ NEXT |
| Phase 3: Feature Completion | 4-6 hrs | ⏳ LATER |
| Phase 4: Polish & Launch | 3-4 hrs | ⏳ FINAL |
| **TOTAL** | **14-18 hrs** | |

---

## Key Decisions

1. **Parallel Sub-Agent Execution**: All 5 frontend components implemented simultaneously
2. **Service Injection**: All components use dependency injection for services
3. **Error Handling**: Try-catch blocks with user-friendly error messages
4. **Real-time Updates**: Echo listeners wired to component methods
5. **Testing**: Pest tests for all components before launch

---

## Risk Mitigation

| Risk | Mitigation |
|------|-----------|
| Sub-agent outputs don't compile | Review code before integration, iterate if needed |
| Real-time updates fail | Test Echo setup separately before integration |
| Performance issues | Implement pagination and caching early |
| Mobile layout breaks | Test on multiple devices during Phase 4 |
| Authorization bypass | Run security audit before launch |

---

## Notes for Next Session

- Start with Phase 1 (backend fix) - should be quick
- Use parallel sub-agent execution for Phase 2 to save time
- Review each sub-agent output carefully before integration
- Run tests after each component integration
- Document any issues or deviations from plan

