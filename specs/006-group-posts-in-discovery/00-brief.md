# 006 — Public group posts in discovery — Strategic brief

**Status:** DRAFT (→ IN REVIEW → APPROVED)
**Author:** Augment Agent (orchestrator draft)
**Date:** 2026-10-19

> The spec number 006 is a placeholder. The decided roadmap (BUILD_PROTOCOL §7)
> ends at 005; the owner decides where this sits in the sequence.

---

## Goal

A user browsing the nearby feed or search sees posts from public groups in their
area, labelled with the group, and can open the group's public page from the card.

## Claims

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | A post in a public group with discovery on appears in the nearby feed of a user inside its radius | `test_public_group_post_appears_in_nearby_feed` | Discovery |
| C-02 | A post in a private group never appears in any feed or search result, for anyone, including members | `test_private_group_post_never_in_global_feed` | Discovery, Groups |
| C-03 | A public group post outside the viewer's radius does not appear | `test_group_post_outside_radius_excluded` | Discovery |
| C-04 | A group admin can turn "show our posts in discovery" off; posts then disappear from feed and search | `test_discovery_toggle_off_hides_group_posts` | Groups |
| C-05 | The setting defaults to on for new public groups and is off and unchangeable for private groups | `test_discovery_default_and_private_lock` | Groups |
| C-06 | Feed cards for group posts show a "from {group}" badge linking to the group page | `test_group_post_card_shows_group_badge` | Cards |
| C-07 | Group posts are searchable only while the visibility rule holds; making a group private or toggling off removes them from the index | `test_search_index_respects_group_discovery` | Discovery |
| C-08 | Expired posts (24–48h) leave the feed like any other post | `test_expired_group_post_excluded` | Posts |
| C-09 | Feed query count and server time stay inside the Spec 001 budget with group posts included | `test_feed_query_budget_with_group_posts` | Timeline |

## Blueprint anchors

- [D] Authorization is server-side only. Visibility is decided in the feed query and the index rule, never in the view.
- [D] Location queries go through the spatial layer only (`laravel-eloquent-spatial`). No hand-rolled distance math.
- [D] Timeline performance budget from Spec 001: server feed data < 200 ms at 10k posts.
- [D] 2026-10-19: public groups are intentionally open so links can be shared outside the platform.
- [O] **Do group events join discovery?** Group events are always `is_public = false` today. → **Stop condition:** this brief covers posts only. Events are a non-goal until the owner answers.
- [O] **Radius source:** the post's own coordinates, or the group's location when the post has none. Proposed: post coordinates, falling back to the group's. → **Stop condition:** C-01/C-03 tickets are not planned until confirmed.
- [P] Default is on for public groups; per-post override comes later.

## Production impact

- Needs a schema change: a boolean on groups (proposed `discovery_enabled`, default true) and coordinates on group posts if missing. Exact delta goes in `02-schema-delta.md`.
- The pre-pilot relaxation (until ~2026-10-21) allows breaking migrations; after launch the migration must be backward-compatible with a backfill for existing group posts.
- This is a hot path (the feed). Ship behind the timeline's rollout plan, not as a drive-by.
- Rollback: set the feature flag off or default the setting to false; the feed query ignores group posts again. No data is lost.

## Security and privacy classification

- **Actors:** guest, user, group member, group admin.
- **Data touched:** group posts, group name/slug (public), group setting.
- **Authorization rules:** feed and search include a post only if its group is public, discovery is on, and the post is unexpired. The toggle is admin-only via the group policy.
- **Location exposure:** a group post's coordinates are exposed within the feed's radius contract (~5–10 km). If it falls back to the group's location, a group meeting at a private address would be revealed at that precision. Decide whether to round or hide street addresses in feed cards.
- **Payment touchpoints:** none.

## Evidence and fixtures

Scenario for `04-fixtures.json`: one public group with discovery on, one with discovery off, one private group, one group made private after posting. Posts: in radius, out of radius, expired, with and without own coordinates. Viewers: member, non-member nearby, non-member far away, guest.

## Pre-mortem

- **Likely failure:** a private or toggled-off group's posts stay in the search index and leak through search while the feed is correct.
- **Assumption:** group posts can carry coordinates, or the group location is a good fallback. Not yet checked in the schema.
- **Unknown:** how the feed's cursor pagination handles a second post source without extra queries.
- **Mitigation:** index rule and feed rule share one scope; C-07 and C-09 are release gates.

## Non-goals

- Group events in discovery (see [O] above).
- Per-post discovery override.
- Converting group posts to events.
- Algorithm ranking changes (Spec 005).

## Approval gate

- [ ] Orchestrator verdicts written (claims table complete)
- [ ] Builder has reviewed and added risks to the pre-mortem
- [ ] Governing [D]/[P]/[O] items linked; [O] stop conditions stated
- [ ] Production impact + rollback plan stated
- [ ] Security and privacy classification complete
- [ ] Product owner approved — **approver:** <name> · **date:** <YYYY-MM-DD>
