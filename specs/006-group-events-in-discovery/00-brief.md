# 006 — Public group events in discovery — Strategic brief

**Status:** DRAFT (→ IN REVIEW → APPROVED)
**Author:** Augment Agent (orchestrator draft)
**Date:** 2026-10-19 · **Revised:** 2026-10-22 (rescoped from group posts to group events)

> The spec number 006 is a placeholder. The decided roadmap (BUILD_PROTOCOL §7)
> ends at 005; the owner decides where this sits in the sequence.

---

## Goal

A user browsing the nearby feed, For You feed or map sees events from public
groups in their area, labelled with the group, and can RSVP without joining.
Group posts stay inside the group.

## Decisions (2026-10-22, product owner)

- **[D]** Group **posts** are not in discovery. They are group banter and questions.
- **[D]** Group **events** appear in discovery only when the group is **public**. Private-group events never do.
- **[D]** Visibility follows the group's **current** privacy, not a copy on the event. A group going private removes its future events from discovery.
- **[D]** A non-member may RSVP to a public group's event. The card shows the group name; a "Join group" prompt is wanted.

## Claims

| ID | Capability | Verdict (named binary test) | Status |
|---|---|---|---|
| C-01 | A public-group event appears in the nearby feed and For You feed, with its group name | `FeedServiceTest › shows public-group events in discovery but never private-group events` | Built |
| C-02 | A private-group event never appears in any feed | same test | Built |
| C-03 | An event leaves discovery when its group becomes private | same test | Built |
| C-04 | A non-member can RSVP to a public-group event | `GroupsIntegrationTest::test_non_member_can_rsvp_to_public_group_event` | Built |
| C-05 | A non-member cannot RSVP to or check out a private-group event | `GroupsIntegrationTest::test_non_member_cannot_rsvp_or_checkout_for_private_group_event` | Built |
| C-06 | The event card shows "in {group}" linking to the group page | covered by existing card render test; dedicated assertion to add | Partial |
| C-07 | A "Join group" prompt appears for non-members on a group event | not written | Open |
| C-08 | Search results (Scout) apply the same rule | not written | Open |

## Blueprint anchors

- [D] Authorization is server-side only. Visibility is decided by `Activity::discoverable()` and `ActivityPolicy`, never in the view.
- [D] Location queries go through the spatial layer only.
- [D] Timeline performance budget from Spec 001: server feed data < 200 ms at 10k posts.
- [O] **Street-address exposure:** a group event carries its venue. Feed cards show `location_name` today. Whether to round or hide street addresses on feed cards is undecided.
- [O] **Search index:** whether Scout/Meilisearch indexes private-group events is unchecked (C-08).

## Production impact

- No schema change. The rule is a query scope on `groups.privacy`.
- Reads one extra `groups` lookup (`whereHas`) on the hot feed path. Re-check against the Spec 001 budget before launch.
- Rollback: remove `->discoverable()` calls; no data changes.

## Security and privacy classification

- **Actors:** guest, user, group member, group admin.
- **Data touched:** group events, group name/slug (public).
- **Authorization rules:** feed includes an event only if it has no group or its group is public. RSVP and checkout require the `view` policy on the event.
- **Payment touchpoints:** checkout now authorizes `view` first. Payment logic is unchanged.

## Pre-mortem

- **Likely failure:** private-group events leak through a path that does not use the scope (search, map, notifications).
- **Mitigation:** one shared scope; C-08 closes the search path.

## Non-goals

- Group posts in discovery.
- Per-event discovery override.
- Algorithm ranking changes (Spec 005).

## Approval gate

- [ ] Product owner approved — **approver:** <name> · **date:** <YYYY-MM-DD>
