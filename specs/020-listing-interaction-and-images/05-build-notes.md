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
