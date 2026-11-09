# 020 — Archaeology results and implementation plan

Written 2026-11-09 by Lotus, from the live code on `main`
(merge `134cd4d`), before any 020 code exists. File and line
references are to that tree.

## Archaeology

### (a) Listing interest — the base for "Going"

- Table `activity_interests` (migration
  `2026_11_09_000001_add_listing_fields_to_activities_table.php`):
  uuid id, `activity_id` / `user_id` FKs cascade, timestamps,
  `unique(activity_id, user_id)`. Model
  `app/Models/ActivityInterest.php`. Deliberately distinct from
  `EventInterest` (guest engagement on hosted events), which 020
  does not touch.
- Service `app/Services/ListingInterestService.php` owns toggle
  semantics (one row per user; toggle off deletes the row), count,
  `isInterested`, and `visibleInterestedUsers` — the C-15 privacy
  filter, built on `User::allowsProfileContentFor($viewer)`
  (`app/Models/User.php` L408: public profiles visible to all,
  followers/friends only to followers, private to nobody, self
  always).
- Policy gate `ActivityPolicy::expressInterest` (L255): listing +
  `config('listings.enabled')` + status `published` +
  `Activity::isCurrentListing()` (not ended; not started when no
  end time).
- UI: `app/Livewire/Listings/InterestToggle.php`, state arrives
  from the feed payload with no query on render; used on the card
  (`resources/views/components/event-card-compact.blade.php`
  L165) and the detail page
  (`resources/views/livewire/activities/listing-detail.blade.php`,
  inside `@auth`). Feed payload already carries `interest_count`
  and `viewer_interested` (`app/Services/FeedService.php` L761-762).
- The detail surface is its own Blade
  (`listing-detail.blade.php`) rendered by
  `ActivityDetail::render()` (L309) with `interestedUsers`,
  `interestCount`, `viewerInterested`. The amber notice text
  today, verbatim: "FunLynk does not run this event. It is listed
  here so you can see what is on around town. Details, tickets
  and changes are handled by the organizer."

**Verdict:** the interest machinery is complete and already
privacy-correct. "Going" is a relabel and presentation change on
this machinery, plus a list heading change. No schema change, no
new service.

### (b) Hosted RSVP — do not extend

- `app/Models/Rsvp.php` carries statuses (attending / waitlist
  seen in `RsvpButton`), `is_paid`, `payment_amount`, a
  `Transaction` relation, `checked_in_at`, refund-window change
  responses, and model-boot hooks that release event needs when
  an attending RSVP is withdrawn. `current_attendees` is a
  denormalized counter on the activity; capacity and waitlist
  hang off `max_attendees`.
