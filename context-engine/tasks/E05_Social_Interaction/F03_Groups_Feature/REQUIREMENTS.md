# Groups Feature - Requirements Document

## 📋 Problem Statement

Users need a way to organize around shared interests, activities, and communities beyond individual connections. Currently, FunLynk only supports 1-on-1 interactions (DMs, follows) and public discovery (posts, events). Groups will enable users to create focused communities where members can chat, share posts, and organize events together.

---

## 🎯 Vision & Goals

**Vision**: Enable users to build and participate in niche communities around shared interests, making it easier to organize recurring activities and maintain ongoing conversations with like-minded people.

**Goals**:
1. **MVP Speed**: Launch with core features (public/private groups, chat, posts, events)
2. **Simplicity**: Reuse existing components (chat, posts, events) with minimal modifications
3. **Scalability**: Support groups from 3 to 1000+ members
4. **Privacy**: Clear distinction between public and private groups
5. **Integration**: Seamless integration with existing FunLynk features

---

## 👥 User Stories

### Group Discovery & Joining
- As a user, I want to **search for public groups** so I can find communities that match my interests
- As a user, I want to **filter groups by interest tags** so I can find relevant communities
- As a user, I want to **browse groups by category** (e.g., Sports, Music, Food, etc.)
- As a user, I want to **create a new group** (public or private) so I can build a community
- As a user, I want to **tag my group with interests** so others can discover it
- As a user, I want to **invite friends to a private group** so we can organize together
- As a user, I want to **request to join a private group** so I can participate if approved
- As a user, I want to **leave a group** if I'm no longer interested

### Group Management (Admins)
- As a group admin, I want to **edit group details** (name, description, avatar, privacy settings)
- As a group admin, I want to **approve/deny join requests** for private groups
- As a group admin, I want to **remove members** who violate group rules
- As a group admin, I want to **delete posts/events** that are inappropriate
- As a group admin, I want to **delete the group** if it's no longer needed

### Group Content
- As a group member, I want to **post to the group timeline** so I can share updates with the community
- As a group member, I want to **create events within the group** so we can organize activities together
- As a group member, I want to **chat with all group members** in a shared group chat
- As a group member, I want to **see only group content in the group timeline** (not mixed with main feed)
- As a group member, I want to **see who else is in the group** so I know the community

### Dashboard & Navigation
- As a user, I want to **see my groups on my dashboard** so I can quickly access them
- As a user, I want to **see group activity notifications** so I stay updated
- As a profile visitor, I want to **see the user's profile hero** (existing behavior)
- As a profile owner, I want to **see a dashboard instead of hero** to reclaim screen space

---

## ✅ Feature Scope (MVP)

### **In Scope**
✅ Public and private groups
✅ Group creation (name, description, avatar, privacy)
✅ Group membership (join, leave, invite, remove)
✅ Group roles: Admin and Member only
✅ Group chat (one chat per group, reusing existing chat component)
✅ Group posts (reusing existing post creation, visible only in group timeline)
✅ Group events (reusing existing event creation, visible only in group timeline)
✅ Group timeline (separate from main feed)
✅ Group member list
✅ Group search (public groups only)
✅ Group interest tags (reuse existing tags system for categorization)
✅ Filter groups by tags/interests
✅ Join requests for private groups (admin approval required)
✅ Dashboard for profile owner (replaces hero section)
✅ Notifications for group activity
✅ Permission-based visibility (private group content hidden from non-members)

### **Out of Scope (Future)**
❌ Moderator role (only Admin/Member for MVP)
❌ Multiple chat channels per group
❌ Group recommendations algorithm based on user interests
❌ Group analytics/insights
❌ Pinned posts in groups
❌ Group rules/guidelines section
❌ Member roles beyond Admin/Member
❌ Group events appearing in main discovery feed
❌ Subgroups or nested groups

---

## 🔒 Privacy & Permissions

### **Public Groups**
- Anyone can search and find
- Anyone can view group details (name, description, member count)
- Anyone can request to join (auto-approved or admin-approved based on group settings)
- Only members can see posts, events, and chat
- Only members can post, create events, and chat

