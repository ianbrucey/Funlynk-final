# Team 4: Testing & Integration

## 🎯 Your Mission

Write comprehensive tests for the Groups feature and ensure all components integrate correctly. You will work after Teams 2 and 3 complete their implementations.

---

## 📋 Required Reading

**MUST READ FIRST**:
1. `context-engine/tasks/E05_Social_Interaction/F03_Groups_Feature/REQUIREMENTS.md`
2. `context-engine/tasks/E05_Social_Interaction/F03_Groups_Feature/ARCHITECTURE.md`
3. Team 1's implemented migrations and models
4. Team 2's implemented services and policies
5. Team 3's implemented components
6. Existing test examples:
   - `tests/Feature/DirectMessagesTest.php`
   - `tests/Feature/PostReactionTest.php`

---

## 🎯 Your Responsibilities

### **1. Unit Tests**
Test individual components in isolation:

#### **Model Tests**
- Group model relationships
- GroupMember model relationships
- GroupJoinRequest model relationships
- Model scopes and accessors

#### **Service Tests**
- GroupService methods
- GroupContentService methods
- GroupChatService methods
- Edge cases and error handling

#### **Policy Tests**
- GroupPolicy authorization rules
- GroupPostPolicy authorization rules
- GroupActivityPolicy authorization rules
- Permission edge cases

### **2. Feature Tests**
Test complete user workflows:

#### **Group Management**
- Create public group
- Create private group
- Update group settings
- Delete group
- Add/remove members
- Approve/deny join requests

#### **Group Content**
- Create post in group
- Create event in group
- View group timeline
- Filter timeline by type

#### **Group Chat**
- Send message in group chat
- Receive messages in group chat
- Real-time updates

#### **Group Discovery**
- Search public groups
- Filter groups by tags
- Browse groups by category
- Join/leave groups

### **3. Integration Tests**
Test cross-component interactions:

#### **Post-to-Group Integration**
- Create post with group context
- Post only visible to group members
- Non-members cannot view group posts

#### **Event-to-Group Integration**
- Create event with group context
- Event only visible to group members
- Non-members cannot view group events

#### **Chat-to-Group Integration**
- Group chat created when group is created
- New members added to group chat
- Removed members removed from group chat

#### **Notification Integration**
- Group events trigger notifications
- Notifications sent to correct users
- Real-time broadcasting works

### **4. End-to-End Tests**
Test complete user journeys:

#### **Journey 1: Create and Join Group**
1. User A creates public group with tags
2. User B searches for group by tag
3. User B joins group
4. User B sees group in "My Groups"
5. User B can access group chat

#### **Journey 2: Private Group Workflow**
1. User A creates private group
2. User B requests to join
3. User A (admin) receives notification
4. User A approves request
5. User B receives approval notification
6. User B can now access group

#### **Journey 3: Group Content Workflow**
1. User A creates post in group
2. User B (member) sees post in group timeline
3. User C (non-member) cannot see post
4. User A creates event in group
5. User B can RSVP to event
6. User C cannot see event

### **5. Performance Tests**
Test scalability and performance:

#### **Large Groups**
- Create group with 100+ members
- Send message in large group chat
- Load group timeline with 100+ posts
- Measure query performance

#### **N+1 Query Detection**
- Check all group listing queries
- Check group timeline queries
- Check member list queries
- Ensure eager loading is used

### **6. Security Tests**
Test authorization and privacy:

#### **Privacy Enforcement**
- Non-members cannot view private group content
- Non-members cannot join private groups without approval
- Non-admins cannot remove members
- Non-admins cannot delete groups

#### **Authorization Checks**
- Policies are enforced on all actions
- Unauthorized actions return 403
- Unauthenticated actions redirect to login

---

## 📐 Test Specifications

### **GroupServiceTest**

**File**: `tests/Feature/GroupServiceTest.php`

**Test Cases**:
```php
test('can create public group')
test('can create private group')
test('can update group settings')
test('can delete group')
test('can add member to group')
test('can remove member from group')
test('cannot remove last admin')
test('can search public groups by name')
test('can filter groups by tags')
test('can sync group tags')
test('can create join request')
test('can approve join request')
test('can deny join request')
test('member count updates correctly')
```

