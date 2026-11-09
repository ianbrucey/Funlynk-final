# 020 — Listing interaction and images — Strategic brief

**Status:** DRAFT (for owner review; not yet approved to build)
**Author:** Lotus (archaeology + plan by the same hand, as on 019)
**Date:** 2026-11-09

> Origin: owner used spec 019 live on 2026-11-09, approved the first
> listing ("Tuesday Social Run") in Filament, saw it on the homepage,
> and directed two changes in his own words, in substance: people
> should stay inside FunLynk on a listing — mark they are going, see
> who else is going, and communicate — with the external link kept
> but secondary; and listing research should include images from now
> on, with the image pipeline chosen for stability and
> maintainability over convenience.
>
> This spec therefore **supersedes two 019 v1 rules**: [O-07]
> ("connect" = interested list only, no chat) and [O-04] (no source
> images). Everything else in 019 stands: same table, draft-first
> import, batch approval, feed cap, kill switch, no fake host.

---

## Goal

A listing feels like part of FunLynk, not a doorway out of it. A
signed-in user can say "I am going", see who else is going, and talk
to those people in a chat that lives on the listing, while the page
still says plainly that FunLynk does not run the event. And a listing
carries a real photo of the activity, stored in FunLynk's own
storage, credited to its source.

## Workstreams

- **A — Interaction** (detail in `02-interaction-design.md`):
  the interest signal becomes the attendance signal ("Going",
  [O-20-1]); a per-listing chat reuses the existing conversation
  machinery; the external Details link is demoted to a secondary
  text link on the detail page.
- **B — Images** (detail in `03-images-pipeline.md`): the importer
  downloads the researched image at import time and stores it
  through the same pipeline hosted activity images use. Hotlinking
  is rejected, with reasons. Import format v2 is in
  `04-import-format.md`, including the one-time backfill for the 18
  listings already imported.

## Decisions (owner, 2026-11-09)

- **[D]** People stay in the app on listings: mark going, see who is
  going, communicate in-app. The external link stays, secondary.
- **[D]** Research now includes images. The image pipeline is the
  most maintainable and stable option even if it is more
  cumbersome (this brief locks download-and-store; see
  `03-images-pipeline.md` for the reasoning).
- **[D]** The "FunLynk does not run this event" notice stays
  exactly as it is today (amber notice on the detail page).
- **[D]** Still OUT, unchanged from 019: tickets and payments for
  listings, claiming a listing, host tools for listings.

## Locked in this spec (not open questions)

- **Chat is reuse, not a new system.** One polymorphic conversation
  system already serves posts, activities, groups and DMs
  (`app/Models/Conversation.php`, `app/Services/ChatService.php`,
  `app/Livewire/Chat/ChatComponent.php`). A listing chat is an
  activity conversation whose 019 block is lifted under new rules.
- **The attendance signal is built on Interest, not RSVP.** The
  RSVP machinery is host, capacity, waitlist, payment and refund
  machinery (see archaeology in `01-archaeology-and-plan.md`).
  Listings have no host and no capacity; extending RSVP would drag
  all of that in for nothing. One signal, one row per user, on the
  existing `activity_interests` table.
- **Images are downloaded at import and stored in FunLynk's own
  storage**, in the `activities` directory on the `s3` disk,
  processed by `App\Services\ImageProcessor` exactly like hosted
  activity photos, and saved as paths in the existing `images`
  array. A source URL is never stored in `images` (the URL resolver
  passes full URLs through untouched, so a stored source URL would
  silently become a hotlink).
- **Image failure never fails an import.** A listing whose image
  cannot be fetched or validated imports imageless and is flagged
  in the import report, same as 019's reject-reporting style.
- **Kill switch, feed cap, approval flow unchanged.**
  `LISTINGS_ENABLED` off still makes listings (and now their chats)
  unreachable; the 2-per-10 feed cap still applies; import still
  creates drafts only and approval is still the admin's explicit,
  attributable, batch-capable act.

## Claims

