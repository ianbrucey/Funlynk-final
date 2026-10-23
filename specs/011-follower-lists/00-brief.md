# 011 — Follower and following lists — Strategic brief

**Status:** APPROVED 2026-10-23 (built; see Build notes)
**Author:** Augment Agent (orchestrator draft)
**Date:** 2026-10-23

> The spec number 011 is a placeholder; the decided roadmap (BUILD_PROTOCOL §7) ends at 005.
> Tracker item P1. The owner said yes to clickable followers/following on 2026-10-23.

---

## Goal

A user can tap the followers or following count on a profile and see who those people are, and follow them from the list.

## Archaeology (what exists today, read from code)

- The profile header shows followers and following counts (`ShowProfile`, `show-profile.blade.php`) as plain text. There is no page that lists them.
- `User::followers()` and `User::following()` relations exist (many-to-many through `follows`). Search results (`search-users`) already render a user row with avatar, name, handle and location.
- Profile content visibility already has one rule: `User::allowsProfileContentFor($viewer)` (owner always; public everyone; followers-only only followers; private owner only), from PR #18.
- The named follow pill and unfollow confirm (`x-fl.follow-pill`, `x-fl.follow-modals`, `HandlesHostFollow`) come from PR #24, so the list can reuse them.
- Header counts use the raw relation count, so deactivated accounts are counted today.

## Proposed behaviour [P]

- Two routes on one page: `/u/{username}/followers` and `/u/{username}/following`, with a two-option switch at the top. The header counts link to them.
- Each row: avatar, display name, @handle, location, and a named Follow / Following pill (not on your own row). Tapping the row opens that profile.
- Newest follow first, 20 per page with "Load more".
- Visibility follows the same rule as the profile tabs: if you cannot see the profile content you cannot see the lists, and you get the same lock message. The owner always sees their own.
- A search box under the switch filters the current list by name or @handle (case-insensitive, typing waits briefly before searching). Switching between Followers and Following clears it. [D] 2026-10-23: owner asked for search in the lists.
- Deactivated accounts are left out, and the header counts use the same filter so the numbers agree.

## Claims

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | The followers list shows exactly the users who follow the profile, newest first | `test_followers_list_shows_followers_newest_first` | Profiles |
| C-02 | The following list shows exactly the users the profile follows | `test_following_list_shows_followed_users` | Profiles |
| C-03 | Header counts link to the matching list | `test_header_counts_link_to_lists` | Profiles |
| C-04 | A followers-only profile hides its lists from non-followers; a private profile hides them from everyone but the owner | `test_lists_respect_profile_visibility` | Authorization |
| C-05 | Deactivated users are excluded and header counts match the lists | `test_inactive_users_excluded_and_counts_match` | Profiles |
| C-06 | Pages of 20 load with no duplicates or gaps | `test_lists_page_without_duplicates` | Timeline |
| C-07 | The follow pill on a row follows and unfollows; no pill on your own row | `test_list_follow_pill_and_own_row` | Follows |
| C-08 | The list uses a constant number of queries however many rows | `test_lists_use_constant_queries` | Performance |
| C-09 | An unknown username returns 404 | `test_list_unknown_user_is_404` | Profiles |
| C-10 | Searching filters the current list by name or handle, treats `%` and `_` as plain text, and a locked profile cannot be searched | `test_list_search_filters_and_escapes` | Authorization |

## Blueprint anchors

- [D] Authorization is server-side only: the visibility check runs in the component, never only in the view.
- [D] 2026-10-23: followers and following lists are wanted (owner yes).
- [P] The lists obey the existing profile visibility setting rather than having their own switch.
- [D] 2026-10-23 A followers-only profile hides its lists from non-followers; a private profile hides them from everyone but the owner (owner said "yes yes" to the proposal and to search in the lists).

## Production impact

- No schema change. Two new routes and one Livewire component; the profile header gets links.
- Rollback: remove the links; the routes can stay or go.

## Security and privacy classification

- **Actors:** guest (cannot reach profiles), user, follower, owner.
- **Data touched:** follows, public user fields (name, handle, avatar, location name).
- **Authorization rules:** `allowsProfileContentFor` before any row is loaded.
- **Location exposure:** the location name is shown the same way search results already show it. No coordinates.
- **Payment touchpoints:** none.

## Pre-mortem

- **Likely failure:** the list leaks people from a profile that is meant to be hidden.
- **Assumption:** showing a user's location name in a list is acceptable because search already does.
- **Unknown:** whether pilot users will mind that their follows are visible. This is why the visibility rule matters.
- **Mitigation:** C-04 is a release gate; the check runs before the query.

## Non-goals

- Removing a follower, blocking, or mutual-follow badges.
- Searching across all people (already exists as user search).
- Notifications.

## Approval gate

- [x] Orchestrator verdicts written (claims table complete)
- [x] Builder has reviewed and added risks to the pre-mortem
- [x] Governing [D]/[P]/[O] items linked; [O] stop conditions stated
- [x] Production impact + rollback plan stated
- [x] Security and privacy classification complete
- [x] Product owner approved — **approver:** product owner · **date:** 2026-10-23 ("yes yes")

## Build notes (2026-10-23)

- `App\Livewire\Profile\FollowList` with routes `profile.followers` and `profile.following`; header counts on the profile link to them.
- Visibility check runs before any row is queried; search escapes `%`, `_` and `\`; deactivated users are excluded and the header counts use the same filter.
- Follow pills reuse `x-fl.follow-pill`, `HandlesHostFollow` and `x-fl.follow-modals` (spec 009).
- Tests: `tests/Feature/FollowListTest.php` (10). Removing the visibility check, the search escaping and the active filter makes three of them fail.
- Not verified: a browser check at 390px.
