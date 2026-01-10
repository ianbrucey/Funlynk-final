# Implementation Plan - Guest Event Experience

## Overview
3-phase rollout over 2 weeks. Phase 1 is critical path for social media campaigns. Phases 2-3 optimize conversion and enable viral growth.

## Phase 1: Public Viewing & Context Preservation (Critical - Week 1)

**Goal**: Enable guest viewing and preserve context through signup
**Duration**: 18 hours (2-3 days)
**Priority**: 🔥 CRITICAL - Blocks social media campaigns

### T01: Database Schema (3 hours)

```bash
# Create migrations
php artisan make:migration add_slug_to_activities_table
php artisan make:migration create_event_interests_table
php artisan make:migration create_guest_bookmarks_table
php artisan make:migration create_social_shares_table

# Run migrations
php artisan migrate
```

**Files**:
- `database/migrations/YYYY_MM_DD_add_slug_to_activities_table.php`
- `database/migrations/YYYY_MM_DD_create_event_interests_table.php`
- `database/migrations/YYYY_MM_DD_create_guest_bookmarks_table.php`
- `database/migrations/YYYY_MM_DD_create_social_shares_table.php`

**Acceptance Criteria**:
- [ ] `activities.slug` column added with unique index
- [ ] Existing activities have slugs generated
- [ ] All 3 new tables created with proper indexes
- [ ] Foreign keys and constraints in place

---

### T02: Models & Relationships (2 hours)

```bash
# Create models
php artisan make:model EventInterest
php artisan make:model GuestBookmark
php artisan make:model SocialShare
```

**Files**:
- `app/Models/EventInterest.php`
- `app/Models/GuestBookmark.php`
- `app/Models/SocialShare.php`
- Update `app/Models/Activity.php` (add relationships)

**Acceptance Criteria**:
- [ ] All models have proper relationships defined
- [ ] Casts and fillable properties configured
- [ ] Activity model has slug generation logic
- [ ] Models have proper PHPDoc blocks

---

### T03: Service Classes (4 hours)

```bash
# Create services
php artisan make:class Services/GuestEngagementService
php artisan make:class Services/ContextPreservationService
php artisan make:class Services/SocialShareService
```

**Files**:
- `app/Services/GuestEngagementService.php`
- `app/Services/ContextPreservationService.php`
- `app/Services/SocialShareService.php`

**Acceptance Criteria**:
- [ ] All service methods implemented per spec
- [ ] Proper type hints and return types
- [ ] PHPDoc blocks for all public methods
- [ ] Services registered in service container if needed

---

### T04: Public Routes & Middleware (3 hours)

```bash
# Create Livewire component
php artisan make:livewire PublicEventView

# Create middleware
php artisan make:middleware CaptureIntendedAction
```

**Files**:
- `routes/web.php` (add public routes)
- `app/Http/Livewire/PublicEventView.php`
- `resources/views/livewire/public-event-view.blade.php`
- `app/Http/Middleware/CaptureIntendedAction.php`

**Acceptance Criteria**:
- [ ] `/e/{slug}` route accessible without auth
- [ ] PublicEventView component displays event details
- [ ] Middleware captures UTM params and referral codes
- [ ] Route throttling configured

---

### T05: Social Media Meta Tags (3 hours)

```bash
# Create Blade component
php artisan make:component SocialMeta
```

**Files**:
- `app/View/Components/SocialMeta.php`
- `resources/views/components/social-meta.blade.php`

**Acceptance Criteria**:
- [ ] Open Graph tags for Facebook/Instagram
- [ ] Twitter Card tags
- [ ] Schema.org Event markup
- [ ] Dynamic meta descriptions per event
- [ ] Proper image URLs for social previews

---

### T06: Context Preservation in Auth Flow (2 hours)

