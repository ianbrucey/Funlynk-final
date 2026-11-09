# 019 — Archaeology results and implementation plan

Written 2026-11-08 by Lotus, completing the steps the brief left open.
The brief (`00-brief.md`) stands as written; every [O] stop condition is
honored with the brief's own default, no new owner decisions invented.

## Archaeology verdict: same table CONFIRMED

Host reads enumerated 2026-11-08 over `app/` and `resources/views/`:

- 23 `->host` reads in 9 files. The genuinely null-unsafe ones:
  - `app/Services/FeedService.php` ~L614-632 (`mapEventToFeedItem`):
    `author_image_url` and `author_url` read `$event->host->...`
    unguarded. This is the feed crash the brief predicted; fix here.
  - `app/Services/PaymentService.php` L53/L62 and
    `app/Livewire/Payments/CheckoutForm.php` L39: `$activity->host->stripeAccount`.
    Only reachable through checkout, which C-11 forbids for listings;
    guard at the policy/service entry, not by faking a host.
  - `app/Jobs/NotifyFollowersOfNewEvent.php` already guards
    `! $activity->host` (L39). No change needed; listings never
    dispatch it.
- Everything else reads `host_id` in comparisons
  (`$activity->host_id === $user->id` in ActivityPolicy, RsvpPolicy,
  ActivityDetail, QrScanner, HostAttendeeManager, EventDashboard).
  For a listing (host_id NULL) these simply evaluate false, which is
  the correct outcome: nobody is the host of a listing.
- `PostConvertedToEventMail` receives a host from post conversion only;
  listings never convert. No change.
- Feed "following" event query (`FeedService` L98, `whereIn('host_id',
  $followed)`) naturally excludes listings; the nearby/discovery path
  is where listings join.

Conclusion: reusing `activities` is cheaper than a separate table, as
the brief assumed. Null-safety work is concentrated in FeedService
plus hosted-only action guards.

## Decisions applied (from the brief, not invented)

- [O-01] Feed cap: config `listings.feed_cap`, default **2 listings
  per 10-item page**, owner-tunable. No separate listings filter in v1.
- [O-04] Images: NO source image is downloaded or hot-linked.
  Listings render with the existing date-strip-over-placeholder card
  treatment (spec 018 pattern).
- [O-07] "Connect" in v1 = the interested list only. No chat, no
  messaging for listings.
- Kill switch: config `listings.enabled` (env `LISTINGS_ENABLED`,
  default true). When false, listings vanish from feed, map, search
  and detail (detail 404s) without a deploy.
- Approval is by batch in admin and records who and when
  (`approved_by`, `approved_at` on the activity row).

## Build plan (branch `feat/019-external-event-listings`)

1. **Migration** (backward-compatible, runs on the live schema):
   - `activities.kind` string, default `hosted`, indexed.
   - `activities.source_name` nullable string,
     `activities.source_url` nullable string.
   - `activities.approved_by` nullable uuid FK users nullOnDelete,
     `activities.approved_at` nullable timestamp.
   - `host_id` becomes nullable; CHECK constraint:
     `(kind = 'hosted' AND host_id IS NOT NULL) OR
      (kind = 'external' AND host_id IS NULL)`.
   - New table `activity_interests` (activity_id FK cascade,
     user_id FK cascade, timestamps, unique(activity_id, user_id)).
2. **Model**: `Activity` gets `kind` casts/scopes
   (`scopeHosted`, `scopeListings`, `scopePublishedListings`),
   `isListing()`, `interestedUsers()` relation; `host` relation
   unchanged (nullable now).
3. **Feed**: listings join the nearby/discovery event query when
   enabled, published, and not started/ended (start_time > now or
   end_time > now, rule: hidden once ended, or once started when no
   end_time). Apply the per-page cap after merge, and collapse
   recurring listings (same `recurring_schedule_id`) to the next
   upcoming occurrence. `mapEventToFeedItem` becomes null-safe and
   emits `is_listing`, `source_name`, `source_url`,
   `interest_count`, `viewer_interested`. Query count must not grow:
   eager-load interest counts with the page query (C-13).
4. **Card + detail UI** (galaxy theme, existing card components,
   390px-safe by construction): listing card shows an "Around town"
   label in place of the host row, a "Details" link to the source
   URL (`target="_blank"`, `rel="noopener noreferrer"`), and an
   "I'm interested" toggle with count. No RSVP, Invite, Follow host,
   or price chip unless a real price is known. Detail page for a
   listing shows the same fields, a "FunLynk does not run this
   event" notice, the source link, and the interested list filtered
   by each user's existing profile-privacy rules. No tickets, needs,
   or chat sections.
5. **Hosted-only guards** (C-11): RSVP store, ticket checkout,
   event chat, and event needs endpoints reject listings with 403
   (policy check on kind). Interest is the only attendance action a
   listing accepts, and only when published and not expired (C-14).
6. **Intake**: `php artisan listings:import {path}` reads a JSON
   file of candidate listings (the format Muse research produces),
   validates strictly (https source URL only; title, description,
   start time, location name, coordinates required; payment rules
   per the existing checks), dedupes on
   (source_url, title, start_time), and creates rows as
   kind=external, status=draft. It can never publish. A JSON schema
   example ships in the spec folder (`04-import-format.md` written
   during the build).
7. **Admin**: Filament Activity resource gains a kind filter and,
   for listings, Approve (single + bulk) and Unpublish actions,
   admin-only, recording approved_by/approved_at. Draft listings
   are visible only in admin (C-05).
8. **Tests**: Pest tests named exactly as the brief's claims table
   (C-01 to C-15), plus fixtures per the brief's evidence list.
   Full suite must stay green against the Nov 6 baseline
   (417 passed / 2 skipped) plus the new tests. Pint clean;
   PHPStan level 7 clean for touched files.

## Rollout

Null-safe code and migration ship together on the branch; no listing
rows exist until an import runs, so merging changes nothing visible.
Rollback: set `LISTINGS_ENABLED=false`, or unpublish all listings in
admin. The columns can stay.

## Out of scope (unchanged from the brief)

Claiming, ticketing/RSVP/chat for listings, source images,
auto-publishing, scraping inside FunLynk.
