# F05 Private Direct Messaging - Parallel Implementation Strategy

## Overview

This feature can be efficiently implemented by two agents working in parallel. The work is divided into **Backend/Business Logic** (Agent A) and **Frontend/UI** (Agent B), with clear integration points.

---

## Agent A: Backend & Business Logic

**Focus**: Database, services, routing logic, notifications, authorization
**Estimated Time**: 19-25 hours
**Priority**: Must complete T01-T03 before Agent B can fully implement UI

### Task Assignment

#### T01: Extend ChatService for DM Routing Logic (4-5 hours)
**Deliverables**:
- [ ] `createDirectMessageConversation()` method
- [ ] `isMutualFollower()` method
- [ ] `shouldRouteToRequests()` method
- [ ] Modified `sendMessage()` to fire DM events
- [ ] Feature tests for all routing scenarios

**Files to Create/Modify**:
- `app/Services/ChatService.php` (modify)
- `tests/Feature/DirectMessageRoutingTest.php` (create)

---

#### T02: Database Schema Enhancements (2-3 hours)
**Deliverables**:
- [ ] Migration for `request_status` field
- [ ] Updated `ConversationParticipant` model
- [ ] Database indexes
- [ ] Schema tests

**Files to Create/Modify**:
- `database/migrations/YYYY_MM_DD_add_request_status_to_conversation_participants_table.php` (create)
- `app/Models/ConversationParticipant.php` (modify)
- `tests/Feature/DirectMessageSchemaTest.php` (create)

---

#### T03: Message Request Management Service (3-4 hours)
**Deliverables**:
- [ ] `MessageRequestService` class
- [ ] `acceptRequest()` method
- [ ] `declineRequest()` method
- [ ] `getMessageRequests()` method
- [ ] `getDirectInbox()` method
- [ ] `getUnreadRequestCount()` method
- [ ] Unit tests

**Files to Create/Modify**:
- `app/Services/MessageRequestService.php` (create)
- `tests/Feature/MessageRequestServiceTest.php` (create)

**🔄 CHECKPOINT**: After T03, Agent B can begin full UI implementation

---

#### T06: Notification System Integration (4-5 hours)
**Deliverables**:
- [ ] `DirectMessageReceived` event
- [ ] `SendDirectMessageNotification` listener
- [ ] `MessageRequestReceived` event
- [ ] `SendMessageRequestNotification` listener
- [ ] `MessageRequestAccepted` event (optional)
- [ ] Event registration in `EventServiceProvider`
- [ ] Notification tests

**Files to Create/Modify**:
- `app/Events/DirectMessageReceived.php` (create)
- `app/Events/MessageRequestReceived.php` (create)
- `app/Listeners/SendDirectMessageNotification.php` (create)
- `app/Listeners/SendMessageRequestNotification.php` (create)
- `app/Providers/EventServiceProvider.php` (modify)
- `tests/Feature/DirectMessageNotificationTest.php` (create)

---

#### T08: Policies and Authorization (3-4 hours)
**Deliverables**:
- [ ] `ConversationPolicy` with DM rules
- [ ] `view()` method
- [ ] `sendMessage()` method
- [ ] `acceptRequest()` method
- [ ] `declineRequest()` method
- [ ] Policy registration
- [ ] Authorization tests

**Files to Create/Modify**:
- `app/Policies/ConversationPolicy.php` (create)
- `app/Providers/AuthServiceProvider.php` (modify)
- `tests/Feature/DirectMessageAuthorizationTest.php` (create)

---

### Agent A Summary

**Total Estimated Time**: 19-25 hours

**Critical Path**:
1. T01 → T02 → T03 (must be sequential, 9-12 hours)
2. T06 and T08 can be done in parallel after T03 (7-9 hours)

**Key Integration Points**:
- After T03: Provide Agent B with service methods for UI integration
- Coordinate on route names for notifications (e.g., `messages.show`, `messages.requests`)
- Ensure policy methods match Agent B's authorization checks

---

## Agent B: Frontend & UI

**Focus**: Livewire components, pages, profile integration, UI polish
**Estimated Time**: 19-23 hours
**Dependencies**: Can start UI mockups immediately, but needs T01-T03 for full implementation

### Task Assignment

#### T04: DM Inbox Livewire Components (5-6 hours)
**Deliverables**:
- [ ] `InboxList` Livewire component
- [ ] `RequestsList` Livewire component
- [ ] Blade views with galaxy theme
- [ ] Real-time updates via Echo
- [ ] Feature tests

**Files to Create/Modify**:
- `app/Livewire/DirectMessages/InboxList.php` (create)
- `app/Livewire/DirectMessages/RequestsList.php` (create)
- `resources/views/livewire/direct-messages/inbox-list.blade.php` (create)
- `resources/views/livewire/direct-messages/requests-list.blade.php` (create)
- `tests/Feature/DirectMessageInboxTest.php` (create)

**Dependencies**: Requires T03 (`MessageRequestService`)

---

#### T05: Profile "Message" Button Integration (3-4 hours)
**Deliverables**:
- [ ] "Message" button on profile page
- [ ] `startConversation()` method in ShowProfile component
- [ ] Conversation creation/navigation logic
- [ ] Galaxy-themed button styling
- [ ] Tests

