# Groups Feature - Agent Instructions

## 🎯 Welcome!

You are one of 4 specialized agents working on the **Groups Feature** for FunLynk.

Your proposals have been reviewed by the Architect Agent. This document tells you what to do next.

---

## 📋 Find Your Team

### **Team 1: Database Engineer**
**Your file**: `TEAM_1_NEXT_STEPS.md`

**Status**: ✅ APPROVED - Start immediately

**Summary**: Make 1 minor change (add index on `groups.name`), then implement all migrations, models, factories, and seeders.

**Estimated Time**: 4-6 hours

---

### **Team 2: Backend Engineer**
**Your file**: `TEAM_2_NEXT_STEPS.md`

**Status**: ⚠️ NEEDS REVISION - Fix 4 issues and resubmit

**Summary**: Fix routing strategy, property naming, validation rules, and event names. Resubmit as `BACKEND_PROPOSAL_v2.md`.

**Estimated Time**: 1 hour to revise, then 6-8 hours to implement (after approval)

---

### **Team 3: Frontend Engineer**
**Your file**: `TEAM_3_NEXT_STEPS.md`

**Status**: ⚠️ NEEDS REVISION - Fix 5 issues and resubmit

**Summary**: Fix tag validation, channel naming, event names, and add avatar upload + dashboard routing details. Resubmit as `FRONTEND_PROPOSAL_v2.md`.

**Estimated Time**: 1 hour to revise, then 8-10 hours to implement (after approval)

---

### **Team 4: QA Engineer**
**Your file**: `TEAM_4_NEXT_STEPS.md`

**Status**: ✅ APPROVED - Wait for Teams 2 & 3 to complete

**Summary**: Your testing plan is excellent. Wait for implementation to complete, then write and run all tests.

**Estimated Time**: 6-8 hours (once you start)

---

## 📖 How To Use This System

### **Step 1: Read Your Team File**
Open your team's `TEAM_X_NEXT_STEPS.md` file and read it carefully.

### **Step 2: Read the Full Review**
Open `PROPOSAL_REVIEW_RESULTS.md` and read your team's section for detailed explanations.

### **Step 3: Follow Instructions**
Your team file contains:
- ✅ Checklist of what to do
- 📝 Code examples to copy
- ⏱️ Time estimates
- 🚫 Blockers (if any)

### **Step 4: Report Progress**
When you complete a task, update `PROPOSAL_REVIEW_RESULTS.md` at the bottom in the "Team Progress Updates" section.

**Example**:
```markdown
## Team Progress Updates

### Team 1 (Database) - 2025-12-01 15:30
✅ COMPLETE
- All 7 migrations created and tested
- All 3 models created with relationships
- Factories and seeders working
```

### **Step 5: Ask Questions**
If you have questions, add them to `PROPOSAL_REVIEW_RESULTS.md` and tag the Architect Agent.

---

## 🔄 Workflow Overview

```
Team 1 (Database)
    ↓ (completes first)
    ├─→ Team 2 (Backend) ──┐
    │                      │
    └─→ Team 3 (Frontend) ─┤
                           ↓
                    Team 4 (Testing)
```

**Dependencies**:
- Team 1 must complete first (everyone needs the database)
- Teams 2 & 3 work in parallel (after Team 1)
- Team 4 waits for Teams 2 & 3 to complete

---

## 📁 File Structure

```
context-engine/tasks/E05_Social_Interaction/F03_Groups_Feature/
├── START_HERE.md                    ← You are here
├── PROPOSAL_REVIEW_RESULTS.md       ← Full review with details
├── TEAM_1_NEXT_STEPS.md             ← Database team instructions
├── TEAM_2_NEXT_STEPS.md             ← Backend team instructions
├── TEAM_3_NEXT_STEPS.md             ← Frontend team instructions
├── TEAM_4_NEXT_STEPS.md             ← Testing team instructions
├── INTEGRATION_CONTRACTS.md         ← Shared API contracts (Team 2 updates)
├── REQUIREMENTS.md                  ← Feature requirements
├── ARCHITECTURE.md                  ← System architecture
├── TEAM_1_DATABASE.md               ← Original database brief
├── TEAM_2_BACKEND.md                ← Original backend brief
├── TEAM_3_FRONTEND.md               ← Original frontend brief
├── TEAM_4_TESTING.md                ← Original testing brief
├── SCHEMA_PROPOSAL.md               ← Team 1's original proposal
├── BACKEND_PROPOSAL.md              ← Team 2's original proposal
├── FRONTEND_PROPOSAL.md             ← Team 3's original proposal
└── TESTING_PROPOSAL.md              ← Team 4's original proposal
```

---

## ✅ Quick Start

1. **Identify your team** (Database, Backend, Frontend, or Testing)
2. **Open your `TEAM_X_NEXT_STEPS.md` file**
3. **Read the instructions carefully**
4. **Follow the checklist**
5. **Report progress when done**

---

## 🎯 Success Criteria

The feature is complete when:
- ✅ All migrations run successfully
- ✅ All services work correctly
- ✅ All components render correctly
- ✅ All tests pass (>80% coverage)
- ✅ No N+1 queries
- ✅ No slow queries
- ✅ Galaxy theme applied everywhere
- ✅ Real-time updates work

---

## 📞 Communication

**Daily Updates**: Post progress in `PROPOSAL_REVIEW_RESULTS.md`

**Questions**: Ask in `PROPOSAL_REVIEW_RESULTS.md` and tag the Architect

**Blockers**: Report immediately in `PROPOSAL_REVIEW_RESULTS.md`

---

## 🚀 Let's Build This!

You have everything you need. Your team file has detailed instructions with code examples.

**Good luck!** 🎯

---

**Last Updated**: 2025-12-01  
**Architect**: Architect Agent  
**Status**: PROPOSALS REVIEWED - TEAMS ASSIGNED

