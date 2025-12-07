# Post-to-Event Conversion - Implementation Tasks

> **Epic**: E03 (Activity Management) + E04 (Discovery Engine)  
> **Status**: Ready for Implementation  
> **Estimated Timeline**: 4-5 weeks  
> **Agents**: 2 (parallel work)

---

## Overview

This task implements the complete Post-to-Event conversion workflow, allowing spontaneous posts to evolve into structured events based on user engagement. The workflow includes:

1. **Interest Tracking**: Users can view posts they've reacted to
2. **Conversion Prompts**: Post owners are notified when posts reach engagement thresholds
3. **Conversion Flow**: Streamlined process to convert posts to events
4. **User Notifications**: Interested users are invited to the new event
5. **Post Archival**: Converted posts are archived with links to events

---

## Architecture Decisions

### Key Design Choices (from design doc review)

1. **Two-Tier Threshold System**: 5 reactions (soft prompt) → 10 reactions (strong prompt)
2. **Invitation Over Auto-RSVP**: Users must actively choose to attend the event
3. **Hybrid Conversion Flow**: Pre-filled form with required event fields
4. **Post Archival**: Posts marked as "converted" with badge and event link
5. **Privacy-First**: Interested users shown as count only (not names/avatars)
6. **Transaction Safety**: All conversion operations wrapped in database transactions
7. **Idempotency**: Prevent duplicate prompts via timestamp checks

### Integration Points

- **E01**: Notifications system for conversion prompts and invitations
- **E02**: User profiles for "Interested" tab
- **E03**: Activity creation from post data
- **E04**: Discovery feed filtering (exclude converted posts)
- **E05**: Social interactions (reactions, invitations)

---

## Database Schema Changes

### Migration 1: Posts Table Enhancements
```sql
ALTER TABLE posts 
ADD COLUMN conversion_prompted_at TIMESTAMP NULL,
ADD COLUMN conversion_dismissed_at TIMESTAMP NULL,
ADD COLUMN conversion_dismiss_count INTEGER DEFAULT 0;

CREATE INDEX idx_posts_conversion_prompted ON posts(conversion_prompted_at);
CREATE INDEX idx_posts_status_reaction_count ON posts(status, reaction_count);
```

### Migration 2: Post Conversions Table Enhancements
```sql
ALTER TABLE post_conversions
ADD COLUMN interested_users_notified INTEGER DEFAULT 0,
ADD COLUMN notification_sent_at TIMESTAMP NULL,
ADD COLUMN invited_users_notified INTEGER DEFAULT 0;
```

### Migration 3: Post Reactions Index
```sql
CREATE INDEX idx_post_reactions_post_type ON post_reactions(post_id, reaction_type);
```

---

## Task Breakdown (Parallel Work)

### 🔵 AGENT A: Backend Foundation & Conversion Logic

**Focus**: Service layer, business logic, database operations, event handling

#### A1: Database Migrations & Models (Day 1)
- Create migrations for schema changes
- Update Post model with new casts and scopes
- Update PostConversion model
- Add indexes for performance
- Write migration tests

#### A2: Service Layer - Conversion Eligibility (Day 1-2)
- Implement `PostService::shouldPromptConversion()`
- Implement `PostService::dismissConversionPrompt()`
- Add re-prompt logic (7-day expiry, max 3 dismissals)
- Add idempotency checks
- Write unit tests

#### A3: Service Layer - Conversion Execution (Day 2-3)
- Implement `PostService::convertPostToEvent()`
- Implement `ActivityService::createFromPost()`
- Add transaction handling
- Add rollback logic for failures
- Write unit tests

#### A4: Event System (Day 3-4)
- Create `PostConversionPrompted` event
- Create `PostConvertedToEvent` event
- Create `PostInvitationMigrated` event
- Create listeners for notifications
- Write event tests

#### A5: Notification Service (Day 4-5)
- Implement `NotificationService::notifyPostConversion()`
- Add batched notification dispatch (10 users per batch)
- Implement priority ordering (recent reactors first)
- Add invitation migration logic
- Write notification tests

#### A6: API Endpoints (Day 5-6)
- POST `/api/posts/{id}/convert` - Convert post to event
- POST `/api/posts/{id}/dismiss-prompt` - Dismiss conversion prompt
- GET `/api/posts/{id}/conversion-eligibility` - Check eligibility
- GET `/api/posts/{id}/interested-users-count` - Get count only
- Write API tests