**Files to Create/Modify**:
- `resources/views/livewire/profile/show-profile.blade.php` (modify)
- `app/Livewire/Profile/ShowProfile.php` (modify)
- `tests/Feature/ProfileMessageButtonTest.php` (create)

**Dependencies**: Requires T01 (`ChatService::createDirectMessageConversation()`)

---

#### T07: Routes and Pages (3-4 hours)
**Deliverables**:
- [ ] Routes for DM pages
- [ ] `MessagesPage` Livewire component
- [ ] Tabbed interface (Inbox/Requests)
- [ ] Sidebar + ChatComponent layout
- [ ] Galaxy-themed UI
- [ ] Mobile-responsive design

**Files to Create/Modify**:
- `routes/web.php` (modify)
- `app/Livewire/DirectMessages/MessagesPage.php` (create)
- `resources/views/livewire/direct-messages/messages-page.blade.php` (create)
- `tests/Feature/DirectMessagePagesTest.php` (create)

**Dependencies**: Requires T04 (InboxList, RequestsList components)

---

#### T09: UI Polishing and E2E Tests (4-5 hours)
**Deliverables**:
- [ ] Loading skeletons
- [ ] Empty states with illustrations
- [ ] Error handling and toast notifications
- [ ] Unread badges
- [ ] E2E tests for complete user flows
- [ ] Galaxy theme consistency check

**Files to Create/Modify**:
- All UI files (polish)
- `tests/Feature/DirectMessagingE2ETest.php` (create)

**Dependencies**: Requires T04, T05, T07 completed

---

### Agent B Summary

**Total Estimated Time**: 19-23 hours

**Critical Path**:
1. Wait for Agent A to complete T01-T03 (9-12 hours)
2. T04 → T05 → T07 → T09 (sequential, 15-19 hours)

**Parallel Work Opportunities**:
- While waiting for T01-T03: Design UI mockups, create Blade templates with placeholder data
- Can work on galaxy theme styling independently
- Can create component structure before wiring up services

**Key Integration Points**:
- Use `ChatService::createDirectMessageConversation()` from T01
- Use `MessageRequestService` methods from T03
- Apply `ConversationPolicy` checks from T08
- Coordinate route names with Agent A for notifications

---

## Integration Timeline

### Phase 1: Foundation (Agent A Only)
**Duration**: 9-12 hours
**Tasks**: T01, T02, T03

**Agent B During Phase 1**:
- Design UI mockups
- Create Blade template structure
- Set up component skeletons
- Review galaxy theme standards

---

### Phase 2: Parallel Development
**Duration**: 10-13 hours
**Agent A**: T06, T08 (can work in parallel)
**Agent B**: T04, T05 (can work in parallel)

**Integration Points**:
- Agent B uses services from Phase 1
- Agent A coordinates route names for notifications
- Daily sync on progress

---

### Phase 3: Final Integration
**Duration**: 4-5 hours
**Agent A**: Support Agent B with any backend issues
**Agent B**: T07, T09 (pages and polish)

**Integration Activities**:
- E2E testing together
- Bug fixes
- Performance optimization
- Documentation updates

---

## Communication Protocol

### Daily Sync (15 minutes)
- Progress update
- Blockers discussion
- Integration point coordination

### Shared Documentation
- Update task checklist in README.md
- Document any API changes
- Share test results

### Code Review
- Agent A reviews Agent B's service usage
- Agent B reviews Agent A's event payloads
- Cross-review authorization logic

---

## Risk Mitigation

### Potential Blockers

1. **Agent B blocked on T01-T03**: Mitigated by UI mockup work
2. **Route name mismatches**: Mitigated by early coordination
3. **Policy authorization failures**: Mitigated by clear policy documentation
4. **Real-time updates not working**: Mitigated by testing Echo setup early

### Contingency Plans

- If Agent A falls behind: Agent B focuses on static UI and tests
- If Agent B falls behind: Agent A can assist with Blade templates
- If integration issues: Schedule pair programming session

---

## Success Metrics

### Agent A Completion Criteria
- [ ] All service methods tested and working
- [ ] Database migrations run successfully
- [ ] Notifications firing correctly
- [ ] Policies enforcing authorization
- [ ] All backend tests passing

### Agent B Completion Criteria
- [ ] All UI components rendering correctly
- [ ] Galaxy theme applied consistently
- [ ] Real-time updates working
- [ ] Mobile-responsive design
- [ ] All frontend tests passing

### Joint Completion Criteria
- [ ] E2E tests passing for all user flows
- [ ] No console errors
- [ ] Performance benchmarks met
- [ ] Documentation complete
- [ ] Code formatted with Pint

---

## Estimated Timeline

**Sequential Implementation**: 38-48 hours (one agent)
**Parallel Implementation**: 19-25 hours (two agents)

**Time Savings**: ~50% reduction in total calendar time

**Recommended Schedule**:
- Week 1: Agent A completes T01-T03, Agent B prepares UI
- Week 2: Both agents work in parallel on remaining tasks
- Week 3: Integration, testing, and polish

---

This parallel implementation strategy maximizes efficiency while maintaining code quality and clear integration points between backend and frontend work.

