# <NNN> — <Feature name> — Strategic brief

**Status:** DRAFT (→ IN REVIEW → APPROVED)
**Author:** <orchestrator name>
**Date:** <YYYY-MM-DD>

> HOW TO USE THIS TEMPLATE: fill in every `<...>` placeholder. Do not delete
> sections — if a section does not apply, write "N/A — <reason>" so the omission
> is explicit. No implementation work begins until this brief is APPROVED by the
> product owner.

---

## Goal

<One sentence: the user outcome. Not a feature name — e.g. "A user scrolling
the nearby feed sees new posts appear in under a second on a mid-range phone."
If you cannot write the outcome in one sentence, the scope is too big — split
the feature.>

## Claims

Every capability gets a named, binary verdict. The verdict is written by the
planner/orchestrator — **a builder never writes the verdict that will judge
its own work** — and the builder may challenge verdicts before approval.

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | <what the user can do> | <e.g. "test_feed_renders_50_posts_under_budget" — PASS/FAIL, no judgment calls> | <e.g. Discovery, or "—" if none> |
| C-02 | <...> | <...> | <...> |

## Blueprint anchors

Link each claim to the governing system decisions. Copy the tags exactly.

- [D] <e.g. D-xx server-side authorization — claim C-01's endpoints authorize via PostPolicy before any data access>
- [P] <proposed decision this feature relies on — flag that it is not implementation authority>
- [O] <open question this feature depends on> → **stop condition:** <what the builder must do instead of guessing>

<RULE: if any [O] item is load-bearing for this feature, the brief must state
the stop condition. Builders stop on unresolved [O] items; they do not invent
answers.>

## Production impact

<What changes for live users and their data. Migration compatibility story.
Rollback plan. For hot paths (feed, checkout): the rollout plan.>

## Security and privacy classification

- **Actors:** <e.g. guest, user, group_member, host, admin>
- **Data touched:** <posts, events, payments, locations, messages>
- **Authorization rules:** <which policies/gates, per operation>
- **Location exposure:** <what coordinates enter, what radius applies, what leaves>
- **Payment touchpoints:** <none, or intents/transfers/refunds with idempotency>

## Evidence and fixtures

<Canonical test scenario: the synthetic users, posts, events, groups, payments,
and adversarial cases this feature builds against. Point to 04-fixtures.json.
Include unauthorized users, expired posts, sold-out events, failed payments,
out-of-radius locations where relevant.>

## Pre-mortem

- **Likely failure:** <what will probably go wrong first>
- **Assumption:** <what we are assuming that might be false>
- **Unknown:** <what we don't know yet>
- **Mitigation:** <what we do about each of the above>

## Non-goals

<Explicit exclusions. If it is not listed here or in Claims, it is not being built.>

## Approval gate

- [ ] Orchestrator verdicts written (claims table complete)
- [ ] Builder has reviewed and added risks to the pre-mortem
- [ ] Governing [D]/[P]/[O] items linked; [O] stop conditions stated
- [ ] Production impact + rollback plan stated
- [ ] Security and privacy classification complete
- [ ] Product owner approved — **approver:** <name> · **date:** <YYYY-MM-DD>

<No code, no schema changes, no tickets until every box is checked.>