---

### 🟢 AGENT B: Frontend UI & User Experience

**Focus**: Livewire components, Blade views, UI/UX, user interactions

#### B1: Profile "Interested" Tab (Day 1-2)
- Update User model with `getInterestedPosts()` method
- Create Livewire component for Interested tab
- Add tab to profile navigation (with overflow menu for mobile)
- Implement filtering (active/converted/expired)
- Add empty state
- Write component tests

#### B2: Post Card Enhancements (Day 2-3)
- Create conversion badge component (soft/strong variants)
- Add "Converted to Event" overlay for archived posts
- Implement "Remove Interest" action
- Add "View Event" CTA for converted posts
- Apply galaxy theme styling
- Write component tests

#### B3: Conversion Prompt Notifications (Day 3-4)
- Create in-app notification component
- Add notification bell integration
- Implement feed banner component
- Add dismissal logic with session handling
- Apply galaxy theme styling
- Write component tests

#### B4: Conversion Modal - Structure (Day 4-5)
- Create Livewire modal component
- Implement form layout (3 sections)
- Add pre-fill logic from post data
- Create interested users count display (privacy-safe)
- Apply galaxy theme styling
- Write component tests

#### B5: Conversion Modal - Form & Validation (Day 5-6)
- Implement all event fields
- Add real-time validation
- Implement image upload
- Add smart defaults (capacity = reactions * 1.5)
- Write validation tests

#### B6: Conversion Modal - Preview Step (Day 6-7)
- Create preview screen component
- Show event card preview
- Display notification count
- Add "Edit" and "Confirm" actions
- Write component tests

---

## Shared Tasks (Both Agents)

### S1: Integration Testing (Day 7-8)
- Test full conversion flow end-to-end
- Test notification delivery and batching
- Test RSVP flow after conversion
- Test edge cases (race conditions, failures)
- Test privacy (interested users not exposed)

### S2: UI/UX Polish (Day 8-9)
- Refine animations and transitions
- Test responsive layouts (mobile/tablet/desktop)
- Accessibility audit (WCAG 2.1 AA)
- Galaxy theme consistency check
- Cross-browser testing

### S3: Analytics & Monitoring (Day 9-10)
- Add conversion tracking events
- Implement dashboard metrics
- Set up error monitoring (Sentry/Bugsnag)
- Add performance monitoring
- Create admin analytics view

---

## Detailed Task Files

Each task has a detailed implementation guide:

- `AGENT_A_TASKS.md` - Backend implementation details
- `AGENT_B_TASKS.md` - Frontend implementation details
- `INTEGRATION_GUIDE.md` - How the two halves connect
- `TESTING_STRATEGY.md` - Comprehensive test plan

---

## Success Criteria

### Functional Requirements
- ✅ Users can view posts they've reacted to in profile "Interested" tab
- ✅ Post owners receive prompts at 5 and 10 reaction thresholds
- ✅ Post owners can convert posts to events via modal
- ✅ Interested users receive invitations (not auto-RSVPs)
- ✅ Converted posts are archived with event links
- ✅ Privacy: Interested users not exposed by name/avatar

### Technical Requirements
- ✅ All database operations use transactions
- ✅ Idempotency checks prevent duplicate prompts
- ✅ Notifications are batched (max 10 per batch)
- ✅ Race conditions handled (simultaneous reactions)
- ✅ 95%+ test coverage
- ✅ \u003c 200ms API response times

### UX Requirements
- ✅ Galaxy theme applied to all new components
- ✅ Mobile-responsive layouts
- ✅ WCAG 2.1 AA accessibility compliance
- ✅ Smooth animations and transitions
- ✅ Clear error messages and feedback

---

## Next Steps

1. **Agent A**: Start with A1 (Database Migrations)
2. **Agent B**: Start with B1 (Profile Interested Tab)
3. **Daily Sync**: 15-minute standup to discuss integration points
4. **Week 1 Milestone**: Backend foundation + Profile tab complete
5. **Week 2 Milestone**: Conversion modal + notifications complete
6. **Week 3 Milestone**: Integration testing + polish
7. **Week 4 Milestone**: Analytics + production deployment

---

*See individual agent task files for detailed implementation instructions.*

