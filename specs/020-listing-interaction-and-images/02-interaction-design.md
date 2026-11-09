# 020 — Workstream A: interaction on external listings

Detail design for the brief's workstream A. All file references
are to `main` at merge `134cd4d`; the archaeology behind every
statement is in `01-archaeology-and-plan.md` section (a), (b),
(c) and (f).

## A1. The attendance signal: "Going"

One signal, built on the shipped interest machinery. Nothing
about the data changes:

- Table: `activity_interests` (unchanged). One row per
  (activity, user); marking creates the row, unmarking deletes
  it (`ListingInterestService::toggle`).
- Gate: `ActivityPolicy::expressInterest`, unchanged — listing,
  `LISTINGS_ENABLED`, status `published`, `isCurrentListing()`.
  A draft, an expired listing, or the kill switch refuses the
  toggle, and the component already resyncs quietly instead of
  erroring the page (`InterestToggle::toggle`).
- Copy changes only (per [O-20-1] default): the toggle reads
  **"Going"** (state: "You're going"), the count reads
  "N going", and the detail list heading reads **"Who's going"**.
  Internal names (`ListingInterestService`, `interest_count`,
  `viewer_interested`, the table) stay as they are; they are not
  user copy, and renaming them would churn working code for
  nothing.
- Card: the toggle stays where it is on
  `event-card-compact.blade.php` (it already ships there with
  count and viewer state from the feed payload, so no new feed
  queries). The card's footer count line follows the same copy.
- Detail: the toggle stays in the action row of
  `listing-detail.blade.php`; the list below it is
  `ListingInterestService::visibleInterestedUsers`, which is the
  C-15 privacy filter (`User::allowsProfileContentFor`): a
  private profile never appears to a stranger, a followers-only
  profile appears only to its followers, and a user always sees
  themselves. C-20-02 re-proves this under the new heading.
- Guests: the toggle stays behind `@auth`, as today. Guests see
  the count and the who's-going list (privacy-filtered with a
  null viewer, exactly as `visibleInterestedUsers` already
  handles).

What "Going" is not: not an RSVP (no capacity, no waitlist, no
check-in, no ticket), not a promise to the organizer (FunLynk
does not run the event, and the notice says so), and not visible
to the organizer anywhere. It is a signal between FunLynk users.

## A2. Per-listing chat

### Machinery (all existing, all reused)

- Conversation: the listing's morphOne `Conversation`
  (type `group`, as for every activity), created lazily by
  `ChatService::getOrCreateConversation` on first view of the
  chat section. No schema change.
- UI: `<livewire:chat.chat-component :conversationable="$activity" />`
  embedded on `listing-detail.blade.php`, the same embed the
  hosted detail page uses (`activity-detail.blade.php` L475).
  Reactions, replies, mentions, search, mute, notify levels,
  read markers and Reverb live updates come with it.
- Notifications: `NotifyChatParticipants` already fires for
  non-private conversations; no new notification code.

### One chat per listing, and per series

A listing row is one occurrence. Listings that share a
`recurring_schedule_id` are one series (019 collapses them in
the feed to the next occurrence). The chat belongs to the
series, not to an occurrence:

- Resolver (replaces the 019 throw in
  `ChatService::getOrCreateConversation` for listings): if the
  listing has a `recurring_schedule_id`, look for an existing
  conversation whose `conversationable` is any activity row in
  the series; return it if found. Otherwise create the
  conversation attached to the listing being viewed.
- With no `recurring_schedule_id`, the plain morphOne behavior
  applies (one conversation for the row).
- Consequence: the chat survives the weekly roll-over. When
  this week's occurrence expires and the detail page starts
  serving next week's row, the same conversation renders, with
  its history. C-20-03 opens the chat from two different
  occurrence rows and asserts a single conversation and shared
  history.
- The expired occurrence's own detail page 404s for
  non-admins as today (019 expiry rules unchanged); the chat
  is reached through the current occurrence.

### Access rules

Enforced server-side, in policy and service, never in Blade
alone:

