# 020 — Build notes

Built 2026-11-09 on `feat/020-listing-interaction-images` (from the
spec branch `spec/020-listing-interaction-images`, 037cd62), following
`00-brief.md` through `04-import-format.md` as written, with all eight
open decisions on the owner's approved defaults ([O-20-1]..[O-20-8]).
Every claim test passes as named.

## Baseline and result

- Baseline before the build (main at 134cd4d): 436 passed / 2 skipped.
- Final suite on the finished branch: **451 passed / 2 skipped /
  0 failed** (436 + the 15 new claim tests in
  `tests/Feature/Listings/ListingInteractionImagesTest.php`). The two
  019 tests the spec supersedes were updated in place:
  `test_listing_card_has_no_host_actions` (Going copy, demoted source
  link) and `test_hosted_only_actions_reject_listings` (chat refusal
  moved out; it is now proved by C-20-04/C-20-08 in the 020 file).
- Pint PASS on all touched files; PHPStan level 7 clean on all
  touched files, baseline untouched.
- Migrations run against the real database (additive only):
  `image_source_url` column added; reports check constraint relaxed
  (see below). Real-DB counts under the safe test protocol,
  before and after the full suite: **activities 18 (8 draft,
  10 published), users 1 — unchanged.** (The 8/10 draft/published
  split reflects the owner approving more listings in Filament after
  the 019 snapshot of 17/1; the total is the invariant and it held.)

## What shipped

- **Going (A1):** copy-only relabel on the existing interest
  machinery. Toggle reads "Going" / "✓ You're going", detail list
  heading is "Who's going", card footer counts "N going". No schema
  change; feed payload keys unchanged.
- **Chat (A2):** `ActivityPolicy::chat` redefined for listings
  (signed in + listings on + a published, current listing in the
  series + a Going mark on any series row). `ChatService` listing
  throw replaced by a series-aware resolver: one conversation per
  series, refusing unpublished listings and the kill switch.
  `ConversationPolicy::sendMessage` and `ChatService::sendMessage`
  both enforce the Going gate for listing subjects. The existing
  ChatComponent is embedded on the listing detail for signed-in
  users on published listings; guests get the sign-in prompt;
  non-Going users see "Mark Going to join the chat"; an ended
  listing shows "This event has ended" and stays readable.
- **Notice and source link (A3):** the amber notice is byte-for-byte
  as shipped in 019 (asserted in C-20-06). The detail source link is
  now a small text link in a footer block ("Details at {source} ↗")
  with the photo credit beside it; the card's external link shrank
  to the same small text form under the source row, and the Going
  toggle is the card's action.
- **Moderation:** a "Report message" action in the chat message
  sheet, listing chats only, creating `Report` rows via
  `ChatComponent::reportMessage`. Per-conversation mute and admin
  unpublish are the existing tools, unchanged. No reports admin UI,
  no user blocking (follow-up specs, per the brief).
- **Images (B):** new `ListingImageService` (https only, 10s
  timeout, content type jpeg/png/webp/gif, cap
  `listings.image_max_bytes` default 10 MB, then
  `ImageProcessor::store($bytes, 'activities', 'cover', 's3')`;
  undecodable bytes are an image failure). The importer runs the
  image step after dedupe and before create, stores the path in
  `images[0]` and the URL in the new `image_source_url` column, and
  reports per-row failures plus `Images: N stored, M failed.`
  counters. The FeedService listing carve-out is removed, so feed
  payloads ship listing images; the detail page renders `images[0]`
  with the date strip over it and keeps the 019 placeholder when
  empty. New `listings:import-images {path}` backfill command:
  fills only empty listings matched on the dedupe triple, never
  creates, never overwrites, never downloads for a filled listing;
  re-running is a no-op (proved by C-20-15). **The backfill was NOT
  run against the real database** — the research file with image
  URLs for the 18 existing listings is a separate content step.

## Bugs found by the claim tests (not in the archaeology)

- **Reports constraint vs. the plan.** The original
  `report_target_check` allows exactly ONE target column, so the
  plan's report row (`reported_message_id` + `reported_user_id`)
  could never be stored. Migration
  `2026_11_09_000003_allow_message_author_reports` relaxes the
  check to admit exactly the sets {user}, {activity}, {message},
  {message + its author} and nothing else. C-20-09 proves the row
  the brief asks for.
