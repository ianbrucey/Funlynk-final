# Brain dump triage — 2026-10-18

Source: `scratch.md` (voice transcript, product owner walkthrough of the app).
Purpose: every issue/question raised, grouped and tagged. Nothing here is decided yet.

Tags: **[BUG]** defect · **[DESIGN]** needs a mockup + approval first (AGENTS.md: UI changes need an approved mockup) · **[DECISION]** product choice before any work · **[SMALL]** can ship directly.
Items are numbered so we can refer to them one at a time. "Said" = what the owner said; "Unverified" = I have not checked it in the code yet.

## A. Feed layout and look (the timeline)

| # | Item | Said | Tag |
| --- | --- | --- | --- |
| A1 | Search bar and filters take too much space on first load | Replace with a collapsed search icon/bar that toggles the form open | DESIGN |
| A2 | Filter form looks clunky on mobile | Select dropdowns and radius control need a redesign (go together with A1) | DESIGN |
| A3 | Grid columns | Currently 3 columns on desktop (unverified). Too many. Choose 1 column (Facebook/Instagram style) or 2 (Eventbrite style) | DECISION |
| A4 | Event photo shape | Either let users upload any size, or force a shape by cropping. Owner leans to: one column, or two columns with a "tap to enlarge" photo on the feed and full size on the event page | DECISION (depends on A3) |
| A5 | Feed background | Body gradient ("space blue") makes cards blend in. Removing it (near black) made cards easier to see. Owner leans darker | DESIGN (touches the galaxy theme in `UI_STANDARDS.md`) |
| A6 | Tell events apart from posts | Maybe an icon (lightning/party), maybe on the right. Owner unsure whether to do it at all | DECISION |
| A7 | Visual separation inside post cards | Thin, light horizontal line above the group chat section | SMALL |
| A8 | Radius units | Show miles instead of kilometers, or both (e.g. `mi / km`) | DECISION (miles-only vs both), then SMALL |

Withdrawn: an "@" sign at the top of post cards. Owner considered it and dropped it. Do not build.

## B. Bugs

| # | Item | Detail | Tag |
| --- | --- | --- | --- |
| B1 | Menu flashes on page load | The mobile "+" create-post list shows briefly, then hides. Seen on several pages. Likely CSS/JS hiding too late (unverified) | BUG |
| B2 | Uncaught Stripe onboarding error | RSVP on a paid event whose host has no Stripe Connect account throws "Host has not completed Stripe onboarding" from `PaymentService` (about line 54) at `/events/{id}/checkout`. Owner believes hosts cannot create paid events without onboarding, so this should be rare. Still wants it caught and shown as an alert | BUG |

Note for B2: first check whether host creation really blocks paid events without onboarding. If it does not, that is a bigger gap than the error message.

## C. Loading and transitions

| # | Item | Detail | Tag |
| --- | --- | --- | --- |
| C1 | Freeze after login | Long pause with a frozen screen before the feed appears. Owner wants a loading screen (FunLink icon + spinner/"loading") so users know something is happening | DESIGN |
| C2 | Page transitions | "Terrible" in general. Open question: loader only for login to feed, or global | DECISION |

C1 and C2 are one piece of work once C2 is answered. The slow login itself is also a performance question, separate from the loader (unverified whether it is the app or the dev machine).

## D. Product decisions

| # | Item | Detail | Tag |
| --- | --- | --- | --- |
| D1 | Third post type: informational post | Admin/promoter posts about an outside event they are not hosting, so people can view it, maybe RSVP or say they are going. Low priority. Conflicts with "Posts and Events are the central objects" in `PRODUCT_BLUEPRINT.md`, so needs a decision recorded first | DECISION |
| D2 | Two pages for one post | `/posts/{id}` and `/posts/{id}/chat` both let you enter the group chat. Keep both or merge? The post page looks fine; the chat page looks ugly | DECISION + DESIGN |
| D3 | Group chat design | Basic design exists, replies exist. Concerns: long conversations on mobile, easy-to-see replies, searching for a detail. Also asks how much effort chat deserves versus leaving it as is | DECISION + DESIGN |

## E. Deferred

- **E1 Groups.** Owner wants a dedicated, serious session. Out of scope for this triage.

## How these map to the decided roadmap (`BUILD_PROTOCOL.md` §7)

This is my proposal, not a decision.

| Roadmap spec | Items that fit |
| --- | --- |
| 002 cards + images | A3, A4, A6, A7 (and A5/A8 if treated as card/feed styling) |
| 003 detail pages | D2 |
| 004 group chat | D3 |
| Not on the roadmap | A1, A2, A5, A8, B1, B2, C1, C2, D1 |

## Suggested order

1. **Bugs, no mockup needed:** B2, then B1.
2. **Quick decisions that unblock others:** A3 (grid), A8 (units), C2 (loader scope), A5 (background).
3. **Design batch (one mockup session):** A1+A2 together, then C1.
4. **Card work (Spec 002):** A4, A6, A7 after A3 is settled.
5. **Later specs:** D2, D3. D1 whenever the owner wants it.
6. **Separate session:** E1.

## Open questions

- A3: one or two columns?
- A8: miles only, or miles and kilometers?
- C2: loader for login only, or for every page transition?
- D1: is a third post type allowed under the blueprint, or does it become an event type?
