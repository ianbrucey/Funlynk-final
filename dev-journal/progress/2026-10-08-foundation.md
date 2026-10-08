# Progress — 2026-10-08

## Done
- Foundation protocol ported to FunLink (branch `feat/foundation-protocol`):
  new `AGENTS.md` + `CLAUDE.md` (mirrors), `docs/BUILD_PROTOCOL.md`,
  `docs/PLANNING_PROTOCOL.md`, `docs/UI_STANDARDS.md`,
  `docs/PRODUCT_BLUEPRINT.md` (v0.1 verified draft), `specs/_template/`,
  `dev-journal/` skeleton.
- Prior agent system (`context-engine/`, `agent-swarm/`, `dev-logs/`,
  `GEMINI.md`, `WARP.md`) marked legacy in AGENTS.md — read for context,
  do not extend.

## In progress
- Owner review of the foundation docs before merge to `main`.

## Next
- Tooling tickets [P]: PHPStan level 7 config + baseline; CI workflow
  (Pint → PHPStan → Pest on PostgreSQL).
- System docs [P]: `docs/DOMAIN_MODEL.md`, `docs/SECURITY.md`, `docs/ROADMAP.md`.
- Spec 001 candidate [P]: timeline performance refactor (brief → archaeology →
  mockup → tickets), pending roadmap approval.

## Decisions
- 2026-10-08 [D] New build protocol is standing law for FunLink; spec-first,
  [D]/[P]/[O] tags, binary verdicts, UI mockups as raw single-file HTML,
  mobile-first gate, galaxy theme non-negotiable.
- 2026-10-08 [D] Brownfield rules: backward-compatible migrations, stated
  rollback, mandatory post-merge smoke test (merge = production deploy).
