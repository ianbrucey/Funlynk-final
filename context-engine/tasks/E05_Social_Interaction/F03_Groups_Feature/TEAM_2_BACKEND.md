# Team 2: Backend Services & API

## 🎯 Your Mission

Build the backend business logic, services, policies, and API contracts for the Groups feature. You will work in parallel with Team 3 (Frontend) once Team 1 completes the database schema.

---

## 📋 Required Reading

**MUST READ FIRST**:
1. `context-engine/tasks/E05_Social_Interaction/F03_Groups_Feature/REQUIREMENTS.md`
2. `context-engine/tasks/E05_Social_Interaction/F03_Groups_Feature/ARCHITECTURE.md`
3. Team 1's `SCHEMA_PROPOSAL.md` (once approved)
4. `context-engine/domain-contexts/auth-context.md` (Laravel Auth patterns)
5. Existing service examples:
   - `app/Services/ChatService.php`
   - `app/Services/MessageRequestService.php`

---

## 🎯 Your Responsibilities

### **1. Service Classes**
Build service classes that encapsulate all business logic:

#### **GroupService**
- Group CRUD operations
- Membership management
- Join request handling
- Search and filtering (including by tags)
- Tag management

#### **GroupContentService**
- Create posts within groups
- Create events within groups
- Fetch group timeline (posts + events)
- Filter and pagination

#### **GroupChatService** (extends ChatService)
- Get or create group chat conversation
- Send messages to group chat
- Fetch group chat messages
- Handle group chat permissions

### **2. Authorization Policies**
Create Laravel policies for fine-grained permissions:

#### **GroupPolicy**
- Who can view groups
- Who can create groups
- Who can update/delete groups
- Who can join/leave groups
- Who can invite members
- Who can remove members
- Who can approve join requests

#### **GroupPostPolicy** (extends PostPolicy)
- Who can create posts in groups
- Who can view group posts
- Who can update/delete group posts

#### **GroupActivityPolicy** (extends ActivityPolicy)
- Who can create events in groups
- Who can view group events
- Who can update/delete group events

### **3. Events & Listeners**
Create events for real-time updates and notifications:

#### **Events**
- `GroupCreated`
- `GroupMemberJoined`
- `GroupMemberRemoved`
- `GroupPostCreated`
- `GroupEventCreated`
- `GroupJoinRequestReceived`
- `GroupJoinRequestApproved`

#### **Listeners**
- `SendGroupNotification` (generic listener for all group events)
- `BroadcastGroupUpdate` (Reverb broadcasting)
- `UpdateGroupMemberCount` (increment/decrement)

### **4. API Contracts**
Document all API endpoints for Team 3 (Frontend) to consume.

### **5. Validation**
Create Form Request classes for input validation:
- `CreateGroupRequest`
- `UpdateGroupRequest`
- `JoinGroupRequest`
- `CreateGroupPostRequest`
- `CreateGroupEventRequest`

---

## 📐 Service Specifications

### **GroupService**

**File**: `app/Services/GroupService.php`

**Methods**:
```php
public function createGroup(User $user, array $data): Group
public function updateGroup(Group $group, array $data): Group
public function deleteGroup(Group $group): bool
public function addMember(Group $group, User $user, string $role = 'member'): GroupMember
public function removeMember(Group $group, User $user): bool
public function updateMemberRole(Group $group, User $user, string $role): GroupMember
public function searchPublicGroups(string $query, ?array $tagIds = null): Collection
public function getGroupsByTag(Tag $tag): Collection
public function getUserGroups(User $user): Collection
public function getGroupMembers(Group $group): Collection
public function syncGroupTags(Group $group, array $tagIds): void
public function createJoinRequest(Group $group, User $user): GroupJoinRequest
public function approveJoinRequest(GroupJoinRequest $request, User $admin): bool
public function denyJoinRequest(GroupJoinRequest $request, User $admin): bool
public function isGroupMember(Group $group, User $user): bool
public function isGroupAdmin(Group $group, User $user): bool
```

**Key Logic**:
- **createGroup**: Create group, add creator as admin, sync tags, fire `GroupCreated` event
- **addMember**: Add member, increment member_count, fire `GroupMemberJoined` event
- **removeMember**: Remove member, decrement member_count, fire `GroupMemberRemoved` event, prevent removing last admin
- **searchPublicGroups**: Search by name/description, optionally filter by tags, only return public groups
- **approveJoinRequest**: Update request status, add user as member, fire `GroupJoinRequestApproved` event

---

### **GroupContentService**

**File**: `app/Services/GroupContentService.php`

**Methods**:
```php
public function createGroupPost(Group $group, User $user, array $data): Post
public function createGroupEvent(Group $group, User $user, array $data): Activity
public function getGroupTimeline(Group $group, int $page = 1, int $perPage = 20): Collection
public function getGroupPosts(Group $group): Collection
public function getGroupEvents(Group $group): Collection
```