---

### **GroupPolicyTest**

**File**: `tests/Feature/GroupPolicyTest.php`

**Test Cases**:
```php
test('anyone can view public groups')
test('only members can view private groups')
test('authenticated users can create groups')
test('only admins can update groups')
test('only admins can delete groups')
test('non-members can join public groups')
test('members can leave groups')
test('last admin cannot leave group')
test('only admins can remove members')
test('only admins can approve join requests')
```

---

### **GroupContentTest**

**File**: `tests/Feature/GroupContentTest.php`

**Test Cases**:
```php
test('members can create posts in group')
test('non-members cannot create posts in group')
test('members can view group posts')
test('non-members cannot view group posts')
test('members can create events in group')
test('non-members cannot create events in group')
test('group timeline shows posts and events')
test('group timeline is sorted by date')
test('group posts do not appear in main feed')
test('group events do not appear in main discovery')
```

---

### **GroupChatTest**

**File**: `tests/Feature/GroupChatTest.php`

**Test Cases**:
```php
test('group chat is created with group')
test('members can send messages in group chat')
test('non-members cannot send messages in group chat')
test('new members are added to group chat')
test('removed members are removed from group chat')
test('group chat messages broadcast to all members')
```

---

### **GroupIntegrationTest**

**File**: `tests/Feature/GroupIntegrationTest.php`

**Test Cases**:
```php
test('complete group creation workflow')
test('complete join request workflow')
test('complete group content workflow')
test('complete group chat workflow')
test('notifications are sent for group events')
test('real-time updates work for group actions')
```

---

## 📦 Deliverables

### **Phase 1: Test Plan** (Submit for Review)
Create a document: `TESTING_PROPOSAL.md` with:
1. Complete list of test cases (organized by category)
2. Test data requirements (factories, seeders)
3. Performance benchmarks (acceptable query times)
4. Security test scenarios
5. Integration test scenarios
6. Any gaps in test coverage

**Submit this for approval before proceeding to Phase 2**

### **Phase 2: Implementation** (After Approval)
1. Test files:
   - `tests/Feature/GroupServiceTest.php`
   - `tests/Feature/GroupPolicyTest.php`
   - `tests/Feature/GroupContentTest.php`
   - `tests/Feature/GroupChatTest.php`
   - `tests/Feature/GroupIntegrationTest.php`
   - `tests/Feature/GroupPerformanceTest.php`
2. Run all tests and ensure they pass
3. Generate code coverage report
4. Document any bugs found
5. Work with Teams 2 & 3 to fix bugs
6. Re-run tests until all pass

---

## ⚠️ Critical Rules

1. **Test Everything** - Every feature must have test coverage
2. **Use Factories** - Use model factories for test data
3. **Clean State** - Each test should start with a clean database
4. **Descriptive Names** - Test names should describe what they test
5. **Assertions** - Use specific assertions (assertEquals, assertTrue, etc.)
6. **Edge Cases** - Test boundary conditions and error cases
7. **Performance** - Flag slow queries (>100ms)

---

## 🧪 Testing Checklist

Before submitting Phase 2:
- [ ] All unit tests pass
- [ ] All feature tests pass
- [ ] All integration tests pass
- [ ] All end-to-end tests pass
- [ ] Code coverage > 80%
- [ ] No N+1 query issues
- [ ] No slow queries (>100ms)
- [ ] All security tests pass
- [ ] All bugs documented and fixed

---

## 📞 Communication

**Report to**: Architect Agent (me)
**Coordinate with**: Teams 2 & 3 for bug fixes
**Blockers**: Report immediately if tests reveal design flaws
**Questions**: Ask before making assumptions

**When complete**: Submit `TESTING_PROPOSAL.md` for review, then implement tests and report results.

---

**Team Lead**: QA Engineer Agent
**Dependencies**: Teams 2 & 3 must complete first
**Priority**: P1 (High)
**Estimated Time**: 6-8 hours
**Status**: WAITING FOR TEAMS 2 & 3