**Files**:
- Update `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Update `app/Http/Controllers/Auth/RegisteredUserController.php`

**Acceptance Criteria**:
- [ ] Login redirects to intended action
- [ ] Registration redirects to intended action
- [ ] Guest data migrated after signup
- [ ] Session cleaned up after redirect

---

### T07: Testing Phase 1 (1 hour)

```bash
# Create tests
php artisan make:test --pest Feature/GuestEventViewingTest
php artisan make:test --pest Feature/ContextPreservationTest
php artisan make:test --pest Unit/GuestEngagementServiceTest
```

**Acceptance Criteria**:
- [ ] Guest can view public event
- [ ] Guest redirected to login for private event
- [ ] Context preserved through signup
- [ ] Services tested in isolation

---

## Phase 2: Engagement Features (High Priority - Week 2)

**Goal**: Add soft engagement options for guests
**Duration**: 24 hours (3-4 days)
**Priority**: ⚠️ HIGH - Improves conversion significantly

### T08: "Interested" Button & Email Capture (6 hours)

```bash
php artisan make:livewire InterestedButton
php artisan make:livewire EmailCaptureModal
php artisan make:controller GuestEngagementController
```

**Files**:
- `app/Http/Livewire/InterestedButton.php`
- `app/Http/Livewire/EmailCaptureModal.php`
- `app/Http/Controllers/GuestEngagementController.php`
- `resources/views/livewire/interested-button.blade.php`
- `resources/views/livewire/email-capture-modal.blade.php`

**Acceptance Criteria**:
- [ ] "Interested" button visible to guests
- [ ] Email capture modal with validation
- [ ] Interest recorded in database
- [ ] Confirmation email sent
- [ ] Duplicate interest handling

---

### T09: Social Sharing Buttons (5 hours)

```bash
php artisan make:livewire ShareButton
php artisan make:livewire ShareModal
```

**Files**:
- `app/Http/Livewire/ShareButton.php`
- `app/Http/Livewire/ShareModal.php`
- `resources/views/livewire/share-button.blade.php`
- `resources/views/livewire/share-modal.blade.php`

**Acceptance Criteria**:
- [ ] Share buttons for Instagram, Facebook, Twitter, WhatsApp
- [ ] Copy link functionality
- [ ] Referral codes generated
- [ ] Share URLs tracked in database
- [ ] Platform-specific share dialogs

---

### T10: Guest Bookmarking (5 hours)

```bash
php artisan make:livewire BookmarkButton
```

**Files**:
- `app/Http/Livewire/BookmarkButton.php`
- `resources/views/livewire/bookmark-button.blade.php`
- Update `app/Http/Controllers/Auth/RegisteredUserController.php`

**Acceptance Criteria**:
- [ ] Bookmark button visible to guests
- [ ] Cookie-based guest token generated
- [ ] Bookmarks stored in database
- [ ] Bookmarks migrated after signup
- [ ] User can view bookmarks in dashboard

---

### T11: Social Proof Indicators (4 hours)

```bash
php artisan make:livewire SocialProofBadge
```

**Files**:
- `app/Http/Livewire/SocialProofBadge.php`
- `resources/views/livewire/social-proof-badge.blade.php`

**Acceptance Criteria**:
- [ ] "X people interested" displayed
- [ ] "Y people attending" displayed
- [ ] Real-time updates via Livewire
- [ ] Proper pluralization
- [ ] Urgency indicators ("Only 5 spots left")

---

### T12: Email Reminder Job (4 hours)

```bash
php artisan make:job SendEventRemindersJob
php artisan make:mail EventReminderMail
```

**Files**:
- `app/Jobs/SendEventRemindersJob.php`
- `app/Mail/EventReminderMail.php`
- `resources/views/emails/event-reminder.blade.php`

**Acceptance Criteria**:
- [ ] Job scheduled to run daily
- [ ] Sends reminders 24hrs before event
- [ ] Only to unconverted interests
- [ ] Includes "Get Tickets" CTA
- [ ] Tracks reminder sent timestamp

---

## Phase 3: Optimization & Analytics (Enhancement - Week 3)

**Goal**: Maximize conversion and enable data-driven decisions
**Duration**: 28 hours (3-4 days)
**Priority**: 📈 ENHANCEMENT - Improves ROI

### T13: Analytics Integration (6 hours)

```bash
php artisan make:class Services/AnalyticsService
php artisan make:migration create_conversion_events_table
```

**Files**:
- `app/Services/AnalyticsService.php`
- `database/migrations/YYYY_MM_DD_create_conversion_events_table.php`

**Acceptance Criteria**:
- [ ] Track conversion funnel steps
- [ ] Source attribution (Instagram, Facebook, etc.)
- [ ] UTM parameter tracking
- [ ] Referral conversion tracking
- [ ] Dashboard for analytics

---

### T14: A/B Testing Framework (8 hours)

```bash
php artisan make:class Services/ABTestingService
php artisan make:migration create_ab_tests_table
php artisan make:migration create_ab_test_variants_table
```

**Files**:
- `app/Services/ABTestingService.php`
- `database/migrations/YYYY_MM_DD_create_ab_tests_table.php`
- `database/migrations/YYYY_MM_DD_create_ab_test_variants_table.php`

**Acceptance Criteria**:
- [ ] Test different CTAs
- [ ] Test signup modal vs. redirect
- [ ] Test social proof placement
- [ ] Track variant performance
- [ ] Statistical significance calculation

---

### T15: Mobile Optimization (8 hours)

**Files**:
- Update `resources/views/livewire/public-event-view.blade.php`
- Update `resources/views/livewire/email-capture-modal.blade.php`
- Add mobile-specific CSS

**Acceptance Criteria**:
- [ ] Instagram in-app browser optimization
- [ ] Touch-friendly buttons
- [ ] Fast load times (<2s)
- [ ] Responsive images
- [ ] Mobile checkout flow

---

### T16: Email Nurture Sequence (6 hours)

```bash
php artisan make:job SendNurtureEmailsJob
php artisan make:mail InterestFollowUpMail
php artisan make:mail EventFillingUpMail
php artisan make:mail SimilarEventsMail
```

**Files**:
- `app/Jobs/SendNurtureEmailsJob.php`
- `app/Mail/InterestFollowUpMail.php`
- `app/Mail/EventFillingUpMail.php`
- `app/Mail/SimilarEventsMail.php`

**Acceptance Criteria**:
- [ ] Follow-up email 3 days after interest
- [ ] Urgency email when event 80% full
- [ ] Similar events recommendations
- [ ] Unsubscribe functionality
- [ ] Email performance tracking

---

## Testing Strategy

### Unit Tests (Throughout)
- Service classes in isolation
- Model relationships
- Helper functions

### Feature Tests (End of Each Phase)
- Guest viewing flows
- Context preservation
- Engagement features
- Conversion tracking

### Manual Testing Checklist
- [ ] Instagram in-app browser
- [ ] Facebook in-app browser
- [ ] Mobile Safari
- [ ] Desktop Chrome
- [ ] Social media previews
- [ ] Email deliverability

---

## Deployment Strategy

### Phase 1 Deployment
1. Deploy to staging
2. Test with real Instagram campaign (small budget)
3. Monitor conversion metrics
4. Deploy to production if metrics improve

### Phase 2 Deployment
1. Deploy engagement features
2. A/B test against Phase 1
3. Monitor email deliverability
4. Adjust based on data

### Phase 3 Deployment
1. Deploy analytics and optimization
2. Run A/B tests for 2 weeks
3. Implement winning variants
4. Scale successful campaigns

---

## Success Metrics

| Phase | Metric | Target |
|-------|--------|--------|
| Phase 1 | Instagram → Event View | 90% |
| Phase 1 | Overall Conversion | 13%+ |
| Phase 2 | Email Capture Rate | 25% |
| Phase 2 | Overall Conversion | 20%+ |
| Phase 3 | Cost Per Acquisition | $10 |
| Phase 3 | Overall Conversion | 29%+ |