### **Private Groups**
- Not searchable (invite-only or direct link)
- Non-members cannot see group details
- Join requests require admin approval
- Only members can see posts, events, and chat
- Only members can post, create events, and chat

### **Admin Permissions**
- Edit group settings
- Approve/deny join requests
- Remove members
- Delete posts/events
- Delete group

### **Member Permissions**
- Post to group timeline
- Create events in group
- Chat in group chat
- View all group content
- Leave group

---

## 🗄️ Data Model (High-Level)

### **New Tables**
- `groups` - Group details (name, description, avatar, privacy, created_by, created_at)
- `group_members` - Membership (group_id, user_id, role, joined_at)
- `group_join_requests` - Pending requests (group_id, user_id, status, requested_at)
- `group_tag` - Pivot table (group_id, tag_id) - links groups to existing tags

### **Modified Tables**
- `posts` - Add `group_id` (nullable, foreign key to groups)
- `activities` - Add `group_id` (nullable, foreign key to groups)
- `conversations` - Add `group_id` (nullable, foreign key to groups) and update `type` enum to include 'group'

### **Reused Tables**
- `tags` - Existing tags table for categorization (no modifications needed)

---

## 🎨 UI/UX Requirements

### **Dashboard (Profile Owner View)**
- Replace hero section with dashboard cards
- Show: Groups joined, recent notifications, interested posts, upcoming events
- Profile editing accessible via settings icon or dropdown

### **Group Discovery Page**
- Search bar for finding public groups
- Tag/category filter chips (e.g., Sports, Music, Food, Tech, etc.)
- List of search results with group name, description, tags, member count, join button
- "My Groups" section showing groups user has joined
- "Browse by Interest" section with popular tags

### **Group Detail Page**
- Group header (avatar, name, description, tags, member count, join/leave button)
- Tabs: Timeline, Members, Chat
- Timeline: Posts and events created within the group
- Members: List of all group members with role badges
- Chat: Group chat interface (reusing existing chat component)

### **Group Creation Form**
- Name (required)
- Description (optional)
- Avatar (optional)
- Interest Tags (multi-select, reuse existing tags)
- Privacy: Public or Private (radio buttons)
- Submit button

### **Post/Event Creation**
- Add "Post to Group" dropdown (optional)
- If group selected, post/event only visible in that group's timeline

---

## 🔔 Notifications

- New post in group
- New event in group
- New member joined group
- Join request approved (for requester)
- Invited to group
- Removed from group
- Group chat messages (reuse existing DM notification logic)

---

## ✅ Success Criteria

1. Users can create public and private groups
2. Users can search and join public groups
3. Users can invite others to private groups
4. Group members can post, create events, and chat within the group
5. Group content is isolated from main feed
6. Admins can manage group membership and content
7. Dashboard replaces hero section for profile owners
8. All features follow galaxy theme design standards
9. Real-time updates for group chat (via Reverb)
10. All features have Pest test coverage

---

## 📊 Technical Constraints

- **Laravel 12** - Use latest syntax and features
- **Livewire v3** - All UI components
- **Filament v4** - Admin panel for group management
- **PostgreSQL + PostGIS** - Database with spatial support
- **Laravel Reverb** - Real-time updates for group chat
- **DaisyUI** - UI components
- **Galaxy Theme** - All pages must follow ui-design-standards.md
- **Pest v4** - All tests

---

## 🚀 Implementation Phases

### **Phase 1: Foundation (Team 1 - Database)**
- Design schema
- Create migrations
- Define relationships
- **Blocking**: Other teams wait for schema approval

### **Phase 2: Parallel Development**
- **Team 2 (Backend)**: Services, policies, API contracts
- **Team 3 (Frontend)**: Livewire components, Blade views

### **Phase 3: Integration & Testing (Team 4)**
- Write tests
- Integration testing
- Bug fixes
- Documentation

---

**Document Owner**: Architect Agent
**Last Updated**: 2025-12-01
**Status**: APPROVED - Ready for team assignment

