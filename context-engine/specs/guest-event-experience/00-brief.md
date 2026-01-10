# Guest-Friendly Event Experience - Brief

## Problem Statement

**The Friction Wall**: A user clicks an Instagram ad for "Sunset Yoga on the Beach - $15" and immediately hits a login page. No event details visible. No context about what they're signing up for. They bounce.

**Current Reality**:
- All `/activities/{id}` routes require authentication
- Instagram → Login Wall → Generic Form → Dashboard → Lost Context
- 80% bounce rate at the login wall
- <1% conversion from social media traffic
- Wasted ad spend on campaigns

**Business Impact**:
- Cannot run effective Instagram/Facebook campaigns
- No viral growth through social sharing
- Competitive disadvantage (Eventbrite, Facebook Events allow guest viewing)
- Poor ROI on marketing efforts ($50 CPA vs. $10 target)

## Success Verdict

### Must Have (Phase 1)
1. ✅ Guest users can view full public event details at `/e/{slug}` without authentication
2. ✅ Instagram/Facebook links lead directly to event page (no login wall)
3. ✅ After signup, user lands at checkout with event context preserved
4. ✅ Events have rich social media previews (image, title, description in Instagram/Facebook)
5. ✅ Guest-friendly RSVP button shows "Sign Up to Join" instead of hard redirect
6. ✅ Conversion rate improves from <1% to 13%+

### Should Have (Phase 2)
7. ✅ Guests can click "Interested" and provide email before creating account
8. ✅ Guests can share events to Instagram, Facebook, Twitter, WhatsApp
9. ✅ Guests can bookmark events (cookie-based, migrated after signup)
10. ✅ Social proof visible: "23 people interested, 15 attending"
11. ✅ Conversion rate improves to 20%+

### Nice to Have (Phase 3)
12. ✅ A/B testing for different CTAs and signup flows
13. ✅ Email nurture sequence for interested guests
14. ✅ Mobile-optimized for Instagram in-app browser
15. ✅ Referral tracking for viral growth incentives
16. ✅ Conversion rate improves to 29%+

## Core User Flows

### Flow 1: Instagram User Discovers Event (Current - Broken)

```
User sees Instagram ad: "Sunset Yoga 🧘‍♀️ Beach Party - $15"
    ↓
Clicks link → /activities/abc123
    ↓
❌ Route middleware blocks (requires auth)
    ↓
Redirects to /login (no context, generic form)
    ↓
User confused: "What was I signing up for?"
    ↓
80% bounce, 20% continue
    ↓
After login → Dashboard (context lost)
    ↓
User searches for event (if they remember)
    ↓
<1% conversion
```

**Pain Points**:
- No event details before login
- Context lost after authentication
- Generic login form (not event-specific)
- User has to search for event again

---

### Flow 2: Instagram User Discovers Event (Phase 1 - Fixed)

```
User sees Instagram ad: "Sunset Yoga 🧘‍♀️ Beach Party - $15"
    ↓
Clicks link → /e/sunset-yoga-beach-party
    ↓
✅ Public event page loads (no auth required)
    ↓
Sees: Full details, location, time, price, host, attendee count
    ↓
Clicks "Get Tickets - $15"
    ↓
Quick signup modal appears (context preserved)
  "Sign up to join Sunset Yoga Beach Party"
  [Email] [Password] [Sign Up with Google]
    ↓
After signup → /activities/abc123/checkout (context preserved)
    ↓
Completes purchase
    ↓
13%+ conversion
```

**Improvements**:
- Full event details visible before signup
- Context preserved through auth flow
- Event-specific signup modal
- Direct to checkout after auth

---

### Flow 3: Guest Expresses Interest (Phase 2)

```
User lands on /e/sunset-yoga-beach-party
    ↓
Not ready to commit, but interested
    ↓
Clicks "I'm Interested" button
    ↓
Email capture modal:
  "Get notified about Sunset Yoga Beach Party"
  [Email] [Notify Me]
    ↓
Email saved (no account created yet)
    ↓
Receives reminder email 24hrs before event:
  "Sunset Yoga is tomorrow! Only 5 spots left."
  [Get Tickets]
    ↓
Clicks link → /e/sunset-yoga-beach-party?interested=true
    ↓
"Welcome back! Ready to join?"
    ↓
Quick signup → Checkout
    ↓
20%+ conversion
```

