# 014 — Event needs list (who brings what) — Strategic brief

**Status:** APPROVED 2026-10-24 (owner: "you decide"; built, see Build notes)
**Author:** Augment Agent (orchestrator draft)
**Date:** 2026-10-24

> The spec number 014 is a placeholder; the decided roadmap (BUILD_PROTOCOL §7) ends at 005.
> Origin: owner discussion 2026-10-24 about group admin tooling (the Tuesday/Thursday skate-night dispute).
> This is step 1 of a sequence. Step 2, group "asks" with receipts, is a separate later spec.

---

## Goal

An organizer can list what an event needs, attendees can claim items, and everyone can see at a glance what is covered and what is still missing.

## Decisions (owner, 2026-10-24)

- **[D]** First piece to build is the **event needs list**. Group asks and the receipts ledger come after it.
- **[D]** When asks are built, money is **link-out only** (Venmo, Cash App or PayPal link), no money held by FunLink, and **any group admin** approves an ask and posts receipts.
- **[D]** Roles split into two ideas: standing authority roles at group level (later) and per-event responsibilities (this spec).

## Archaeology (what exists today, read from code)

- Events (`Activity`) have RSVPs (`Rsvp`, status such as attending or waitlist), hosts (`host_id`), and `ActivityPolicy` (view, update). Group events are covered by `GroupPolicy` (admin is the `memberships.role = admin`).
- The event page (`activity-detail`) has a details card, attendee management for the host, and the chat section. There is no place to say what is needed.
- `routes/console.php` already schedules event reminder jobs (`SendEventReminders`), so a day-before job has a home.
- Notifications use in-app records, queued jobs, the global preference, and the chat and follow notifiers as patterns (specs 008 and 013).

## Proposed behaviour [P]

- An **"What we need"** section on the event page. Each item: name, how many are needed (default 1), optional note (up to 200 characters), and a status derived from claims: Open, Partly covered, Covered, Brought.
- **Who manages:** the event host, or a group admin for group events (same as `ActivityPolicy::update`). They add, edit, remove and reorder items, assign an item to someone who is going, and mark claims as brought. Up to 30 items per event.
- **Who claims:** anyone who is **going** (RSVP attending). Others see "RSVP to claim". A claim has a quantity; the total of claims cannot exceed the number needed.
- **Release:** a claimer can release their claim any time; the item reopens.
- **Copy from a previous event:** the host picks one of their earlier events and copies its items (without claims). Auto-copy for recurring schedules is a later step.
- **Day-before check:** about 24 hours before the event, each claimer gets one in-app notification, "Still bringing X?", with Confirm and Release.
- **Visible to whoever can view the event;** claim names show to people who can view the event.

## Claims

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | Host or group admin can add, edit, remove and reorder items; others cannot | `test_only_managers_edit_needs` | Authorization |
| C-02 | Only people going can claim; others are refused and shown "RSVP to claim" | `test_only_attendees_claim` | Events |
| C-03 | Claims cannot exceed the quantity needed, even with two people claiming at the same moment | `test_claims_cannot_exceed_quantity` | Concurrency |
| C-04 | A claimer can release; the item reopens and statuses update | `test_release_reopens_item` | Events |
| C-05 | A manager can assign an item to someone who is going and mark a claim brought | `test_manager_assigns_and_marks_brought` | Events |
| C-06 | Item status (Open, Partly covered, Covered, Brought) is derived from claims | `test_item_status_from_claims` | UX |
| C-07 | Copying from a previous event copies items without claims, only from events the user manages | `test_copy_needs_without_claims` | Authorization |
| C-08 | A day-before notification goes once to each claimer who is still going; Confirm keeps the claim, Release frees it | `test_day_before_check_notifies_claimers_once` | Notifications |
| C-09 | Limits hold: 30 items, name 80 characters, note 200 characters, quantity 1 to 99 | `test_needs_limits` | Validation |
| C-10 | Someone who cancels their RSVP loses their claims and the item reopens | `test_cancelling_rsvp_releases_claims` | Events |
| C-11 | Private-group event needs are visible only to people who can view the event | `test_needs_follow_event_visibility` | Authorization |
| C-12 | The needs section loads with a constant number of queries however many items | `test_needs_query_count_constant` | Performance |

## Blueprint anchors

- [D] Authorization is server-side only: manage, claim and release each authorize on the server.
- [D] Every item attaches to an event; there is no standalone needs data.
- [D] Mobile-first at 390px; 44px targets.
- [D] 2026-10-24 **Reminder:** 24 hours before the event, one notification per person per event, only for claims at least 3 hours old.
- [D] 2026-10-24 **Release:** the host is not notified when someone releases a claim (avoids noise); the list shows it.
- [D] 2026-10-24 **No auto-RSVP:** people RSVP first, then claim.

## Production impact

- Two new tables (`event_needs`, `event_need_claims`). Backward compatible; no change to existing tables.
- Rollback: hide the section; data is additive.

## Security and privacy classification

- **Actors:** host, group admin, attendee, non-attendee, guest.
- **Data touched:** needs, claims (names of people who claim).
- **Authorization rules:** view via the event policy; manage via `update`; claim requires an attending RSVP.
- **Location exposure:** none. **Payment touchpoints:** none.

## Pre-mortem

- **Likely failure:** two people claim the last unit at once and the item over-fills.
- **Assumption:** attendees will use the page, not the chat, to claim. Pilot size is under 100 users.
- **Unknown:** whether organizers want templates by event type, not only copy-from-last.
- **Mitigation:** a database transaction with a row lock on claim (C-03); the chat can link to the section later.

## Non-goals

- Group asks, donations, receipts (next spec); standing authority roles and treasurer; shared gear inventory; venue calendar; auto-copy for recurring schedules; money of any kind.

## Approval gate

- [x] Orchestrator verdicts written (claims table complete)
- [x] Builder has reviewed and added risks to the pre-mortem
- [x] Governing [D]/[P]/[O] items linked; [O] stop conditions stated
- [x] Production impact + rollback plan stated
- [x] Security and privacy classification complete
- [x] Product owner approved — **approver:** product owner (delegated) · **date:** 2026-10-24

## Build notes (2026-10-24)

- Tables `event_needs` and `event_need_claims`; `EventNeedService` holds the rules (limits, quantity, open event) and takes a row lock on claim so two people cannot over-claim.
- `ActivityPolicy` gained `manageNeeds` (same as `update`: host or group admin) and `claimNeeds` (managers or people with an attending RSVP).
- `Rsvp` model hook releases a person's claims when their RSVP leaves `attending` or is deleted.
- `SendNeedsCheckReminders` runs hourly; claimers see a "Still bringing it" button on the event page after being reminded.
- Tests: `EventNeedsTest.php` (12). Removing the claim rule, the item limit and the quantity check each makes a test fail.
- Not verified: browser behaviour at 390px, queue worker and scheduler running in production.
