# Groups Feature: Testing Proposal

## 1. Complete List of Test Cases

### 1.1. Unit Tests

#### Model Tests
- Group model relationships
- GroupMember model relationships
- GroupJoinRequest model relationships
- Model scopes and accessors

#### Service Tests
- `GroupService` methods:
    - `test('can create public group')`
    - `test('can create private group')`
    - `test('can update group settings')`
    - `test('can delete group')`
    - `test('can add member to group')`
    - `test('can remove member from group')`
    - `test('cannot remove last admin')`
    - `test('can search public groups by name')`
    - `test('can filter groups by tags')`
    - `test('can sync group tags')`
    - `test('can create join request')`
    - `test('can approve join request')`
    - `test('can deny join request')`
    - `test('member count updates correctly')`
- `GroupContentService` methods
- `GroupChatService` methods
- Edge cases and error handling

#### Policy Tests
- `GroupPolicy` authorization rules:
    - `test('anyone can view public groups')`
    - `test('only members can view private groups')`
    - `test('authenticated users can create groups')`
    - `test('only admins can update groups')`
    - `test('only admins can delete groups')`
    - `test('non-members can join public groups')`
    - `test('members can leave groups')`
    - `test('last admin cannot leave group')`
    - `test('only admins can remove members')`
    - `test('only admins can approve join requests')`
- `GroupPostPolicy` authorization rules
- `GroupActivityPolicy` authorization rules
- Permission edge cases

### 1.2. Feature Tests

#### Group Management
- Create public group
- Create private group
- Update group settings
- Delete group
- Add/remove members
- Approve/deny join requests

#### Group Content
- `GroupContentTest` test cases:
    - `test('members can create posts in group')`
    - `test('non-members cannot create posts in group')`
    - `test('members can view group posts')`
    - `test('non-members cannot view group posts')`
    - `test('members can create events in group')`
    - `test('non-members cannot create events in group')`
    - `test('group timeline shows posts and events')`
    - `test('group timeline is sorted by date')`
    - `test('group posts do not appear in main feed')`
    - `test('group events do not appear in main discovery')`

#### Group Chat
- `GroupChatTest` test cases:
    - `test('group chat is created with group')`
    - `test('members can send messages in group chat')`
    - `test('non-members cannot send messages in group chat')`
    - `test('new members are added to group chat')`
    - `test('removed members are removed from group chat')`
    - `test('group chat messages broadcast to all members')`

#### Group Discovery
- Search public groups
- Filter groups by tags
- Browse groups by category
- Join/leave groups

### 1.3. Integration Tests

#### Post-to-Group Integration
- Create post with group context
- Post only visible to group members
- Non-members cannot view group posts

#### Event-to-Group Integration
- Create event with group context
- Event only visible to group members
- Non-members cannot view group events

#### Chat-to-Group Integration
- Group chat created when group is created
- New members added to group chat
- Removed members removed from group chat

#### Notification Integration
- `GroupIntegrationTest` test cases:
    - `test('complete group creation workflow')`
    - `test('complete join request workflow')`
    - `test('complete group content workflow')`
    - `test('complete group chat workflow')`
    - `test('notifications are sent for group events')`
    - `test('real-time updates work for group actions')`

### 1.4. End-to-End Tests

#### Journey 1: Create and Join Group
1. User A creates public group with tags
2. User B searches for group by tag
3. User B joins group
4. User B sees group in "My Groups"
5. User B can access group chat

#### Journey 2: Private Group Workflow
1. User A creates private group
2. User B requests to join
3. User A (admin) receives notification
4. User A approves request
5. User B receives approval notification
6. User B can now access group

#### Journey 3: Group Content Workflow
1. User A creates post in group
2. User B (member) sees post in group timeline
3. User C (non-member) cannot see post
4. User A creates event in group
5. User B can RSVP to event
6. User C cannot see event

## 2. Test Data Requirements

- **Factories**: Use model factories for `User`, `Group`, `GroupMember`, `GroupJoinRequest`, `Post`, `Activity`, `Tag`, and `Notification` models to generate test data.
- **Seeders**: Utilize seeders for initial setup of common data (e.g., categories, default tags) if necessary, but primarily rely on factories for dynamic test data generation within each test.

## 3. Performance Benchmarks

- **Acceptable Query Times**: Flag any queries exceeding 100ms.
- **N+1 Query Detection**: Implement checks for N+1 query issues in:
    - All group listing queries
    - Group timeline queries
    - Member list queries
- **Large Groups**: Test scenarios with groups containing 100+ members and 100+ posts/events to measure performance under load.

## 4. Security Test Scenarios

- **Privacy Enforcement**:
    - Verify non-members cannot view private group content.
    - Verify non-members cannot join private groups without approval.
    - Verify non-admins cannot remove members.
    - Verify non-admins cannot delete groups.
- **Authorization Checks**:
    - Ensure policies are enforced on all actions (create, update, delete, join, leave, post, event, chat).
    - Verify unauthorized actions return a 403 HTTP status code.
    - Verify unauthenticated actions redirect to the login page.

## 5. Integration Test Scenarios

- **Group Creation to Chat**: Ensure a group chat is automatically created when a new group is successfully created.
- **Member Management to Chat**: Verify new members are automatically added to the group chat and removed members are removed from the group chat.
- **Post/Event Visibility**: Confirm posts and events created within a group are only visible to group members and not in main feeds/discovery for non-members.
- **Notification Triggers**: Validate that group-related events (e.g., new join request, approval, new post/event) correctly trigger notifications to the relevant users.
- **Real-time Updates**: Confirm real-time broadcasting works for group chat messages and other dynamic group actions.

## 6. Gaps in Test Coverage

- **UI/Frontend Testing**: This proposal primarily focuses on backend (unit, feature, integration, E2E via API) testing. Dedicated frontend tests (e.g., using Cypress or Playwright for Livewire components) are not explicitly covered here but would be a valuable addition for complete coverage.
- **Accessibility Testing**: No specific accessibility tests are included in this proposal.
- **Cross-Browser/Device Compatibility**: Not covered in this backend-focused testing plan.
- **Load Testing**: While performance benchmarks are included, full-scale load testing beyond large group scenarios is not detailed.
- **Error Logging and Monitoring**: While error handling is mentioned for services, explicit tests for ensuring proper error logging and monitoring integration are not detailed.

