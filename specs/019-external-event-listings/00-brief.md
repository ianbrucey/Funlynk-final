# 019 — External event listings ("Around town") — Strategic brief

**Status:** DRAFT
**Author:** Augment Agent (orchestrator draft)
**Date:** 2026-11-09

> The spec number 019 is a placeholder; the decided roadmap (BUILD_PROTOCOL §7) ends at 005.
> Origin: owner brainstorm 2026-11-09. Muse AI researches events happening around the city; FunLynk lists the ones it does not host, so the site has content and people see what is on nearby.
> Blueprint anchors below are NOT yet linked: `PRODUCT_BLUEPRINT.md` was not read for this draft. The planner fills them in before review.

---

## Goal

Someone scrolling the timeline sees real events happening around their city that nobody on FunLynk is hosting, clearly labelled as listings, with a link to the organizer's own page.

## Decisions (owner, 2026-11-09)

- **[D]** Listings are **not hosted by FunLynk**. There is no fake admin host.
- **[D]** Listings appear in the timeline alongside normal posts and events.
- **[D]** Imports are researched by Muse AI, then reviewed by a human before they appear (draft-first, like `poster:intake`).
- **[D] 2026-11-09** Interest stays **in the app**: "I'm interested" ships in the first version, because people want to see who is coming and connect.
- **[D] 2026-11-09** Claiming is **not** in the first version.
- **[D] 2026-11-09** The normal event radius applies to listings; they all have locations.
- **[D] 2026-11-09** The owner approves listings, and approval is **by batch**.

## Archaeology (what exists today)

Read from the live `funlynk` database schema (columns only, no row data) and from code:

- `activities.host_id` is `uuid NOT NULL`, foreign key to `users` with `ON DELETE CASCADE`. A listing cannot exist without a host today.
- `activities` has no source, source URL, source name or claimed flag. It does have `is_public`, `status`, `slug` (unique, optional), `cover_image_url` and `images`.
- Location is required and already spatial: `location_coordinates` (PostGIS, NOT NULL), `location_name` (NOT NULL), `location_address` (optional). Feed, map and search run on this.
- Other required fields an import must fill: `description` (NOT NULL), `payment_type` (NOT NULL). Check `valid_price`: `free` needs `price_cents` NULL; `online` or `at_door` needs `price_cents` above 0. Check `valid_times`: `end_time` is NULL or after `start_time`.
- Recurring events exist: `recurring_schedule_id` and `recurrence_date` link to `recurring_schedules`.
- `FeedService::mapEventToFeedItem` reads `$event->host->display_name`, `->username`, `->profile_image_url` and `->username` without a null check. A listing with no host would crash the feed today.
- The event card shows "Follow host", an RSVP button and an Invite button. Event detail, needs list (spec 014), chat and tickets also assume a host.

**To confirm in the archaeology step (not yet checked):** every place that reads `$activity->host` or `host_id`, including policies, notifications, Filament, search indexing, the map and the event detail page.

## Proposed behaviour [P]

- **Same table, new kind.** Add a source to events with two values, `hosted` (default, every existing row) and `external`. Make `host_id` nullable, enforced by a database check: hosted events require a host; external listings must not have one.
- **External listing fields:** source name, source URL, and a "listed by FunLynk" label. Time, place and description use the existing columns.
- **Card:** a distinct "Around town" label in place of the host row. The main button is "Details ↗" to the source URL (opens in a new tab, `rel="noopener noreferrer"`). No RSVP, no Invite, no Follow host, no price chip unless the price is known.
- **Detail page:** shows the same fields, a notice that FunLynk does not run the event, and the source link. No tickets, needs list or chat.
- **Interest:** an "I'm interested" button with a count and the list of people who tapped it, shown according to each person's profile privacy. It carries no ticket, no capacity and no host. What "connect" means beyond seeing names is [O-07].
- **Feed mix:** external listings are capped per page of the timeline so they never crowd out posts and events. The cap is a config value, default decided at planning ([O-01]).
- **Expiry:** a listing disappears from the feed once it has started or ended (rule decided at planning).
- **Intake:** imports from Muse AI are created as drafts and never visible until a human approves them in an admin review screen. Approving is an explicit, attributable act (who and when).
- **Dedupe:** the same source URL plus title plus start time is imported once; weekly runs can overlap safely.
- **Recurring listings:** repeated listings from one series are collapsed in the feed to the next upcoming date, so a weekly event does not flood the timeline.
- **Takedown:** an organizer can ask for removal; an admin can unpublish a listing at any time.
- **Claiming (later):** an organizer claims a listing, an admin verifies, a host is set and it becomes a normal hosted event. Not in the first version [D].