**Key Logic**:
- **createGroupPost**: Set `group_id` on post, verify user is member, fire `GroupPostCreated` event
- **createGroupEvent**: Set `group_id` on activity, verify user is member, fire `GroupEventCreated` event
- **getGroupTimeline**: Merge posts and events, sort by created_at DESC, paginate

---

### **GroupChatService**

**File**: `app/Services/GroupChatService.php`

**Methods**:
```php
public function getOrCreateGroupChat(Group $group): Conversation
public function sendGroupMessage(Group $group, User $user, string $message): Message
public function getGroupChatMessages(Group $group, int $limit = 50): Collection
public function addParticipantToGroupChat(Group $group, User $user): void
public function removeParticipantFromGroupChat(Group $group, User $user): void
```

**Key Logic**:
- **getOrCreateGroupChat**: Find or create conversation with `type = 'group'` and `group_id`
- **sendGroupMessage**: Verify user is group member, send message, broadcast to all group members
- **addParticipantToGroupChat**: Add user to conversation participants when they join group
- **removeParticipantFromGroupChat**: Remove user from conversation when they leave group

---

## 🔐 Policy Specifications

### **GroupPolicy**

**File**: `app/Policies/GroupPolicy.php`

**Methods**:
```php
public function viewAny(User $user): bool // Anyone can search
public function view(?User $user, Group $group): bool // Members can view private, anyone can view public
public function create(User $user): bool // Authenticated users
public function update(User $user, Group $group): bool // Admins only
public function delete(User $user, Group $group): bool // Admins only
public function join(User $user, Group $group): bool // Non-members
public function leave(User $user, Group $group): bool // Members (except last admin)
public function invite(User $user, Group $group): bool // Members can invite
public function removeMember(User $user, Group $group): bool // Admins only
public function approveRequest(User $user, Group $group): bool // Admins only
```

---

## 📦 Deliverables

### **Phase 1: Service Design** (Submit for Review)
Create a document: `BACKEND_PROPOSAL.md` with:
1. Service class signatures (all methods with parameters and return types)
2. Policy rules (who can do what)
3. Event/Listener mapping
4. API endpoint contracts (for Team 3)
5. Validation rules for each request
6. Any deviations from ARCHITECTURE.md (with justification)

**Submit this for approval before proceeding to Phase 2**

### **Phase 2: Implementation** (After Approval)
1. Service files:
   - `app/Services/GroupService.php`
   - `app/Services/GroupContentService.php`
   - `app/Services/GroupChatService.php`
2. Policy files:
   - `app/Policies/GroupPolicy.php`
   - Update `app/Policies/PostPolicy.php` (add group context)
   - Update `app/Policies/ActivityPolicy.php` (add group context)
3. Event files:
   - `app/Events/GroupCreated.php`
   - `app/Events/GroupMemberJoined.php`
   - `app/Events/GroupMemberRemoved.php`
   - `app/Events/GroupPostCreated.php`
   - `app/Events/GroupEventCreated.php`
   - `app/Events/GroupJoinRequestReceived.php`
   - `app/Events/GroupJoinRequestApproved.php`
4. Listener files:
   - `app/Listeners/SendGroupNotification.php`
   - `app/Listeners/BroadcastGroupUpdate.php`
   - `app/Listeners/UpdateGroupMemberCount.php`
5. Form Request files:
   - `app/Http/Requests/CreateGroupRequest.php`
   - `app/Http/Requests/UpdateGroupRequest.php`
   - `app/Http/Requests/JoinGroupRequest.php`
6. API Contract document:
   - `INTEGRATION_CONTRACTS.md` (shared with Team 3)
7. Register events in `EventServiceProvider`
8. Write Pest tests for all services and policies

---

## ⚠️ Critical Rules

1. **Authorization First** - Always check policies before performing actions
2. **Fire Events** - Every significant action should fire an event for notifications/broadcasting
3. **Transaction Safety** - Use DB transactions for multi-step operations
4. **Eager Loading** - Prevent N+1 queries with proper eager loading
5. **Validation** - Use Form Requests for all user input
6. **Error Handling** - Throw meaningful exceptions with clear messages
7. **Type Hints** - Use strict types for all method parameters and return values

---

## 🧪 Testing Checklist

Before submitting Phase 2:
- [ ] All service methods have Pest tests
- [ ] All policy methods have Pest tests
- [ ] Events are fired correctly
- [ ] Listeners handle events properly
- [ ] Validation rules work as expected
- [ ] No N+1 query issues
- [ ] Transactions rollback on errors
- [ ] API contracts documented in INTEGRATION_CONTRACTS.md

---

## 📞 Communication

**Report to**: Architect Agent (me)
**Coordinate with**: Team 3 (Frontend) - share API contracts
**Blockers**: Report immediately if Team 1's schema is insufficient
**Questions**: Ask before making assumptions

**When complete**: Submit `BACKEND_PROPOSAL.md` for review, then wait for approval before implementing.

---

**Team Lead**: Backend Engineer Agent
**Dependencies**: Team 1 (Database) must complete first
**Priority**: P1 (High)
**Estimated Time**: 6-8 hours
**Status**: WAITING FOR TEAM 1

