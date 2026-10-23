# Work tracker — 2026-10-22

The single list we work through, one item at a time. Update the **Status** column
as items move. Source: owner voice notes (`scratch.md`) plus the 2026-10-18 triage
(`2026-10-18-brain-dump-triage.md`, which stays as the history of the first brain dump).

**Status values:** TODO · BRIEF (needs a brief/spec first) · MOCKUP (needs a mockup first,
AGENTS.md) · BLOCKED (waiting on an owner decision) · IN PR (open PR) · DONE.
**Tags:** [D] decided · [P] proposed · [O] open. Decisions carry their date.

## Shipped so far (2026-10-22)

| Item                                                                                          | Where                         |
| --------------------------------------------------------------------------------------------- | ----------------------------- |
| Public-group events in discovery; private-group events never; search leak closed              | PR #17 (merged)               |
| Group events saved as `published` (they were invisible); data migration                     | PR #17 (merged)               |
| Cover image on group events; "Join group" pill on event page                                  | PR #17 (merged)               |
| Profile privacy (public / followers / private), tab filtering, owner-only Saved removal       | PR #18 (open)                 |
| Profile page redesign from approved mockup 007; "Saved Posts" tab; header stats without posts | PR #19 (open, stacked on #18) |

## A. Follow system (one piece of work)

Finding: the Follow button on the event page follows the host, saves the follow and updates
counts, and **nothing else**. No notification is sent and nothing reads follows (the
recommendation engine's social score is a constant placeholder).

| #  | Item                                                                                                                      | Decision                                                 | Status  |
| -- | ------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- | ------- |
| F1 | Following a host notifies the follower when the host publishes an event                                                   | **[D] 2026-10-22: Option 1 (notify on new event)** | BRIEF DRAFTED (specs/008-follow-notifications)   |
| F2 | Button label names the person: "Follow {host name}", not "Follow"                                                         | [D] 2026-10-22                                           | MOCKUP  |
| F3 | On first follow, a pop-up explains: "You'll get a notification when {name} publishes an event" (then a short toast after) | [D] 2026-10-22; copy is [P]                              | MOCKUP  |
| F4 | Follow button on the nearby-feed card, so people need not open the event                                                  | [P] depends on F1 being real                             | MOCKUP  |
| F5 | Unfollow needs a way to turn notifications off (settings)                                                                 | [O] not raised by owner yet                              | BLOCKED |

Order: F1 first (the pop-up cannot promise what does not exist), then F2+F3+F4 in one mockup.

## B. Following feed

| #   | Item                                                                                            | Notes                                                                                                                                 | Status  |
| --- | ----------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- | ------- |
| FD1 | Nearby feed shows only random nearby content; no way to see posts/events from people you follow | Unverified in code: a search of `FeedService` and the Discovery components found no follow logic. First step is an archaeology pass | BRIEF   |
| FD2 | Shape of the answer: a "Following" filter/tab on the feed, and/or ranking boost (Spec 005 area) | [O] owner to choose after archaeology                                                                                                 | BLOCKED |

## C. Profile page follow-ups

| #  | Item                                                                        | Notes                                                                                                                                                                           | Status  |
| -- | --------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------- |
| P1 | Followers / following counts are not clickable; no list of who follows whom | [O] **Opinion given:** yes, add lists, subject to the profile visibility rule (hide lists on followers-only/private profiles; owner always sees theirs). Needs a decision | BLOCKED |
| P2 | Saved tab name stays "Saved Posts"                                          | [D] 2026-10-22                                                                                                                                                                  | DONE    |
| P3 | Posts count removed from header stats                                       | [D] 2026-10-22                                                                                                                                                                  | DONE    |

## D. Feed behaviour

| #  | Item                                                                                                                     | Notes                                                                                                                                          | Status |
| -- | ------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------- | ------ |
| N1 | Return to the same place in the nearby feed after opening an event/post and going back (3 pages deep stays 3 pages deep) | Owner: lowest priority. Tested by hand: browser Back did not restore position. Likely needs a saved scroll/cursor; treat as its own small spec | BRIEF  |

## E. Group chat

| #  | Item                                                                                    | Notes                            | Status  |
| -- | --------------------------------------------------------------------------------------- | -------------------------------- | ------- |
| G1 | Review group chat design (long conversations on mobile, easy-to-see replies, searching) | From triage D3. Roadmap spec 004 | TODO    |
| G2 | `/posts/{id}` vs `/posts/{id}/chat`: keep both or merge                             | From triage D2. Roadmap spec 003 | BLOCKED |

## F. Carried over from the 2026-10-18 triage (not yet done)

| #     | Item                                                                                                                                                                                                | Status      |
| ----- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------- |
| B1    | Menu flashes on page load (mobile "+" create list)                                                                                                                                                  | TODO        |
| B2    | Uncaught "Host has not completed Stripe onboarding" at checkout. Partly handled: `CheckoutForm` now shows a friendly message. Still check whether hosts can create paid events without onboarding | TODO        |
| A1/A2 | Collapsed search bar, cleaner filter form on mobile                                                                                                                                                 | MOCKUP      |
| A3/A4 | Grid columns (1 or 2) and event photo shape                                                                                                                                                         | BLOCKED     |
| A5    | Feed background darker                                                                                                                                                                              | BLOCKED     |
| A6    | Tell events apart from posts                                                                                                                                                                        | BLOCKED     |
| A7    | Thin line above the chat section on post cards                                                                                                                                                      | TODO        |
| A8    | Radius in miles (or both)                                                                                                                                                                           | BLOCKED     |
| D1    | Third "informational" post type                                                                                                                                                                     | BLOCKED     |
| E1a   | Group differentiation (fundraising, role/equipment assignment)                                                                                                                                      | shelved [O] |

Note: the 2026-10-18 triage marked C1/C2 (loading screen) as handled by the global navigation overlay.

## G. Open questions waiting on the owner

- F5: can a follower turn off notifications from one host?
- FD2: Following feed as a tab/filter, a ranking boost, or both?
- P1: follower/following lists, and do they obey profile visibility?
- Group event street addresses on feed cards: round or hide? (from brief 006)
- Feed speed: measure the group lookup added by PR #17 against the Spec 001 budget.
- Not yet browser-tested: profile redesign (PR #19), group event cover upload, street-address picker.

## Suggested order

1. Merge #18 then #19. Browser-check the profile at 390px.
2. F1 (follow notifications), then one mockup for F2+F3+F4.
3. FD1 archaeology, then the FD2 decision.
4. G1 group chat review.
5. P1, N1, then the carried-over items (B2 and B1 first).