- **Guest redirect loop on listings.** Guests hitting
  `events.show` were 301'd by `RedirectGuestsToPublicEvent` to the
  public slug page, which (since 019) redirects listings straight
  back to `events.show` — a loop, and it made the spec's guest
  surface (who's-going list + sign-in prompt on the detail page)
  unreachable. The middleware now passes guests through for
  listings only; hosted events keep the canonical-slug redirect.
  Proved by the guest assertions in C-20-04.

## Not done (by design, per the brief)

Tickets/payments, claiming, host tools, a reports admin UI, and a
user blocking system remain out. The images backfill for the 18
existing listings was not run (see above). Production verification
of a live listing chat session and of the detail page with a real
stored photo needs the owner's own use: there is no production
admin session available to the builder, and no listing has a stored
image until the backfill content step lands.

## Fix 2026-11-09: the listing chat join path (branch fix/020-chat-join-cta)

Live-use report from the owner, signed in on a published listing: the
page said "Mark Going to join the chat." and he could not chat at all.

**Root cause.** The design (02-interaction-design.md) assumed "the
toggle already live-updates" the chat. It did not. The Going toggle
(`InterestToggle`) and the chat (`ChatComponent`) are separate
Livewire components; the toggle dispatched nothing, the chat had no
listener, and the parent page never re-rendered, so the chat's
`canSend` flag stayed at its page-load value (false) and the composer
never appeared until a manual page reload. The policy and service
gates themselves were correct and series-aware (verified: the Going
check in `ActivityPolicy::chat` spans the series, and the series
conversation resolver was already consistent with it); the failure
was purely that nothing on the page reacted to the Going change.

**What changed.**
- `ChatComponent::joinChat()`: the not-Going state of a listing chat
  now shows the plain line "People who are going can chat here." and
  a **Join the chat** button (new view vars `canJoinChat` /
  `listingEnded`). One tap marks Going on the viewed occurrence via
  `ListingInterestService` (the same row the page toggle uses) and
  the same response re-renders with the composer unlocked. The ended
  state reads "This event has ended." and Join is a no-op there.
- A `going-changed` event now ties the page together:
  `InterestToggle` dispatches it after every toggle; `ChatComponent`
  listens (`onGoingChanged`, series-filtered) and reloads;
  `ActivityDetail` listens and re-renders (who's-going list, count);
  `InterestToggle` also listens and resyncs when the chat's Join
  button did the marking. Un-marking Going re-locks the chat the
  same way. The O-20-2 rule is unchanged: posting still requires
  the Going mark, enforced exactly where it was.
- Copy: "Mark Going to join the chat." is removed from the listing
  detail page, and the `ChatService` backstop exception now reads
  "Join the chat to post a message." This supersedes the CTA wording
  in 02-interaction-design.md. One C-20-04 assertion was updated to
  the new copy; the claim's substance (non-Going users are shown how
  to join; posting still requires Going) is unchanged.

**Tests.** New `tests/Feature/Listings/ListingChatJoinFlowTest.php`
(5 tests, all failed on the pre-fix code): Join button marks Going
and unlocks posting with no reload or remount; the toggle's event
unlocks an already-mounted chat; a recurring series where the
conversation hangs on the second occurrence and the user joins from
the first; the ended listing (ended state, Join no-op); the detail
page copy for guests and signed-in users. Full suite: **456 passed /
2 skipped / 0 failed** (451 baseline + 5 new); all claim tests
C-20-01..15 pass; Pint PASS; PHPStan level 7 clean. Real-DB counts
under the safe test protocol, before and after: **activities 18,
users 1, unchanged.**

**Not verified here:** the live tap-through in a production admin
session (tap Join the chat, post) still needs the owner's own use;
the component-level path is proved by the tests above.

**Addendum to the fix (same branch, 2026-11-09).** Two more items
rode along: (1) `ListingImageService` no longer relabels every
`ImageProcessor::store()` failure as "image could not be decoded".
Only Intervention's `DecoderException` keeps the decode wording;
any other throwable (storage, S3 configuration, encode) surfaces as
`image store failed (<class>: <message>)` in the import and backfill
output, which both print the exception message. This mislabel hid a
missing S3 region behind six bogus decode failures in the production
backfill run earlier today. Covered by
`tests/Feature/Listings/ListingImageServiceTest.php` (storage failure
surfaces its real message; genuine decode failure still reads as a
decode failure). No claim test asserted the old blanket text, so no
claim test changed for this. (2) The images backfill run file for
the existing listings, `storage/listings/images-backfill-run-2026-11-09.json`
(6 rows), is committed with this branch so it deploys to Laravel
Cloud with the merge, the same pattern as the 019 import batch; the
owner runs it from the Laravel Cloud console.
