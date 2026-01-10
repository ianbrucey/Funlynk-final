# Guest-Friendly Event Experience

## Overview
Enable non-authenticated users to discover, engage with, and convert to attendees for FunLynk events through public event pages, soft engagement options, and optimized social media sharing. This system maximizes conversion from Instagram/Facebook campaigns while maintaining security and user experience quality.

## Status: 📋 PLANNED (Not Implemented)

## Problem Statement
**Current State**: All event routes require authentication, creating a hard wall for social media traffic. Instagram users clicking event links hit a login page immediately with no context preservation, resulting in ~80% bounce rate and <1% conversion.

**Business Impact**: 
- Paid social media campaigns waste 80%+ of ad spend
- No viral growth through social sharing
- Competitive disadvantage vs. Eventbrite, Facebook Events
- Poor ROI on marketing efforts

## Success Verdict
1. Guest users CAN view full public event details without authentication
2. Instagram/Facebook links lead directly to event pages (no login wall)
3. Context is preserved through signup flow (user lands at checkout after auth)
4. Guests CAN express interest via email capture before creating account
5. Events have rich social media previews (Open Graph, Twitter Cards)
6. Conversion rate from social media improves from <1% to 15%+

## Documents

| File | Purpose |
|------|---------|
| [00-brief.md](./00-brief.md) | Problem statement, success criteria, user flows |
| [01-database-schema.md](./01-database-schema.md) | New tables for guest engagement tracking |
| [02-routes-architecture.md](./02-routes-architecture.md) | Public vs authenticated routes, URL structure |
| [03-service-architecture.md](./03-service-architecture.md) | Service layer for guest engagement, context preservation |
| [04-ui-components.md](./04-ui-components.md) | Guest event view, interest modals, share buttons |
| [05-social-media-optimization.md](./05-social-media-optimization.md) | Meta tags, Open Graph, Twitter Cards, Schema.org |
| [06-implementation-plan.md](./06-implementation-plan.md) | 3-phase rollout with time estimates |
| [07-fixtures.md](./07-fixtures.md) | Test data, example events, conversion scenarios |
| [08-analytics-tracking.md](./08-analytics-tracking.md) | Conversion funnel metrics, source attribution |

## Quick Reference

### Key Features

**Phase 1: Public Viewing** (Critical - Week 1-2)
- Public event routes (`/e/{slug}`)
- Context preservation through auth
- Social media meta tags
- Guest-friendly RSVP button

**Phase 2: Engagement** (High Priority - Week 3-4)
- "Interested" button with email capture
- Social sharing buttons
- Guest bookmarking (cookie-based)
- Social proof indicators

**Phase 3: Optimization** (Enhancement - Week 5-6)
- A/B testing framework
- Analytics integration
- Mobile optimization
- Email nurture sequences

### URL Structure

```
Public (No Auth):
  /e/{slug}                    → Guest event view
  /e/{slug}/share              → Share modal
  /e/{slug}/interested         → Interest capture

Authenticated:
  /activities/{id}             → Full event management
  /activities/{id}/edit        → Host editing
  /activities/{id}/checkout    → Ticket purchase
```

### Conversion Funnel

```
Instagram Ad → Public Event Page → Interest/Share → Quick Signup → Checkout → Confirmed
     100%            90%               40%             70%           75%        19%
```

**Target**: 15-30% overall conversion (vs. <1% current)

## Dependencies

**Existing**:
- `Activity` model with `is_public` flag
- `ActivityPolicy` (already allows guest viewing)
- `RsvpService` for ticket purchases
- `NotificationService` for emails

**New**:
- 2 tables: `event_interests`, `guest_bookmarks`
- 3 services: `GuestEngagementService`, `ContextPreservationService`, `SocialShareService`
- 4 Livewire components: `PublicEventView`, `InterestedButton`, `ShareButton`, `EmailCaptureModal`
- Meta tag blade components

## Estimated Effort

**Phase 1**: 18 hours (2-3 days) - Critical path
**Phase 2**: 24 hours (3-4 days) - High priority
**Phase 3**: 28 hours (3-4 days) - Enhancement

**Total**: 70 hours (~2 weeks with testing)

## Success Metrics

| Metric | Current | Phase 1 Target | Phase 3 Target |
|--------|---------|----------------|----------------|
| Instagram → Event View | 20% | 90% | 95% |
| Event View → Signup Intent | 10% | 30% | 45% |
| Signup Intent → Complete | 40% | 70% | 85% |
| Complete → Purchase | 50% | 70% | 80% |
| **Overall Conversion** | **0.4%** | **13.2%** | **29.1%** |
| Cost Per Acquisition | $50 | $15 | $10 |
| Return on Ad Spend | 1.5x | 4x | 7x |

## Open Questions

See [00-brief.md](./00-brief.md#open-questions) for decisions needed before implementation:
- Email capture: Pre-signup or post-interest?
- Bookmarking: Cookie-based or require email?
- Social sharing: Track referrals for incentives?
- Mobile optimization: Native app deep links?

## Related Specs

- **Event Edit Protection**: Ensures guest-viewed events maintain integrity
- **E05 Social Interaction**: Future integration for comments, reactions on public pages
- **E06 Payments**: Checkout flow optimization for guest conversions