- **Read** (render the chat section, load messages): signed-in
  user who can view the listing (`ActivityPolicy::view` — in
  practice a published listing while the feature is enabled;
  admins reviewing a draft do not get a chat, the detail mount
  rules are unchanged). Guests do not see the chat section;
  they see a one-line prompt in its place ("Sign in to see the
  chat") — [O-20-4] default.
- **Post**: read access **plus** a Going mark on the listing
  (an `activity_interests` row for this user and *any* row of
  the series — marking Going on the current occurrence is the
  normal path; the series check keeps a regular's access when
  the occurrence rolls over). Implemented as the redefined
  `ActivityPolicy::chat` for listings, and enforced in
  `ConversationPolicy::sendMessage` when the subject is a
  listing, because the component authorizes through
  `ConversationPolicy`, not `ActivityPolicy`. `ChatService`
  is the third enforcement point for non-component callers,
  mirroring how 019 layered its guards.
- **Not marked Going**: the chat renders read-only with a
  single call to action in place of the composer: "Mark Going
  to join the chat", wired to the same toggle. Marking Going
  unlocks the composer without a page reload (the toggle
  already live-updates).
- **Unmarking Going after posting**: posting access is lost
  immediately; messages the user already sent stay (they are
  history, and other people may have replied to them); read
  access is unaffected. Their participant row and mute/notify
  preferences are untouched, so re-marking restores exactly
  where they left off.
- **Pinning**: `ConversationPolicy::pin` delegates to
  `update` on the activity, which for a listing is
  listings-admin only. In a listing chat, pinning is therefore
  an admin act. Kept as-is deliberately: there is no host to
  pin, and an admin pin is the takedown-adjacent tool.

### Lifecycle

- **Posting window**: while the listing is current, by the
  same `isCurrentListing()` rule as the Going gate (published,
  not ended; not started when it has no end time). When the
  window closes, the composer is replaced by "This event has
  ended" and the thread stays readable while the listing is
  published — [O-20-3] default.
- **Unpublish**: `ActivityDetail` already 404s the listing for
  non-admins; the chat is unreachable with it, and
  `getOrCreateConversation` must refuse an unpublished listing
  so no back door remains (C-20-05).
- **Kill switch**: `LISTINGS_ENABLED=false` 404s the detail
  page as today; the resolver likewise refuses, so the chat
  dies with the listing, per the brief's locked rule.
- **Recurring roll-over**: no lifecycle event fires on roll-
  over; the resolver simply finds the series conversation from
  the new occurrence (above).

### Empty states

- Nobody has marked Going: the who's-going section stays
  hidden (current behavior when the list is empty), and the
  chat section shows its explainer line only (below).
- Chat with no messages: the component's own empty thread
  state, preceded on the listing page by one explainer line
  above the chat: "Chat with others going to this event.
  FunLynk does not run this event; the organizer's page is
  linked below." A user who has not marked Going sees the
  explainer plus the "Mark Going to join the chat" action.
- A listing whose chat was never opened has no conversation
  row until the first signed-in view creates it (lazy
  creation, same as hosted activities today).

## A3. The notice and the demoted source link

- The amber notice stays **byte-for-byte** as shipped:
  "FunLynk does not run this event. It is listed here so you
  can see what is on around town. Details, tickets and changes
  are handled by the organizer." Same position (above the
  action row), same styling. C-20-06 asserts the string.
- The source link moves out of the primary button row. New
  home: a quiet footer block at the bottom of the detail
  card, small text, reading "Details at {source_name} ↗"
  (same `target="_blank"`, `rel="noopener noreferrer"` rules
  as 019 C-08), with the photo credit line beside it when the
  listing has one (workstream B). The primary action row holds
  Going (and, through scroll position, the chat) instead.
- Card: per [O-20-8] default, the card's external link shrinks
  to the same small text form under the source row. The card's
  tap target remains the in-app detail page, as today.

## What this workstream does not do

- No tickets, no payments, no claiming, no host tools
  (C-20-08 proves RSVP/checkout/needs still refuse listings).
- No DM changes; messaging a specific person from the
  who's-going list goes through their profile and the existing
  DM/message-request rules, untouched.
- No change to hosted activity chat, post chat, group chat or
  DM behavior (C-20-07).