- The public hosted detail page shows only a count ("N
  attending"); named attendee lists are host-side tools
  (`HostAttendeeManager`, `QrScanner`). There is no public
  who's-going list for hosted events to copy — the pattern to
  copy is the listing interested list (a).

**Verdict:** RSVP is host/capacity/money machinery. Listings have
no host, no capacity (the importer never sets `max_attendees`),
and payments are out of scope by owner decision. Extending RSVP
to listings is rejected; the single attendance signal lives on
`activity_interests` (a).

### (c) Chat — one system, blocked for listings in exactly two places

- Model: `app/Models/Conversation.php` — polymorphic
  `conversationable` (Post or Activity), `type` in
  public/group/private, `group_id` for group chats, soft deletes,
  `metadata` array, `pinned_message_id`. Messages:
  `app/Models/Message.php` plus `MessageReaction`; participants
  through `conversation_participants` pivot (role, is_muted,
  last_read_at, request_status, notify_level).
- Creation: `ChatService::getOrCreateConversation(Model)` returns
  the morphOne conversation or creates it (Activity → type
  `group`). DMs are a separate method (type `private`,
  conversationable null, message-request routing for non-mutuals).
  Groups use their own `GroupChatService::getOrCreateGroupChat`
  on `conversations.group_id` — not the path listings take.
- UI: one component, `app/Livewire/Chat/ChatComponent.php`,
  embedded three ways today: by conversationable for posts
  (`post-chat.blade.php`) and hosted activities
  (`activity-detail.blade.php` L475:
  `<livewire:chat.chat-component :conversationable="$activity" />`),
  and by conversation id for DMs and group chats. It authorizes
  through `ConversationPolicy` (`view`, `sendMessage`, `pin`).
  Sending auto-adds the sender as a participant
  (`ChatService::sendMessage`), broadcasts `MessageSent` (Reverb),
  and dispatches `NotifyChatParticipants` for non-private chats.
  Reactions, replies, mentions (participants only), search,
  pinning, mute and notify levels all exist already.
- Policy shape: `ConversationPolicy` delegates Activity-subject
  view/send to `Gate::allows('view', $activity)`. Pinning
  delegates to `update` on the activity — for a listing that is
  listings-admin only, which 020 keeps as-is.
- **The 019 blocks (C-11):** `ActivityPolicy::chat()` returns
  `! $activity->isListing()` (L246), and
  `ChatService::getOrCreateConversation` throws
  `AuthorizationException('Chat is not available for listings.')`
  for a listing (L19). `ConversationPolicy` itself has no listing
  block; it inherits whatever the two above decide.
- **Moderation reality check:** the `reports` table and
  `app/Models/Report.php` are fully modeled (reporter,
  reported_user / reported_activity / reported_message /
  reported_post, reason, status pending/reviewed/resolved/
  dismissed, reviewed_by/at) but **no code anywhere creates a
  Report and there is no admin UI for reports**. A user-to-user
  **block feature does not exist anywhere in the app**; the only
  per-user safety controls that exist are per-conversation mute
  and notify levels. 020 scopes moderation accordingly (see plan
  step 4); it does not pretend report/block tooling is finished.

**Verdict:** listing chat = lift the two blocks under new policy
rules and embed the same component on the listing detail page.
No new tables, no new component, no parallel system.

### (d) Hosted activity images — the convention listings join

- Storage path: `CreateActivity.php` L143 and
  `EditActivity.php` L202 both call
  `ImageProcessor::store($image, 'activities', 'cover', 's3')`.
  `app/Services/ImageProcessor.php`: Intervention decode, EXIF
  auto-rotate, `scaleDown` to the preset (`cover` = 1600x900, no
  crop), WebP re-encode at quality 82, path
  `activities/<uuid>.webp` on the given disk. Hosted uploads land
  on the **s3 disk** (the app's uploads disk).
- Model: `activities.images` is an `array` cast
  (`app/Models/Activity.php` L63) of path strings; `images[0]`
  is the cover. Rendering resolves paths through
  `ImageProcessor::url()`, which returns full URLs and
  root-relative paths **as-is** and everything else as an s3 URL.
  (That pass-through is why a source URL must never be written
  into `images`: it would render as a silent hotlink.)
- Rendering: hosted detail renders a carousel from `images`
  (`activity-detail.blade.php` L245+). The feed payload maps
  `images` straight from the row — **except listings**:
  `FeedService.php` L745 hardcodes
  `'images' => $event->isListing() ? [] : ($event->images ?? [])`.
  That carve-out, not storage, is why listings show no image
  today even if a row had one.
- Listing detail today renders a placeholder block (aspect-video,
  calendar glyph, date strip over gradient) in
  `listing-detail.blade.php`, per 019 [O-04].
- Admin: the Filament activity form already has an `images`
  FileUpload (directory `activities`,
  `app/Filament/Resources/Activities/Schemas/ActivityForm.php`
  L71), so an admin can replace or clear a listing's image by
  hand. That is the takedown/replace path for workstream B.

**Verdict:** importer stores through `ImageProcessor` with the
exact hosted arguments (`'activities'`, `'cover'`, `'s3'`) into
`images[0]`. Rendering changes are two small removals: the
FeedService carve-out and the detail placeholder (kept as the
fallback when `images` is empty).

### (e) The listings importer

- `app/Console/Commands/ImportListingsCommand.php`
  (`listings:import {path}`): strict per-row validation, dedupe
  on (source_url, title, start_time) checked **before** create,
  creates kind=external / status=draft rows with
  `'images' => []` hardcoded, and reports imported / duplicates
  skipped / rejected with one reason line per rejected row. It
  can never publish. Format: 019's `04-import-format.md`.
- Because dedupe is checked before creation, ordering the image
  download after the dedupe check gives C-20-12 (no re-download
  on duplicate) for free, as long as the builder keeps that
  order.

**Verdict:** workstream B is a contained change to this one
command plus one nullable column (`image_source_url`) plus the
two rendering removals in (d). The backfill is a second small
command keyed on the same dedupe triple (see
`03-images-pipeline.md`).

### (f) 019 hosted-only guards — disposition under 020

| # | Guard (location) | 020 disposition |
|---|---|---|
| 1 | `ActivityPolicy::rsvp` → false for listings (L230) | STAYS. Proved by C-20-08 |
| 2 | `ActivityPolicy::checkout` → false for listings (L238); checkout route 403 in `test_hosted_only_actions_reject_listings` | STAYS. C-20-08 |
| 3 | `ActivityPolicy::chat` → false for listings (L246) | **RELAXED**: redefined for listings in `02-interaction-design.md` (signed-in + Going + published/current for posting) |
| 4 | `ChatService::getOrCreateConversation` throws for listings (L19) | **RELAXED**: replaced by the listing resolver (single conversation; series-shared) |
| 5 | `ActivityPolicy::manageNeeds` / `claimNeeds` → false for listings (L185/L198); `EventNeedService::isOpen` false for listings | STAYS. C-20-08 |
| 6 | `RsvpService::createRsvp` refuses listings (via policy 1) | STAYS. C-20-08 |
| 7 | `ActivityPolicy::expressInterest` — listings only, published + enabled + current (L255) | EXTENDED, rules unchanged: it becomes the Going gate and the chat-posting gate |
| 8 | `ActivityDetail::mount` 404 for unpublished/disabled listings (L79); listing detail surface (L309) | UNCHANGED rules; the surface gains chat + image + demoted source link |
| 9 | Feed `images` strip for listings (FeedService L745) | **REMOVED** by workstream B. C-20-13 |
| 10 | Filament: host select disabled for listings; source fields listing-only | UNCHANGED; the existing images FileUpload becomes the admin image-replace path |
| 11 | `PublicEventView` slug page redirects listings to the canonical detail | UNCHANGED |

## Decisions applied (locked, from the brief)

- Download-and-store for images, via `ImageProcessor`, s3 disk,
  `activities` directory, `images[0]`. Hotlink rejected.
- Attribution in a new nullable column
  `activities.image_source_url` (mirrors the existing
  `source_name` / `source_url` pair; the `images` array stays an
  array of path strings, matching hosted rows byte-for-byte in
  shape). Credit renders in small print on the detail page next
  to the demoted source link.
- One attendance signal on `activity_interests`; no RSVP for
  listings, no new table.
- Chat on the existing conversation system; one conversation per
  listing, shared across a recurring series.

## Open decisions (owner, at review)

Each has a recommended default. The build proceeds on the
defaults for any item the owner does not override, as on 019.

- **[O-20-1] Attendance signal label and shape.** (a) Relabel
  Interest to **"Going"** everywhere on listings (button, counts,
  "Who's going" list), one signal, no schema change —
  **recommended**: it says what the owner asked for ("mark
  they're going") and a single strong signal keeps the count
  meaningful. (b) Keep the "Interested" label, add the list and
  chat only. (c) Two tiers, Interested + Going (extra state on
  `activity_interests`, split counts) — not recommended: splits
  the signal and the chat population.
- **[O-20-2] Chat posting gate.** Require the Going mark to post
  (**recommended**, per the owner's direction); any signed-in
  user can read. Alternative: any signed-in user can post.
- **[O-20-3] Chat after the event.** Posting closes when the
  listing stops being current (ends, or starts with no end time),
  matching the Going gate; the thread stays readable while the
  listing is published (**recommended**). Alternative: a 48-hour
  posting tail for "how was it" messages.
- **[O-20-4] Guest access to chat.** Signed-in only: guests see
  the who's-going list and a sign-in prompt where the chat sits
  (**recommended**, consistent with the `@auth` interest toggle
  today). Alternative: guests can read the chat.
- **[O-20-5] Image caps.** Download cap **10 MB**, content types
  image/jpeg, image/png, image/webp, image/gif (anything else,
  including SVG, is an image failure, never a row failure);
  stored output is always the `cover` preset WebP
  (**recommended**; the cap is a config value
  `listings.image_max_bytes`).
- **[O-20-6] Backfill the 18.** Run the images-only backfill for
  the already-imported 18 listings right after workstream B
  ships (**recommended**): one command run with a research file
  keyed on (source_url, title, start_time); the published Run
  Club listing gets its photo without waiting for its next
  import cycle. Alternative: images only for future imports.
- **[O-20-7] Moderation depth in 020.** Wire the report-message
  action into the existing `reports` table + existing mute +
  admin unpublish (**recommended**); a Filament reports queue
  and user blocking are follow-up specs. Alternative: build the
  reports queue in 020 too.
- **[O-20-8] Card source link.** On the card, the external link
  shrinks to a small text link ("at {source_name} ↗") under the
  source row, with the Going toggle as the card's action
  (**recommended**). Alternative: no external link on the card
  at all; detail page only.

## Build plan (branch `feat/020-listing-interaction-images`)

0. **Mockup first** (brief's approval gate): single-file HTML
   mockup of the revised listing detail (image + credit, notice,
   Going, who's-going, chat, demoted source link) and the card
   with an image, galaxy theme, checked at 390px.
1. **Migration** (backward-compatible): add nullable
   `activities.image_source_url` string. Nothing else. No data
   backfill in the migration (the backfill command in step 5
   owns data).
2. **Going** (workstream A1): relabel Interest → Going on the
   listing card and detail (component copy, list heading "Who's
   going"), per [O-20-1] default. `ListingInterestService`,
   `ActivityPolicy::expressInterest` and the table are untouched
   in behavior; feed payload keys stay `interest_count` /
   `viewer_interested` (internal names, not user copy).
3. **Chat** (workstream A2): redefine `ActivityPolicy::chat` for
   listings; replace the `ChatService` listing throw with the
   series-aware resolver; enforce the posting gate in
   `ConversationPolicy::sendMessage` for listing subjects; embed
   `<livewire:chat.chat-component>` on the listing detail under
   the access rules in `02-interaction-design.md`; demote the
   detail source link to the secondary block (with the photo
   credit beside it, step 4 permitting). Hosted paths verified
   unchanged by C-20-07.
4. **Moderation wiring**: report action on messages in a listing
   chat, creating `Report` rows (`reported_message_id`,
   `reported_user_id`, reason); nothing else about reports
   changes.
5. **Images** (workstream B): importer v2 per
   `04-import-format.md` (validate → dedupe → download → store →
   create; image failure flags and continues); remove the
   FeedService L745 carve-out; listing detail renders `images[0]`
   with the date strip over it when present, placeholder when
   not, credit line when `image_source_url` is set; new command
   `listings:import-images {path}` for the backfill.
6. **Tests**: Pest tests named exactly as the brief's claims
   (C-20-01..C-20-15), in `tests/Feature/Listings/`. 019's
   `test_hosted_only_actions_reject_listings` is updated where
   it asserts chat refusal (it becomes the C-20-08 test minus
   chat, plus C-20-04 asserting the new rules). Full suite stays
   green against the current baseline (436 passed / 2 skipped)
   plus the new tests. Pint clean; PHPStan level 7 clean for
   touched files.
7. **Build notes**: `05-build-notes.md` is written during the
   build, as on 019, including the before/after real-database
   counts under the safe test protocol.

## Rollout

Steps 2–5 ship on one branch behind the existing kill switch:
with `LISTINGS_ENABLED=false` nothing in 020 is reachable, so
merge order is safe. The backfill (step 5) runs once, by hand,
after merge, and only fills empty image fields. Rollback:
kill switch off; or revert the branch — the one nullable column
and any stored `activities/` images can stay harmlessly.

## Out of scope (unchanged from the brief)

Tickets/payments, claiming, host tools, a reports admin UI, a
user blocking system, hosted-event changes of any kind.
