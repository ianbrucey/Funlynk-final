# Group Member Experience - Feature Roadmap

## Overview
This document tracks the prioritized features for the group member experience, starting from the Instagram → Web conversion funnel through recurring engagement.

**Reference Use Case:** BJJ Gym "Sunday Open Mats" - recurring community activity promoted via Instagram ads.

---

## Open Questions (Resolved)

### Q1: Group Events vs Activities Architecture ✅ RESOLVED
**Question:** When creating "events" from a group, do we use existing Activity model?

**Answer:** Yes - use existing `Activity` model with `group_id` foreign key.

**Findings:**
- `Activity` model has `group_id` FK and `group()` relationship
- `GroupContentService::createGroupEvent()` creates activities with `group_id` set
- Activities have full RSVP support via `RsvpService`, `CapacityService`, `CheckInService`
- Activities have `is_public` boolean (defaults to `false` for group events)

**Visibility Rules:**
1. Group events do NOT appear in public nearby feed (is_public = false)
2. Non-members cannot RSVP - must join group first
3. Group events are members-only by default

**Decision:** Use existing `Activity` model. Group events are private by default.

---

### Q2: What is a "Session" vs "Event" vs "Post"? ✅ RESOLVED
**Question:** How do we distinguish content types?

**Answer:**
| Concept | Model | Characteristics |
|---------|-------|-----------------|
| **Schedule** | `Group.schedule_text` | Display-only text ("Sundays @ 4pm") |
| **Session/Event** | `Activity` with `group_id` | RSVP-able, has start_time, check-in support |
| **Post** | `Post` with `group_id` | Ephemeral (24-72h), reactions |
| **Announcement** | Future: `Post` with `post_type='announcement'` | High-priority, triggers push notification |

**"Next Session"** = Next upcoming `Activity` where `group_id = this group`
- Already implemented in `PublicGroupLanding::mount()`

**MVP Decision:**
- No structured schedule model - keep `schedule_text` as display-only
- Future: Add `group_schedules` table with RRULE-style recurrence

---

### Q3: "Post as Group" Implementation ✅ RESOLVED
**Question:** Why do posts show personal user instead of group?

**The Bug Found:**
1. `Post` model HAS `posted_as_group` boolean field ✅
2. `Post` model HAS `getAuthorAttribute()` accessor ✅
3. `PostService::createPost()` accepts `posted_as_group` ✅
4. **BUT** `post-card-compact.blade.php` uses `$post->user` NOT `$post->author`! ❌

**The Fix:**
```blade
{{-- BROKEN: Always shows user --}}
{{ $post->user?->display_name }}

{{-- FIXED: Uses author accessor (returns Group or User) --}}
{{ $post->author?->name ?? $post->author?->display_name }}
```

**Scope:**
- Posts: Support "Post as Group" (fix the view)
- Events: Do NOT support "Post as Group" (host is always a User)
- Group events identified by `group_id` being set, not by author display

---

## Prioritized Feature List

### Priority 0: GroupAuthModal (BLOCKING) ✅ COMPLETE
**Status:** Complete
**Complexity:** Medium (3-5 hours)
**Dependencies:** None

**Problem:** "Join Group" button redirects to `/login`, breaking context.

**Solution:** Created `GroupAuthModal` component (adapted from `EventAuthModal`):
- Inline modal with quick-join and sign-in modes
- Auto-join public groups after auth
- Create join request for private groups
- Extended `ContextPreservationService` for group intent

**Files Created/Modified:**
- `app/Livewire/Auth/GroupAuthModal.php` ✅ (new)
- `resources/views/livewire/auth/group-auth-modal.blade.php` ✅ (new)
- `app/Livewire/Groups/PublicGroupLanding.php` ✅ (modified - dispatches modal event)
- `app/Services/ContextPreservationService.php` ✅ (extended with `captureGroupIntent()`)

---

### Priority 1: Group Event RSVP Integration ✅ COMPLETE
**Status:** Complete
**Complexity:** Low
**Dependencies:** Resolve Q1 first

