# 015 — Group asks and the receipts ledger — Strategic brief

**Status:** DRAFT (→ IN REVIEW → APPROVED)
**Author:** Augment Agent (orchestrator draft)
**Date:** 2026-10-25

> The spec number 015 is a placeholder; the decided roadmap (BUILD_PROTOCOL §7) ends at 005.
> Origin: owner discussion 2026-10-24 (the Tuesday/Thursday skate-night dispute over unexplained donation requests).
> Step 2 of the group admin tooling; step 1 was the event needs list (spec 014).

---

## Goal

A group can ask its members for money for a stated purpose, and everyone can see what was asked, what came in, what was spent, and the receipts, so nobody has to wonder where the money went.

## Decisions (owner, 2026-10-24)

- **[D]** Money is **link-out only**: an ask carries a Venmo, Cash App, PayPal (or similar) link. **FunLink never holds or moves money.**
- **[D]** **Any group admin** approves an ask and posts receipts.
- **[D]** Event needs list first (built), asks second (this spec).

## Archaeology (what exists today, read from code)

- Groups have memberships with a `role` of `admin` or `member`; `GroupPolicy::update` means "is a group admin". Groups are public or private; `GroupPolicy::view` decides who can see a group.
- The group page (`GroupShow`) has tabs (timeline, members, chat). There is no place for money.
- `ImageProcessor` stores photos to S3 with presets (`post` preset suits receipts). The Stripe payment layer (`PaymentService`) exists for ticketed events and is **not used here**.
- In-app notifications are written by queued jobs that honour the global preference (specs 008, 013, 014).

## Proposed behaviour [P]

- A **Funds** tab on the group page lists asks: Open, Waiting for approval (admins only), and Closed.
- **Any member proposes an ask:** title, why (up to 1000 characters), target amount, optional deadline, the payment link. It starts as **Proposed** and is visible only to admins and its author.
- **Any admin approves or declines** (a decline needs a reason the author sees). Approving opens it and notifies members.
- **Ledger.** Admins record **money received** (amount, who or "anonymous", note) and **purchases** (amount, what, a photo of the item, a photo of the receipt). Entries cannot be edited or deleted: an admin can **void** one with a visible reason. Totals are calculated from entries: received, spent, and what is left.
- **Closing:** an admin closes the ask as **Completed** (needs at least one purchase with a receipt photo and an item photo, or the closing note must explain a leftover balance) or **Cancelled** (a reason is required; any money received must be explained in the note). The closing note says what happens to leftover money.
- **"I chipped in"**: a member can tap once to say they gave, to show activity; it is clearly labelled self-reported and never counted in the totals.
- Anyone who can view the group sees asks, the ledger and receipts. The payment link is shown only on open asks. Every page says: payments happen outside FunLink and amounts are recorded by group admins.
- Members are notified in-app when an ask opens and when a purchase with receipt is posted. Admins are notified of new proposals.

## Claims

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | A member can propose an ask; it is hidden from other members until approved | `test_member_proposal_is_hidden_until_approved` | Groups |
| C-02 | Only admins approve or decline; a decline requires a reason | `test_only_admins_approve_or_decline` | Authorization |
| C-03 | Only admins record received money and purchases | `test_only_admins_record_entries` | Authorization |
| C-04 | Entries cannot be edited or deleted; voiding needs a reason and keeps the entry visible | `test_entries_are_append_only_with_void` | Accountability |
| C-05 | Totals (received, spent, left) are derived from non-voided entries | `test_totals_derive_from_entries` | Accountability |
| C-06 | A purchase entry requires an amount, a description, an item photo and a receipt photo | `test_purchase_requires_proof` | Accountability |
| C-07 | Completed close requires a purchase with proof; cancelled close requires a reason | `test_close_rules` | Accountability |
| C-08 | Payment links must be https and on the allow-list; shown only while the ask is open | `test_payment_link_rules` | Safety |
| C-09 | A private group's asks and ledger are visible to members only; a public group's to anyone who can view it | `test_asks_follow_group_visibility` | Authorization |
| C-10 | "I chipped in" is once per member, labelled self-reported, never counted in totals | `test_chipped_in_is_self_reported` | Accountability |
| C-11 | Members are notified when an ask opens and a purchase is posted; admins when a proposal arrives; preferences and visibility are honoured | `test_ask_notifications` | Notifications |
| C-12 | Limits hold: 20 open asks per group, amounts 1 to 1,000,000, text lengths, receipts under 6 MB | `test_ask_limits` | Validation |
| C-13 | The Funds tab loads with a constant number of queries | `test_funds_query_count_constant` | Performance |

## Blueprint anchors

- [D] Authorization is server-side only; payments through the payment layer only: **this spec moves no money**, so it does not touch that layer.
- [D] Every record attaches to a group (the ask), so there is no orphaned content.
- [D] Mobile-first at 390px; 44px targets.
- [O] **Can an admin approve their own proposal?** The owner said "any admin". Proposed safeguard: if the group has two or more admins, an admin cannot approve their own proposal. → **Stop condition:** C-02 is planned both ways; build the safeguard only if the owner confirms.
- [O] **Payment link allow-list** (proposed: venmo.com, cash.app, paypal.me, paypal.com, ko-fi.com, buymeacoffee.com, gofundme.com, zeffy.com). → **Stop condition:** unlisted hosts are refused until the owner adds them.
- [O] **Public visibility of ledger** for public groups is proposed; the owner may want members-only. → **Stop condition:** none; ships as proposed.

## Production impact

- Two new tables (`group_asks`, `group_ask_entries`) plus a small `group_ask_chips` table for "I chipped in". No change to existing tables. Receipt photos use the existing S3 storage.
- Rollback: hide the tab; data is additive.

## Security and privacy classification

- **Actors:** member, admin, public visitor (public groups only), guest.
- **Data touched:** ask text, amounts, names of contributors (optional), receipt photos (may show personal details on receipts).
- **Authorization rules:** view via `GroupPolicy::view`; propose requires membership; approve, record, void and close require `GroupPolicy::update`.
- **Receipt privacy:** receipts are visible to anyone who can view the group. Admins are warned to hide card numbers and addresses before uploading.
- **Fraud risk:** a bad actor could create a group and ask for money. Mitigations in scope: allow-listed links, admin approval, append-only ledger, rate limits. A report path for the platform is **not** in scope (listed below).
- **Payment touchpoints:** none; link-out only.

## Pre-mortem

- **Likely failure:** admins skip recording entries, so the ledger looks empty and transparency fails.
- **Assumption:** admins will record honestly; the ledger only proves what admins chose to post.
- **Unknown:** whether groups will use amounts publicly; pilot is under 100 users.
- **Mitigation:** nudge admins to post a receipt a set time after an ask is funded (later); show "last updated" on each ask so silence is visible.

## Non-goals

- Moving or holding money, Stripe, tax receipts, refunds, recurring donations.
- Reporting asks to the platform, member comments on asks, mutual-aid between groups.
- Standing treasurer role, two-person rules beyond the open question above, a shared venue view.

## Approval gate

- [x] Orchestrator verdicts written (claims table complete)
- [ ] Builder has reviewed and added risks to the pre-mortem
- [ ] Governing [D]/[P]/[O] items linked; [O] stop conditions stated
- [x] Production impact + rollback plan stated
- [x] Security and privacy classification complete
- [ ] Product owner approved — **approver:** <name> · **date:** <YYYY-MM-DD>