Numbering continues 019's table (C-01..C-15 live there). Verdicts
are named binary tests, as in 019.

| ID | Capability | Verdict (named binary test) |
|---|---|---|
| C-20-01 | A signed-in user can mark and unmark Going on a published, current listing; one row per user; the count updates; drafts, expired listings and the kill switch refuse it | `test_going_toggle_rules` |
| C-20-02 | The who's-going list shows only users whose profile privacy allows this viewer (the C-15 rule, reused unchanged) | `test_whos_going_list_respects_privacy` |
| C-20-03 | A listing has exactly one conversation; a recurring series (shared `recurring_schedule_id`) shares one conversation across all its occurrence rows | `test_listing_chat_is_one_conversation_per_listing_and_series` |
| C-20-04 | Posting in a listing chat requires being signed in and marked Going; reading requires being signed in and able to view the listing; guests are refused both | `test_listing_chat_access_rules` |
| C-20-05 | Posting closes when the listing ends or is unpublished or the kill switch is off; the chat stays readable while the listing is published | `test_listing_chat_lifecycle` |
| C-20-06 | The listing detail page keeps the exact "FunLynk does not run this event" notice, and the source link renders as a secondary text link, not the primary button | `test_listing_detail_notice_and_secondary_source_link` |
| C-20-07 | Hosted-event chat behaves exactly as before (no regression from relaxing the listing block) | `test_hosted_activity_chat_unchanged` |
| C-20-08 | RSVP, ticket checkout and event needs still refuse listings (019 C-11, minus chat) | `test_hosted_only_actions_still_reject_listings` |
| C-20-09 | Reporting a listing-chat message creates a row in the existing `reports` table naming the message and its author | `test_report_listing_chat_message` |
| C-20-10 | Import with `image_url` stores a processed WebP under `activities/` on the uploads disk, puts its path in `images`, and records `image_source_url` | `test_import_stores_listing_image` |
| C-20-11 | A failed image (unreachable, non-https, wrong content type, over the size cap, undecodable) never fails the import: the listing imports imageless and the report flags it | `test_import_image_failure_never_fails_listing` |
| C-20-12 | Re-importing a duplicate row performs no image download (dedupe is checked before any fetch) | `test_reimport_does_not_redownload_image` |
| C-20-13 | The feed payload for a listing includes its stored image; an imageless listing still renders the date-strip placeholder on card and detail | `test_listing_image_renders_in_feed_and_detail` |
| C-20-14 | The detail page renders the photo credit in small print next to the source link when `image_source_url` is set | `test_listing_photo_credit_rendered` |
| C-20-15 | The images-only backfill fills `images`/`image_source_url` only on matched listings that have no image, never overwrites, never creates rows, and reports matched / filled / skipped / not-found | `test_backfill_images_only_fills_empty_listings` |

## Blueprint anchors

- [D] Authorization is server-side only: every new surface names
  its policy (Going: `ActivityPolicy::expressInterest`, extended;
  chat posting: `ActivityPolicy::chat`, redefined for listings;
  enforcement in `ChatService` and `ConversationPolicy`, never in
  Blade alone).
- [D] The timeline must feel instant: Going counts and viewer state
  ride the existing feed payload (`interest_count`,
  `viewer_interested` already ship); images add no queries (the
  `images` cast is already on the row).
- [D] Every screen wears the galaxy theme and passes the 390px
  gate; the changed listing detail layout and the card image get
  an approved single-file HTML mockup before build, as 019/018 did.
- [D] Mobile-first: the chat section, Going toggle and who's-going
  list are designed at phone width first; touch targets stay at
  the existing 44px minimum the detail page already uses.

## Production impact

The app is live, with 18 listings in the database (17 draft,
1 published). Changes are additive and backward-compatible: one
nullable column (`activities.image_source_url`), policy and service
changes behind the existing kill switch, one importer upgrade, one
new artisan command for the backfill. No existing row changes
meaning. Rollback: `LISTINGS_ENABLED=false` hides listings and
their chats; image columns can stay; stored images are ordinary
`activities/` objects. The chat relaxation ships with the claim
tests proving hosted chat is untouched (C-20-07).