**Benefits**:
- Captures interest without commitment
- Builds email list for retargeting
- Urgency messaging increases conversion
- Second touchpoint improves conversion

---

### Flow 4: Guest Shares Event (Phase 2)

```
User lands on /e/sunset-yoga-beach-party
    ↓
Clicks "Share" button
    ↓
Share modal appears:
  [Instagram Story] [Facebook] [Twitter] [WhatsApp] [Copy Link]
    ↓
Shares to Instagram Story with rich preview:
  Image: Beach sunset with yoga mats
  Title: "Sunset Yoga Beach Party"
  Description: "Join us for yoga at sunset! $15"
    ↓
Friend sees story → Clicks link
    ↓
Lands on /e/sunset-yoga-beach-party?ref=instagram_story_user123
    ↓
Viral growth + referral tracking
```

**Benefits**:
- Free marketing through social sharing
- Rich previews increase click-through
- Referral tracking enables incentives
- Viral coefficient >1 = exponential growth

---

## Technical Requirements

### Route Architecture

**Public Routes** (No Auth):
```php
Route::get('/e/{activity:slug}', PublicEventView::class)->name('events.public');
Route::post('/e/{activity:slug}/interested', [GuestEngagementController::class, 'interested']);
Route::get('/e/{activity:slug}/share', [GuestEngagementController::class, 'share']);
```

**Authenticated Routes** (Existing):
```php
Route::middleware(['auth', 'onboarding.complete'])->group(function () {
    Route::get('/activities/{activity}', ActivityDetail::class)->name('activities.show');
    Route::get('/activities/{activity}/checkout', CheckoutForm::class)->name('activities.checkout');
});
```

### Context Preservation

```php
// When guest clicks "Get Tickets"
Session::put('intended_action', [
    'type' => 'rsvp',
    'activity_id' => $activity->id,
    'activity_slug' => $activity->slug,
    'source' => 'instagram_campaign_001',
    'timestamp' => now()
]);

// After authentication
if (Session::has('intended_action')) {
    $action = Session::get('intended_action');
    return redirect()->route('activities.checkout', $action['activity_id']);
}
```

### Social Media Meta Tags

```blade
{{-- In PublicEventView blade --}}
<x-social-meta
    :title="$activity->title"
    :description="Str::limit($activity->description, 160)"
    :image="$activity->cover_image_url ?? asset('images/default-event.jpg')"
    :url="route('events.public', $activity->slug)"
    type="event"
    :event-start="$activity->start_time"
    :event-location="$activity->location_name"
/>
```

## Out of Scope (v1)

- Native mobile app deep links (future)
- Guest commenting on events (E05 Social Interaction)
- Guest reactions/likes (E05 Social Interaction)
- Advanced referral incentive programs (E06 Monetization)
- Guest event creation (always requires auth)
- Private event guest access (requires invitation system)

## Open Questions (Decide Before Implementation)

1. **Email Capture Timing**: Capture email immediately on "Interested" click, or show modal first?
   - **Recommendation**: Modal first (less friction)

2. **Bookmark Persistence**: How long should cookie-based bookmarks last?
   - **Recommendation**: 30 days

3. **Social Share Tracking**: Track individual shares for referral incentives?
   - **Recommendation**: Yes (Phase 2), enables future referral programs

4. **Mobile Optimization**: Optimize for Instagram in-app browser specifically?
   - **Recommendation**: Yes (Phase 3), Instagram is primary traffic source

5. **Guest Data Retention**: How long to keep guest interest data before cleanup?
   - **Recommendation**: 90 days for unconverted interests

6. **Spam Prevention**: reCAPTCHA on email capture?
   - **Recommendation**: Yes, invisible reCAPTCHA v3

## Tech Stack Context

- **Framework**: Laravel 12, Livewire v3
- **Existing Models**: `Activity`, `Rsvp`, `User`
- **Existing Services**: `ActivityService`, `RsvpService`, `NotificationService`
- **New Tables**: `event_interests`, `guest_bookmarks`, `social_shares`
- **External**: Stripe (payments), SendGrid (emails), Google reCAPTCHA (spam prevention)

