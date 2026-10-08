# FunLink — UI Standards

**Status:** [D] Decided 2026-10-08 — the UI/UX standing law for FunLink.
Every screen and component obeys this document. New patterns are proposed here
first ([P]), decided ([D]), then built — never invented mid-ticket.

## 1. The galaxy theme (non-negotiable)

FunLink's identity is a **dark space aesthetic**: deep-space gradient
backgrounds, aurora layers, twinkling stars, and glass-morphism cards.

- **Every page** renders inside the `<x-galaxy-layout>` shell. No exceptions.
- **Content lives in glass cards** — translucent surfaces with subtle borders,
  floating over the galaxy background.
- **Buttons:** primary actions use the pink→purple gradient; secondary actions
  use the dark glass style with a cyan hover border.
- **Forms:** cyan focus glow on inputs; white/gray readable text.
- **Motion:** hover lift/scale on interactive elements; keep it smooth and cheap —
  animation must never jank on a mid-range phone.

The full galaxy specification (gradients, aurora layers, star fields, card
styles) lives in the legacy `context-engine/domain-contexts/ui-design-standards.md`
— **read it for visual context, do not extend it.** New visual primitives are
decided here first, then added to the pattern library at Freeze.

## 2. Mobile-first (a gate, not a suggestion)

- Design and verify at **390px width first**; desktop is the enhancement.
- Every mockup shows the 390px layout **before** the desktop layout.
- Touch targets ≥ 44px. No hover-only interactions. Bottom navigation on mobile
  (the `mobile-bottom-nav` component) is the primary wayfinding on phones.
- Assume some users are phone-only. A feature that doesn't work well at 390px
  doesn't ship.

## 3. Mockups: raw single-file HTML

UI changes require an approved mockup before tickets are planned
(`specs/<NNN>/05-ui-mockup.html`):

- **One self-contained `.html` file** — HTML, CSS, and JS baked in. No external
  assets, no build step, no screenshots.
- **Galaxy theme baked in** — the mockup must look like FunLink, not a wireframe.
- **390px layout first, then desktop**, in the same file.
- **Every state:** empty, loading, validation error, denied, conflict, success —
  plus destructive-action confirmation.
- **Realistic data volume** — a full timeline, not three rows.
- **Synthetic data only** — never real user content, names, or locations.
- Approval is recorded in `05-ui.md`. Post-approval UI changes need a mockup
  update + re-approval.

## 4. Components

- **Reuse before inventing.** The `development-standards/` pattern library and
  `resources/views/components/` are the canonical component homes. Check both
  before creating a new component.
- **New primitives** are proposed in the spec's `05-ui.md`, decided here, and
  added to the pattern library at Freeze — with the mockup section that proved them.
- **Livewire v3** for interactive components; follow the existing component
  structure patterns. No Livewire API from memory — check the version-matched docs.
- **Filament v4** is the admin surface only; it never leaks into the user-facing theme.

## 5. States and feedback

Every interactive surface handles: empty, loading, validation error, denied,
conflict, success. Destructive actions (delete post, cancel event, refund)
require explicit confirmation. Real-time updates (chat, reactions, notifications)
arrive over Reverb — the UI degrades gracefully when the socket drops.

## 6. Performance is a UI property

- The timeline must feel **instant**. Bulky, slow page rendering is a defect.
- Lists paginate or virtualize; never render unbounded collections.
- Images lazy-load with placeholders; no layout shift.
- [O] Exact performance budgets (feed time-to-interactive, image weight caps)
  are set by the timeline refactor spec — 2026-10-08.

## 7. Accessibility basics

Semantic HTML, visible focus states, sufficient contrast on glass surfaces,
meaningful alt text, and no information conveyed by color alone.
