# Post-to-Event Conversion - Task Board

> **Visual Overview**: Track progress for both agents  
> **Update**: Mark tasks as ⏳ In Progress, ✅ Complete, or ❌ Blocked

---

## 🔵 Agent A: Backend Tasks

### Week 1: Foundation

#### A1: Database Migrations & Models (Day 1)
- [ ] Create migration for posts table (conversion tracking)
- [ ] Create migration for post_conversions table (notification tracking)
- [ ] Create migration for post_reactions index
- [ ] Update Post model with casts and scopes
- [ ] Update PostConversion model
- [ ] Write migration tests
- **Status**: ⬜ Not Started
- **Blockers**: None

#### A2: Conversion Eligibility Service (Day 1-2)
- [ ] Create ConversionEligibilityService class
- [ ] Implement shouldPrompt() logic
- [ ] Implement getThresholdLevel() logic
- [ ] Add dismissConversionPrompt() to PostService
- [ ] Add getConversionEligibility() to PostService
- [ ] Write unit tests (95%+ coverage)
- **Status**: ⬜ Not Started
- **Blockers**: Depends on A1

#### A3: Conversion Execution Service (Day 2-3)
- [ ] Create ActivityConversionService class
- [ ] Implement createFromPost() method
- [ ] Implement previewConversion() method
- [ ] Add convertToEvent() to PostService
- [ ] Add validation logic
- [ ] Write unit tests (95%+ coverage)
- **Status**: ⬜ Not Started
- **Blockers**: Depends on A2

### Week 2: Events & Notifications

#### A4: Event System (Day 3-4)
- [ ] Create PostConversionPrompted event
- [ ] Create PostConvertedToEvent event
- [ ] Create PostInvitationMigrated event
- [ ] Create SendConversionPromptNotification listener
- [ ] Create NotifyInterestedUsers listener
- [ ] Create MigratePostInvitations listener
- [ ] Register in EventServiceProvider
- [ ] Write event tests
- **Status**: ⬜ Not Started
- **Blockers**: Depends on A3

#### A5: Notification Service (Day 4-5)
- [ ] Implement NotifyInterestedUsers listener
- [ ] Create SendConversionNotificationBatch job
- [ ] Implement batching logic (10 per batch)
- [ ] Implement priority ordering (recent first)
- [ ] Create MigratePostInvitations listener
- [ ] Write job tests
- **Status**: ⬜ Not Started
- **Blockers**: Depends on A4

#### A6: API Endpoints (Day 5-6)
- [ ] Create PostConversionController
- [ ] Implement checkEligibility() endpoint
- [ ] Implement preview() endpoint
- [ ] Implement convert() endpoint
- [ ] Implement dismissPrompt() endpoint
- [ ] Implement getInterestedUsersCount() endpoint
- [ ] Add routes to api.php
- [ ] Write API tests
- **Status**: ⬜ Not Started
- **Blockers**: Depends on A5

---

## 🟢 Agent B: Frontend Tasks

### Week 1: Profile & Post Cards

#### B1: Profile "Interested" Tab (Day 1-2)
- [ ] Add getInterestedPosts() to User model
- [ ] Create InterestedTab Livewire component
- [ ] Create interested-tab.blade.php view
- [ ] Create PostCardInterested component
- [ ] Update ShowProfile component
- [ ] Add tab to profile navigation
- [ ] Implement filtering (active/converted/expired)
- [ ] Add empty state
- [ ] Write component tests
- **Status**: ⬜ Not Started
- **Blockers**: None

#### B2: Post Card Enhancements (Day 2-3)
- [ ] Create ConversionBadge component
- [ ] Create ConvertedPostOverlay component
- [ ] Update post-card-compact with badges
- [ ] Add openConversionModal() to NearbyFeed
- [ ] Apply galaxy theme styling
- [ ] Write component tests
- **Status**: ⬜ Not Started
- **Blockers**: Depends on B1

#### B3: Conversion Prompt Notifications (Day 3-4)
- [ ] Create ConversionPromptCard component
- [ ] Create FeedConversionBanner component
- [ ] Add dismissBanner() to NearbyFeed
- [ ] Update NotificationsList component
- [ ] Add convertPost() and dismissPrompt() methods
- [ ] Apply galaxy theme styling
- [ ] Write component tests
- **Status**: ⬜ Not Started
- **Blockers**: Depends on B2

### Week 2: Conversion Modal

#### B4: Conversion Modal Structure (Day 4-5)
- [ ] Create ConvertPostModal Livewire component
- [ ] Implement open() method with pre-fill logic
- [ ] Implement preFillForm() method
- [ ] Implement loadPreviewData() method
- [ ] Implement togglePreview() method
- [ ] Implement close() method
- [ ] Write component tests
- **Status**: ⬜ Not Started
- **Blockers**: Depends on B3, needs A6 API

