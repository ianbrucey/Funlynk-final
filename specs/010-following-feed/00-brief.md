# 010 — Following feed — Strategic brief

**Status:** APPROVED 2026-10-23 (owner said "proceed" after the open questions); mockup in `05-ui-mockup.html`
**Author:** Augment Agent (orchestrator draft)
**Date:** 2026-10-23

> The spec number 010 is a placeholder; the decided roadmap (BUILD_PROTOCOL §7) ends at 005.
> Tracker items FD1 (archaeology, done here) and FD2 (shape, proposed here).

---

## Goal

A user can switch the nearby feed to "Following" and see the posts and events of the people they follow, without them having to be nearby.

## Archaeology (what exists today, read from code)

- `/feed/nearby` (`NearbyFeed`, `FeedService::getNearbyFeed`) merges posts (fixed 10 km) and events (10 to 50 km) by distance from the viewer. Filters: content type, time, radius, search. **Nothing reads follows.**
- `/feed/for-you` (`ForYouFeed`, `getForYouFeed`) exists and is hidden from the navbar. Its "social" score is a constant 10 for everyone, so follows do not affect it.
- Follows are stored in `follows` (`follower_id`, `following_id`, unique, both columns indexed, no self-follow). Spec 008 (notify followers) and spec 009 (named follow button) are the first features that make following visible.
- **Bug found and fixed in PR #25:** the nearby feed, For You, map and search were showing private-group posts to anyone nearby.
- Feed items are plain arrays with a cursor (`created_at` + `id`), 30 first, then 20 per load, capped at 200. A Following feed must keep that contract and the Spec 001 budget (server feed data under 200 ms at 10k posts).

## Options considered

| Option | What it is | Verdict |
|---|---|---|
| A. "Following" tab on the nearby feed | Second view of the same feed: only content from people you follow | **Proposed.** Easy to explain, matches the follow promise, cheap query |
| B. Ranking boost in For You | Followed authors score higher | Later, with Spec 005 (algorithm). Not needed for A |
| C. Mix followed content into Nearby | Followed posts always appear in Nearby, even far away | Rejected for now: changes the Nearby radius contract and is hard to explain |

## Proposed behaviour [P]

- A switch above the filters: **Nearby | Following**. URL `?feed=following` so it can be linked and survives refresh.
- Following shows, newest first: active posts and upcoming published events whose author or host is someone the viewer follows.
- Radius: a wider cap of **50 km** for both posts and events (instead of 10 km for posts), so a followed person's nearby-ish activity is not hidden. Viewers without a saved location see followed content with no radius.
- Same card components, same time and search filters. Content type filter still works.
- Empty states: following nobody gives an explainer and a "Find people" link; following people with nothing new says so.

## Claims

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | Following view shows posts and events from followed users only | `test_following_feed_only_includes_followed_authors` | Discovery |
| C-02 | Content from non-followed users never appears in it | same test | Discovery |
| C-03 | A followed user's private-group event never appears | `test_following_feed_respects_group_privacy` | Authorization |
| C-04 | Posts that belong to a group (public or private, including posted as the group) never appear | `test_following_feed_excludes_group_posts` | Groups |
| C-05 | Radius cap is 50 km; beyond it nothing shows; no-location viewers see all followed content | `test_following_feed_radius_and_no_location` | Discovery |
| C-06 | Expired posts and past or cancelled events never appear | `test_following_feed_excludes_expired_and_past` | Posts, Events |
| C-07 | Cursor pagination has no duplicates or gaps across pages | `test_following_feed_pages_without_gaps` | Timeline |
| C-08 | Following nobody shows the explainer; unfollowing removes content immediately | `test_following_feed_empty_states` | UX |
| C-09 | Feed data stays inside the Spec 001 budget and uses a constant number of queries | `test_following_feed_query_budget` | Timeline |

## Blueprint anchors

- [D] Authorization is server-side only. Visibility is decided in the query (`Post::discoverable()`, `Activity::discoverable()`), never in the view.
- [D] Location queries go through the spatial layer only. No raw coordinates leave the server.
- [D] Timeline performance budget (Spec 001).
- [D] 2026-10-22: private-group content never appears in discovery; public-group events do.
- [D] 2026-10-23 **Group posts are not in discovery, public or private.** Public-group events are. Done in PR #25 for Nearby, For You, map and search; the Following feed uses the same rule.
- [D] 2026-10-23 **Radius for Following:** 50 km cap for posts and events (owner said "proceed" on the proposal).
- [P] Users who follow people but live without a saved location see unbounded followed content.

## Production impact

- No schema change expected. If the feed query is slow at scale, add a composite index on posts (`user_id`, `created_at`) in a follow-up.
- New code path behind the switch; Nearby is untouched. Rollback: hide the switch.
- This is a hot path (the feed). Ship with the Spec 001 benchmark command (`FeedBenchmarkCommand`) run before and after.

## Security and privacy classification

- **Actors:** user, followed host, group member.
- **Data touched:** follows, posts, events.
- **Authorization rules:** recipient sees only what discovery already allows for any viewer; following adds no extra access (a follower of a private-group member still cannot see private-group content).
- **Location exposure:** same as Nearby: distance only, no coordinates, within the 50 km cap.
- **Payment touchpoints:** none.

## Pre-mortem

- **Likely failure:** a second query per source makes the feed slower than Nearby and breaks the budget.
- **Assumption:** most users follow few people, so a `whereIn` on followed ids is cheap. A user following thousands may not hold.
- **Unknown:** how many followed authors a pilot user will realistically have (pilot is under 100 users).
- **Mitigation:** C-09 is a release gate; use a subquery on `follows`, not an id list, and cap results at 200 like Nearby.

## Non-goals

- Ranking boost for followed authors (Spec 005).
- Showing public-group posts anywhere in discovery (decided: never).
- Following groups, or showing posts of groups you joined (separate item).
- Notifications for posts.
- Followers / following lists (tracker P1).

## Approval gate

- [x] Orchestrator verdicts written (claims table complete)
- [x] Builder has reviewed and added risks to the pre-mortem
- [x] Governing [D]/[P]/[O] items linked; [O] stop conditions stated
- [x] Production impact + rollback plan stated
- [x] Security and privacy classification complete
- [x] Product owner approved — **approver:** product owner · **date:** 2026-10-23 ("proceed")
