# Groups Feature - Architecture Overview

## 🏗️ System Design

### **Core Principle**
Extend existing FunLynk infrastructure (posts, activities, conversations) with group context rather than building parallel systems. This ensures consistency, reduces code duplication, and speeds up MVP delivery.

---

## 📊 Database Architecture

### **New Tables**

#### `groups`
```sql
- id (uuid, primary key)
- name (string, required, max 100 chars)
- slug (string, unique, for URLs)
- description (text, nullable)
- avatar_url (string, nullable)
- cover_image_url (string, nullable)
- privacy (enum: 'public', 'private')
- auto_approve_members (boolean, default false) -- for public groups
- member_count (integer, default 0)
- created_by (uuid, foreign key to users)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable) -- soft deletes
```

#### `group_members`
```sql
- id (uuid, primary key)
- group_id (uuid, foreign key to groups)
- user_id (uuid, foreign key to users)
- role (enum: 'admin', 'member')
- joined_at (timestamp)
- created_at (timestamp)
- updated_at (timestamp)

UNIQUE INDEX on (group_id, user_id)
INDEX on user_id for "my groups" queries
```

#### `group_join_requests`
```sql
- id (uuid, primary key)
- group_id (uuid, foreign key to groups)
- user_id (uuid, foreign key to users)
- status (enum: 'pending', 'approved', 'denied')
- requested_at (timestamp)
- responded_at (timestamp, nullable)
- responded_by (uuid, foreign key to users, nullable)
- created_at (timestamp)
- updated_at (timestamp)

UNIQUE INDEX on (group_id, user_id, status) WHERE status = 'pending'
INDEX on group_id for admin approval queries
```

#### `group_tag` (Pivot Table)
```sql
- group_id (uuid, foreign key to groups)
- tag_id (uuid, foreign key to tags)
- created_at (timestamp)

PRIMARY KEY (group_id, tag_id)
INDEX on tag_id for "groups by tag" queries
```

**Note**: Reuses existing `tags` table from E01 Core Infrastructure. No modifications to `tags` table needed.

### **Modified Tables**

#### `posts`
```sql
ADD COLUMN group_id (uuid, nullable, foreign key to groups)
ADD INDEX on group_id for group timeline queries
```

#### `activities`
```sql
ADD COLUMN group_id (uuid, nullable, foreign key to groups)
ADD INDEX on group_id for group timeline queries
```

#### `conversations`
```sql
ADD COLUMN group_id (uuid, nullable, foreign key to groups)
MODIFY type enum to include 'group' (existing: 'private', 'activity', 'post')
ADD INDEX on group_id
```

---

## 🔗 Relationships

### **Group Model**
```php
- belongsTo: User (creator)
- hasMany: GroupMember
- hasMany: GroupJoinRequest
- hasMany: Post (group posts)
- hasMany: Activity (group events)
- hasOne: Conversation (group chat)
- belongsToMany: Tag (through group_tag pivot)
```

### **User Model**
```php
- belongsToMany: Group (through group_members)
- hasMany: Group (created groups)
- hasMany: GroupJoinRequest
```

### **Tag Model**
```php
- belongsToMany: Group (through group_tag pivot)
```

### **Post Model**
```php
- belongsTo: Group (nullable)
```

### **Activity Model**
```php
- belongsTo: Group (nullable)
```

### **Conversation Model**
```php
- belongsTo: Group (nullable, for group chats)
```

---

## 🔐 Authorization & Permissions

### **Policy Structure**

#### `GroupPolicy`
- `viewAny()` - Anyone can search public groups
- `view()` - Members can view group details, non-members can view public group details
- `create()` - Authenticated users can create groups
- `update()` - Only admins can update group settings
- `delete()` - Only admins can delete groups
- `join()` - Anyone can request to join
- `leave()` - Members can leave (except last admin)
- `invite()` - Members can invite others
- `removeMember()` - Only admins can remove members
- `approveRequest()` - Only admins can approve join requests

#### `GroupPostPolicy` (extends PostPolicy)
- `create()` - Only group members can create posts in group
- `view()` - Only group members can view group posts
- `update()` - Post author or group admin
- `delete()` - Post author or group admin

#### `GroupActivityPolicy` (extends ActivityPolicy)
- `create()` - Only group members can create events in group
- `view()` - Only group members can view group events
- `update()` - Event creator or group admin
- `delete()` - Event creator or group admin

---

## 🛠️ Service Layer

### **GroupService**
```php
- createGroup(array $data): Group
- updateGroup(Group $group, array $data): Group
- deleteGroup(Group $group): bool
- addMember(Group $group, User $user, string $role = 'member'): GroupMember
- removeMember(Group $group, User $user): bool
- updateMemberRole(Group $group, User $user, string $role): GroupMember
- searchPublicGroups(string $query, ?array $tagIds = null): Collection
- getGroupsByTag(Tag $tag): Collection
- getUserGroups(User $user): Collection
- getGroupMembers(Group $group): Collection
- syncGroupTags(Group $group, array $tagIds): void
- createJoinRequest(Group $group, User $user): GroupJoinRequest
- approveJoinRequest(GroupJoinRequest $request, User $admin): bool
- denyJoinRequest(GroupJoinRequest $request, User $admin): bool
```