**Problem:** Group events exist but lack visible RSVP/attendee UI.

**Solution:** Wired existing `RsvpService` into group event cards:
- Added `RsvpButton` to group event cards on timeline ✅
- Show attendee avatars + count ✅
- "Sign in to RSVP" button for guests ✅
- Next Session card on landing page enhanced with RSVP ✅

**Files Modified:**
- `resources/views/livewire/groups/group-timeline.blade.php` ✅
- `resources/views/livewire/groups/public-group-landing.blade.php` ✅
- `app/Livewire/Groups/PublicGroupLanding.php` ✅ (eager load rsvps.user)
- `app/Services/GroupContentService.php` ✅ (eager load rsvps.user)

---

### Priority 2: Next Session Card Enhancement
**Status:** Not Started  
**Complexity:** Low
**Dependencies:** Priority 1, Resolve Q2

**Problem:** "Next Session" card exists but lacks prominence and RSVP.

**Solution:** 
- Redesign with hero treatment
- Add countdown timer
- Add "I'm In" button + attendee avatars
- Pin to top of timeline

---

### Priority 3: Post as Group Toggle ✅ COMPLETE
**Status:** Complete
**Complexity:** Low
**Dependencies:** Resolve Q3

**Problem:** Posts created "as group" still show personal user.

**Solution:**
- Fixed `post-card-compact.blade.php` to use `$post->author` accessor
- Added "GROUP" badge for group-authored posts
- Different gradient colors for group vs user avatars
- Events do NOT support "Post as Group" (host is always a User)

---

### Priority 4: Critical Notification Triggers
**Status:** Not Started
**Complexity:** Medium
**Dependencies:** Priority 1, 2

**Triggers (in priority order):**
1. Reminder before session (6h, 1h) - opt-out
2. New event created - for members
3. Join request approved - for requester
4. Admin announcement - high-priority blast
5. Weekly digest - engagement summary

---

### Priority 5: Recurring Schedule Management (MVP)
**Status:** Not Started
**Complexity:** Medium-High
**Dependencies:** Priority 1, 2

**MVP Approach:**
- Display `schedule_text` prominently (current: free text)
- Admin manually creates events

**Full Approach (Future):**
- Create `group_schedules` table with structured recurrence
- Scheduler command to auto-generate events
- Admin UI to manage schedule

---

## Scope Decision: Specs vs Single Document

**Recommendation:** 
- Priority 0 (GroupAuthModal): Single document sufficient - adapting existing pattern
- Priority 1-3: Single document - small scope, using existing infrastructure
- Priority 4-5: May need specs if scope grows

**Current approach:** Track in this document, create specs only if complexity warrants.

---

## Implementation Log

| Date | Feature | Status | Notes |
|------|---------|--------|-------|
| 2025-01-25 | Priority 0: GroupAuthModal | ✅ Complete | Inline auth modal for group join flow |
| 2025-01-25 | Priority 3: Post as Group Fix | ✅ Complete | Fixed post-card-compact.blade.php to use $post->author |
| 2025-01-25 | ContextPreservationService | ✅ Extended | Added captureGroupIntent() and group redirect handling |
| 2025-01-25 | Priority 1: Group Event RSVP | ✅ Complete | Added RsvpButton to timeline + landing page Next Session card |
| 2025-01-29 | Group Content Permissions | ✅ Complete | Configurable post/event permissions per group (everyone/admins) |
| 2025-01-29 | Simplified Post Creation | ✅ Complete | Removed title/tags/datetime, added expiration dropdown |
| 2025-01-29 | Pinned Posts (Announcements) | ✅ Complete | Admin-only pin/unpin, max 3, amber styling, top of timeline |
| 2025-01-29 | Event Creation Redesign | ✅ Complete | Simplified date/time picker, duration dropdown, modern UI |
| 2025-01-29 | Google Places Autocomplete | ✅ Complete | Location autocomplete for group event creation modal |
| 2025-01-29 | Timeline Refresh Fix | ✅ Complete | Posts/events appear immediately without page refresh |