## Security and privacy classification

- **Actors:** guest (no Going, no chat), user (Going, chat per
  rules), admin (approves, unpublishes, replaces images, receives
  reports), importer (Muse AI research, never publishes).
- **Data touched:** interest rows (relabelled Going), messages in
  listing conversations, reports rows, stored images, one new
  attribution column.
- **Authorization rules:** posting is gated on the Going mark;
  reading is gated on sign-in plus listing view; both enforced in
  policy/service, per the anchors above.
- **Privacy:** the who's-going list reuses the C-15 privacy filter
  verbatim (`User::allowsProfileContentFor`). Chat participation
  is visible to other participants by the nature of chat; the
  who's-going list and the chat roster are the same population
  (people who marked Going), so chat exposes no one the list
  would not.
- **Payment touchpoints:** none, still. Checkout refuses listings.
- **Other risks:** a public chat attached to a third party's event
  can attract spam or off-platform solicitation. Mitigations in
  scope: posting requires an account and a Going mark, the
  existing per-conversation mute applies, message reporting is
  wired to the existing `reports` table, and admin unpublish
  kills the listing and its chat together. Imported images are
  untrusted input: they are decoded and re-encoded by
  `ImageProcessor` before storage, never stored or served raw.

## Evidence and fixtures

`04-fixtures.json` (written at build time, mirroring 019) will
hold: a published listing with a stored image and credit; a
published listing without an image; a draft listing; an expired
listing with a chat history; a three-date recurring series sharing
one chat; a user marked Going and one not; a private-profile user
on the who's-going list; image fixtures (valid JPEG, PNG, a
non-image served as image, an oversize file, an http URL); a
duplicate re-import; a backfill file matching and missing rows.

## Pre-mortem

- **Likely failure:** relaxing the chat block in one place but not
  another (policy says yes, service still throws, or the reverse).
  019 blocked chat in two places on purpose (`ActivityPolicy::chat`
  and `ChatService::getOrCreateConversation`). **Mitigation:**
  C-20-04/C-20-07 test both directions through the service, not
  just the policy; the plan names both files.
- **Likely failure:** recurring occurrences each grow their own
  chat, splitting the conversation weekly. **Mitigation:** the
  series resolver in `02-interaction-design.md`; C-20-03 creates
  the chat from two different occurrence rows and asserts one
  conversation.
- **Assumption:** one attendance signal is enough (no separate
  "interested" tier). **Mitigation:** [O-20-1] is the owner's
  call at review; the build proceeds on the recommended default
  only after he confirms.
- **Risk:** source photos carry other people's rights.
  **Mitigation:** credit plus source link retained on the detail
  page, provenance URL stored, admin can unpublish or replace the
  image in Filament in under a minute. See the rights note in
  `03-images-pipeline.md`.
- **Unknown:** chat volume on popular listings. **Mitigation:**
  the chat reuses the existing pagination, search and notification
  machinery; no new infrastructure.

## Non-goals

- Tickets, payments, claiming, host tools for listings (future
  specs, unchanged from 019).
- A user-to-user blocking system. None exists anywhere in the app
  today; it is an app-wide gap, flagged in
  `01-archaeology-and-plan.md`, not something 020 invents for one
  surface.
- An admin moderation queue UI for reports (follow-up; 020 wires
  the report action into the existing table only).
- Hotlinking source images, in any field, ever (locked decision).
- Changing hosted-event RSVP, chat, images or detail pages.

## Approval gate

- [ ] Owner reviews this brief and the open decisions
  ([O-20-1]..[O-20-8] in `01-archaeology-and-plan.md`)
- [ ] Mockup of the revised listing detail + card approved
  (galaxy theme, 390px)
- [ ] Claim tests C-20-01..C-20-15 agreed as the build's verdicts
- [ ] Production impact + rollback plan above accepted