### **GroupContentService**
```php
- createGroupPost(Group $group, array $data): Post
- createGroupEvent(Group $group, array $data): Activity
- getGroupTimeline(Group $group, int $page = 1): Collection
- getGroupPosts(Group $group): Collection
- getGroupEvents(Group $group): Collection
```

### **GroupChatService** (extends ChatService)
```php
- getOrCreateGroupChat(Group $group): Conversation
- sendGroupMessage(Group $group, User $user, string $message): Message
- getGroupChatMessages(Group $group, int $limit = 50): Collection
```

---

## 🎨 Frontend Components

### **Livewire Components**

#### `Groups/GroupsList`
- Display user's groups
- Search public groups
- Join/leave buttons

#### `Groups/GroupDetail`
- Group header (avatar, name, description, stats)
- Tabs: Timeline, Members, Chat
- Join/leave/settings buttons

#### `Groups/GroupTimeline`
- Display group posts and events
- Create post/event buttons
- Infinite scroll

#### `Groups/GroupMembers`
- List all members with role badges
- Remove member button (admins only)
- Pending requests section (admins only)

#### `Groups/GroupChat`
- Reuse existing `ChatComponent`
- Pass group conversation ID
- Real-time updates via Reverb

#### `Groups/CreateGroupForm`
- Name, description, avatar upload
- Privacy toggle
- Submit button

#### `Groups/GroupSettings` (admins only)
- Edit group details
- Delete group button
- Member management

#### `Dashboard/UserDashboard`
- Replace profile hero for authenticated user
- Cards: Groups, Notifications, Interested Posts, Upcoming Events

---

## 🔔 Events & Notifications

### **New Events**

#### `GroupCreated`
- Broadcasts to creator's channel
- Notification: "You created [Group Name]"

#### `GroupMemberJoined`
- Broadcasts to group members' channels
- Notification: "[User] joined [Group Name]"

#### `GroupPostCreated`
- Broadcasts to group members' channels
- Notification: "[User] posted in [Group Name]"

#### `GroupEventCreated`
- Broadcasts to group members' channels
- Notification: "[User] created an event in [Group Name]"

#### `GroupJoinRequestReceived`
- Broadcasts to group admins' channels
- Notification: "[User] wants to join [Group Name]"

#### `GroupJoinRequestApproved`
- Broadcasts to requester's channel
- Notification: "You were accepted to [Group Name]"

#### `GroupMemberRemoved`
- Broadcasts to removed user's channel
- Notification: "You were removed from [Group Name]"

### **Reused Events**
- `MessageSent` - For group chat messages (existing)
- `DirectMessageReceived` - Adapt for group messages

---

## 🔄 Integration Points

### **With Existing Features**

#### **Posts**
- Add `group_id` to post creation form (optional dropdown)
- Filter posts by `group_id IS NULL` for main feed
- Filter posts by `group_id = X` for group timeline

#### **Activities (Events)**
- Add `group_id` to event creation form (optional dropdown)
- Filter events by `group_id IS NULL` for main discovery
- Filter events by `group_id = X` for group timeline

#### **Conversations (Chat)**
- Create conversation with `type = 'group'` and `group_id`
- Reuse existing `ChatComponent` with group context
- Group chat participants = all group members

#### **Notifications**
- Extend existing notification system
- Add group-specific notification types
- Real-time updates via Reverb

#### **Dashboard**
- New route: `/dashboard` (authenticated user's profile)
- Existing route: `/profile/{username}` (public profile view)
- Conditional rendering based on `auth()->id() === $user->id`

---

## 🚀 API Contracts (for Frontend)

### **Group Endpoints**
```
GET    /groups                    - List user's groups + search public groups
POST   /groups                    - Create new group
GET    /groups/{slug}             - View group details
PUT    /groups/{slug}             - Update group (admin only)
DELETE /groups/{slug}             - Delete group (admin only)
GET    /groups/{slug}/members     - List group members
POST   /groups/{slug}/join        - Request to join group
DELETE /groups/{slug}/leave       - Leave group
POST   /groups/{slug}/invite      - Invite user to group
DELETE /groups/{slug}/members/{user} - Remove member (admin only)
GET    /groups/{slug}/timeline    - Get group posts + events
POST   /groups/{slug}/posts       - Create post in group
POST   /groups/{slug}/events      - Create event in group
GET    /groups/{slug}/chat        - Get group chat conversation
```

---

**Document Owner**: Architect Agent
**Last Updated**: 2025-12-01
**Status**: APPROVED - Ready for team assignment