#### B5: Conversion Modal Form (Day 5-6)
- [ ] Create convert-post-modal.blade.php view
- [ ] Implement all form fields
- [ ] Add real-time validation
- [ ] Implement image upload
- [ ] Add form/preview toggle
- [ ] Apply galaxy theme styling
- [ ] Write form tests
- **Status**: ⬜ Not Started
- **Blockers**: Depends on B4

#### B6: Event Preview Component (Day 6-7)
- [ ] Create EventPreviewCard Livewire component
- [ ] Create event-preview-card.blade.php view
- [ ] Implement event card preview
- [ ] Implement notification preview
- [ ] Add confirm/edit actions
- [ ] Apply galaxy theme styling
- [ ] Write component tests
- **Status**: ⬜ Not Started
- **Blockers**: Depends on B5

---

## 🔗 Integration Tasks (Both Agents)

### Week 3: Integration & Testing

#### S1: Integration Testing (Day 7-8)
- [ ] Test full conversion flow end-to-end
- [ ] Test notification delivery and batching
- [ ] Test RSVP flow after conversion
- [ ] Test edge cases (race conditions, failures)
- [ ] Test privacy (interested users not exposed)
- [ ] Test dismissal and re-prompt logic
- [ ] Test invitation migration
- **Status**: ⬜ Not Started
- **Blockers**: Depends on A6 and B6

#### S2: UI/UX Polish (Day 8-9)
- [ ] Refine animations and transitions
- [ ] Test responsive layouts (mobile/tablet/desktop)
- [ ] Accessibility audit (WCAG 2.1 AA)
- [ ] Galaxy theme consistency check
- [ ] Cross-browser testing (Chrome, Firefox, Safari)
- [ ] Performance optimization
- **Status**: ⬜ Not Started
- **Blockers**: Depends on S1

#### S3: Analytics & Monitoring (Day 9-10)
- [ ] Add conversion tracking events
- [ ] Implement dashboard metrics
- [ ] Set up error monitoring (Sentry/Bugsnag)
- [ ] Add performance monitoring
- [ ] Create admin analytics view
- [ ] Document metrics and KPIs
- **Status**: ⬜ Not Started
- **Blockers**: Depends on S2

---

## 📊 Progress Summary

### Agent A (Backend)
- **Total Tasks**: 6 major tasks, ~30 subtasks
- **Completed**: 0/6 (0%)
- **In Progress**: 0/6
- **Blocked**: 0/6
- **Estimated Days**: 6-7 days

### Agent B (Frontend)
- **Total Tasks**: 6 major tasks, ~35 subtasks
- **Completed**: 0/6 (0%)
- **In Progress**: 0/6
- **Blocked**: 0/6
- **Estimated Days**: 7-8 days

### Shared (Integration)
- **Total Tasks**: 3 major tasks, ~20 subtasks
- **Completed**: 0/3 (0%)
- **In Progress**: 0/3
- **Blocked**: 0/3
- **Estimated Days**: 3-4 days

### Overall Progress
- **Total**: 0/15 major tasks (0%)
- **Timeline**: Week 0 of 4
- **On Track**: ✅ Yes

---

## 🎯 Current Sprint (Week 1)

### Agent A Focus
- [ ] A1: Database Migrations & Models
- [ ] A2: Conversion Eligibility Service
- [ ] A3: Conversion Execution Service

### Agent B Focus
- [ ] B1: Profile "Interested" Tab
- [ ] B2: Post Card Enhancements
- [ ] B3: Conversion Prompt Notifications

### Integration Points
- Model helpers (Post scopes) → Blade components
- Database schema → Component queries

---

## 🚨 Blockers & Risks

### Current Blockers
- None (ready to start)

### Potential Risks
- ⚠️ API contract changes between A and B
- ⚠️ Event name mismatches
- ⚠️ Database schema changes mid-development
- ⚠️ Galaxy theme inconsistencies

### Mitigation
- Daily 15-minute sync
- Immediate communication on contract changes
- Integration testing after each task
- Reference ui-design-standards.md frequently

---

## 📅 Milestones

- [ ] **Week 1 Complete**: Backend foundation + Profile tab (A1-A3, B1-B3)
- [ ] **Week 2 Complete**: Conversion modal + Notifications (A4-A6, B4-B6)
- [ ] **Week 3 Complete**: Integration testing + Polish (S1-S2)
- [ ] **Week 4 Complete**: Analytics + Production ready (S3)

---

## 📝 Notes

### Agent A Notes
- Remember to wrap all operations in transactions
- Add idempotency checks for prompts
- Batch notifications (10 per batch, 5-min delays)
- Test race conditions thoroughly

### Agent B Notes
- Apply galaxy theme to all components
- Use privacy-safe count display (no names/avatars)
- Test mobile responsiveness
- Add accessibility attributes (ARIA labels)

---

**Last Updated**: 2025-11-30  
**Next Update**: End of Week 1

---

*Update this board daily to track progress and identify blockers early.*