## Claims

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | A listing can be stored with no host; a hosted event still requires one (database check) | `test_listing_requires_no_host_and_hosted_requires_one` | — |
| C-02 | The timeline renders a mixed page of posts, hosted events and listings without error | `test_feed_renders_listings_without_host` | Discovery |
| C-03 | A listing card shows the "Around town" label and "Details" link, and shows no RSVP, Invite or Follow host | `test_listing_card_has_no_host_actions` | UI standards |
| C-04 | Listings per feed page never exceed the configured cap | `test_listing_cap_per_page` | Discovery |
| C-05 | Draft listings never appear in feed, map, search or detail for anyone but admins | `test_draft_listings_are_hidden` | Authorization |
| C-06 | Only an admin can approve, unpublish or edit a listing; approval records who and when | `test_only_admin_approves_listings` | Authorization |
| C-07 | Importing the same listing twice creates one row | `test_import_is_idempotent` | Data quality |
| C-08 | Source URLs must be `https` and are rendered with `rel="noopener noreferrer"` | `test_source_url_rules` | Safety |
| C-09 | Past listings are excluded from feed and map | `test_expired_listings_are_hidden` | Discovery |
| C-10 | Listings follow the same location radius contract as events; none is shown beyond it | `test_listing_respects_radius` | Location |
| C-11 | RSVP, ticket checkout, chat and needs endpoints refuse a listing (interest is the only attendance action allowed) | `test_hosted_only_actions_reject_listings` | Authorization |
| C-12 | Repeated listings from one series collapse to the next date in the feed | `test_recurring_listings_collapse` | Discovery |
| C-13 | The timeline query count does not rise with listings in the page | `test_feed_query_count_constant_with_listings` | Performance |
| C-14 | A signed-in user can mark and unmark interest in a published listing once; the count updates; a draft or expired listing refuses it | `test_interest_toggle_rules` | Authorization |
| C-15 | The interested list respects each user's profile privacy and never reveals a private profile to a stranger | `test_interested_list_respects_privacy` | Privacy |

## Blueprint anchors

- [D] Authorization is server-side only: every listing operation names its policy (C-05, C-06, C-11).
- [D] Location queries go through the spatial layer only (C-10).
- [D] The timeline must feel instant (C-13).
- [D] Every screen wears the galaxy theme and passes the 390px gate; this needs an approved mockup first.
- [D] 2026-11-09 Interest is in the app (was O-02); claiming is out of v1 (was O-03); the event radius applies (was O-05); the owner approves, by batch (was O-06).
- [O-01] How many listings may appear in each loaded chunk of the timeline (for example "at most 2 of every 10 items"), and do they also get their own filter? → **stop condition:** the builder does not choose the cap; until answered, planning proposes 2 per 10 as a config value for the owner to confirm.
- [O-04] Listing images: the source gives an image link. Do we download it and store our own copy through `ImageProcessor`, or show it straight from the source site, or skip images? Copying images from other sites can carry copyright risk, and showing them straight from the source can break when the source removes them. Who does the download, Muse or FunLynk's import? → **stop condition:** no image is downloaded or shown from a source site until answered; listings use the date strip over a plain placeholder.
- [O-07] What does "connect" mean for interested people? Only seeing who is interested, or also a way to message each other or open a chat for the listing? → **stop condition:** build the interested list only, with no chat, until answered. Group chat for listings is out of v1.

## Production impact

The app is live. The migration is backward-compatible: add the source column with default `hosted` and backfill nothing (all existing rows are hosted), add the nullable listing fields, then relax `host_id` to nullable and add the check constraint. Code that reads `host` must tolerate none **before** any listing row is created, so the order is: ship the null-safe code and migration, then enable imports. Rollback: stop imports and unpublish all listings; the column can stay. Hot path: the timeline (`FeedService`) changes, so it ships behind the rollout plan in `BUILD_PROTOCOL.md`, with a config switch to hide all listings instantly.

## Security and privacy classification

- **Actors:** guest, user, admin (reviews listings), importer (Muse AI, never publishes).
- **Data touched:** events (listings), source URLs, interest records (who is interested in which listing, a new table).
- **Authorization rules:** a new listing policy: view published only; create, approve, unpublish and edit admin only.
- **Location exposure:** listing coordinates are public venues, not personal addresses. The event radius contract applies.
- **Payment touchpoints:** none. FunLynk never sells tickets for a listing.
- **Other risks:** imported text and URLs are untrusted. Descriptions are escaped, links are validated, and nothing from a source is executed or embedded.

## Evidence and fixtures

`04-fixtures.json` will hold: a hosted event, a published listing, a draft listing, an expired listing, a listing outside the radius, a duplicate import, a three-date recurring series, a listing with an `http` or `javascript:` URL, a listing with no coordinates, and a non-admin trying each admin action.

## Pre-mortem

- **Likely failure:** some screen reads `$activity->host` and crashes on a listing (feed, map, notification, search, Filament). **Mitigation:** enumerate every host read in archaeology; add a feed-and-detail smoke test that includes a listing; roll out null-safe code first.
- **Assumption:** reusing the events table is cheaper than a separate one. **Mitigation:** the archaeology count of host reads confirms or kills this before approval.
- **Assumption:** owners of listed events will tolerate being listed. **Mitigation:** a takedown path and clear source attribution.
- **Unknown:** the quality and format of Muse AI output (dates, venues, prices, coordinates). **Mitigation:** the review queue and a strict import validator; reject rows that do not validate.
- **Unknown:** timeline crowding. **Mitigation:** the per-page cap and recurring collapse.

## Non-goals

- Claiming flow, ticketing, RSVP and chat for listings (chat only if [O-07] says so).
- A fake admin host user.
- Downloading or showing source-site images until [O-04] is answered.
- Auto-publishing imports with no human approval.
- Scraping or crawling inside FunLynk; Muse AI does the research.

## Approval gate

- [ ] Orchestrator verdicts written (claims table complete)
- [ ] Builder has reviewed and added risks to the pre-mortem
- [ ] Governing [D]/[P]/[O] items linked; blueprint anchors filled in; [O] stop conditions stated
- [ ] Production impact + rollback plan stated
- [ ] Archaeology confirms the host-read count and the same-table choice
