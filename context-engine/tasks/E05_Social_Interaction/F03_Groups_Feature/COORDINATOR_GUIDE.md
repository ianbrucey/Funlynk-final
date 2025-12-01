# Groups Feature - Coordinator Guide

## 🎯 Your Role

As the **Architect/Coordinator**, you will:
1. Assign tasks to 4 specialized agent teams
2. Review and approve their proposals
3. Coordinate communication between teams
4. Resolve conflicts and blockers
5. Ensure integration works smoothly
6. Make final approval before deployment

---

## 👥 Team Structure

### **Team 1: Database & Schema Design**
- **Lead**: Database Engineer Agent
- **Priority**: P0 (BLOCKING)
- **Dependencies**: None
- **Deliverables**: Schema proposal → Migrations, models, factories, seeders
- **Brief**: `TEAM_1_DATABASE.md`

### **Team 2: Backend Services & API**
- **Lead**: Backend Engineer Agent
- **Priority**: P1 (High)
- **Dependencies**: Team 1 must complete first
- **Deliverables**: Backend proposal → Services, policies, events, API contracts
- **Brief**: `TEAM_2_BACKEND.md`

### **Team 3: Frontend Components & UI**
- **Lead**: Frontend Engineer Agent
- **Priority**: P1 (High)
- **Dependencies**: Team 1 must complete first, Team 2 for API contracts
- **Deliverables**: Frontend proposal → Livewire components, Blade views, routes
- **Brief**: `TEAM_3_FRONTEND.md`

### **Team 4: Testing & Integration**
- **Lead**: QA Engineer Agent
- **Priority**: P1 (High)
- **Dependencies**: Teams 2 & 3 must complete first
- **Deliverables**: Testing proposal → Test files, bug reports, coverage report
- **Brief**: `TEAM_4_TESTING.md`

---

## 📋 Workflow Phases

### **Phase 0: Kickoff** (You)
✅ **COMPLETE**
- [x] Requirements document created
- [x] Architecture document created
- [x] Team briefs created (4 files)
- [x] Integration contracts template created
- [x] Coordinator guide created

**Next Step**: Assign Team 1 their brief

---

### **Phase 1: Database Foundation** (Team 1)
**Status**: READY TO START

**Team 1 Tasks**:
1. Read `TEAM_1_DATABASE.md`
2. Create `SCHEMA_PROPOSAL.md`
3. Submit for your review

**Your Tasks**:
1. Review schema proposal
2. Check for:
   - All required tables included
   - Proper indexes for performance
   - Foreign key constraints correct
   - Relationships make sense
   - No breaking changes to existing tables
3. Approve or request modifications
4. Once approved, Team 1 implements migrations/models

**Approval Criteria**:
- [ ] All tables from ARCHITECTURE.md included
- [ ] Indexes on all foreign keys
- [ ] Unique constraints where needed
- [ ] Proper cascading deletes
- [ ] group_tag pivot table included
- [ ] Conversations table updated for group chat

**Blockers to Watch For**:
- Conflicts with existing schema
- Missing indexes
- Incorrect relationships

---

### **Phase 2: Parallel Development** (Teams 2 & 3)
**Status**: WAITING FOR TEAM 1

**Team 2 Tasks**:
1. Read `TEAM_2_BACKEND.md`
2. Create `BACKEND_PROPOSAL.md`
3. Submit for your review
4. Once approved, implement services/policies/events
5. Update `INTEGRATION_CONTRACTS.md` with API endpoints

**Team 3 Tasks**:
1. Read `TEAM_3_FRONTEND.md`
2. Create `FRONTEND_PROPOSAL.md`
3. Submit for your review
4. Once approved, implement components/views
5. Use `INTEGRATION_CONTRACTS.md` from Team 2

**Your Tasks**:
1. Review both proposals
2. Ensure they align with each other
3. Check `INTEGRATION_CONTRACTS.md` for completeness
4. Approve or request modifications
5. Monitor progress and resolve blockers

**Approval Criteria (Team 2)**:
- [ ] All services from ARCHITECTURE.md included
- [ ] All policies defined
- [ ] All events defined
- [ ] API contracts documented in INTEGRATION_CONTRACTS.md
- [ ] Validation rules defined
- [ ] Real-time broadcasting implemented

**Approval Criteria (Team 3)**:
- [ ] All components from ARCHITECTURE.md included
- [ ] Galaxy theme applied to all pages
- [ ] Routes defined
- [ ] Real-time Echo listeners implemented
- [ ] Forms have validation
- [ ] Responsive design

**Blockers to Watch For**:
- API contract mismatches between teams
- Missing endpoints
- UI/UX conflicts with requirements
- Performance concerns

---

### **Phase 3: Integration & Testing** (Team 4)
**Status**: WAITING FOR TEAMS 2 & 3

**Team 4 Tasks**:
1. Read `TEAM_4_TESTING.md`
2. Create `TESTING_PROPOSAL.md`
3. Submit for your review
4. Once approved, write and run tests
5. Report bugs to Teams 2 & 3
6. Iterate until all tests pass

**Your Tasks**:
1. Review testing proposal
2. Ensure comprehensive coverage
3. Approve or request modifications
4. Monitor test results
5. Coordinate bug fixes between teams
6. Final approval when all tests pass

**Approval Criteria**:
- [ ] All test cases from proposal included
- [ ] Code coverage > 80%
- [ ] All tests pass
- [ ] No N+1 query issues
- [ ] No slow queries (>100ms)
- [ ] Security tests pass
- [ ] Integration tests pass

**Blockers to Watch For**:
- Design flaws revealed by tests
- Performance issues
- Security vulnerabilities
- Integration failures

---

## 📞 Communication Protocol

### **Team Check-Ins**
- Each team reports progress daily
- Use this format:
  ```
  Team: [1/2/3/4]
  Status: [On Track / Blocked / Complete]
  Progress: [What was done]
  Blockers: [Any issues]
  Next Steps: [What's next]
  ```

### **Conflict Resolution**
If teams disagree:
1. Both teams present their case
2. You make the final decision
3. Document decision in `DECISIONS.md`

### **Blocker Escalation**
If a team is blocked:
1. Team reports blocker immediately
2. You assess severity
3. Reassign work or adjust timeline if needed

---

## ✅ Final Approval Checklist

Before marking the feature complete:
- [ ] All migrations run successfully
- [ ] All models have relationships
- [ ] All services work correctly
- [ ] All policies enforce authorization
- [ ] All components render correctly
- [ ] All forms validate correctly
- [ ] All tests pass (>80% coverage)
- [ ] No N+1 queries
- [ ] No slow queries
- [ ] Galaxy theme applied everywhere
- [ ] Real-time updates work
- [ ] Documentation complete
- [ ] No security vulnerabilities

---

## 🚀 Deployment

Once all teams complete and you approve:
1. Run final test suite
2. Generate code coverage report
3. Review all code with Pint
4. Create deployment checklist
5. Deploy to staging
6. User acceptance testing
7. Deploy to production

---

## 📝 Decision Log

Use this section to document major decisions:

### Decision 1: [Title]
- **Date**: YYYY-MM-DD
- **Issue**: [What was the problem]
- **Options**: [What were the choices]
- **Decision**: [What was decided]
- **Rationale**: [Why this was chosen]

---

**Coordinator**: Architect Agent
**Start Date**: 2025-12-01
**Target Completion**: TBD
**Status**: PHASE 0 COMPLETE - READY TO ASSIGN TEAM 1

