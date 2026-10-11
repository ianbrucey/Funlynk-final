# FunLink — Product Blueprint

**Status:** [D] v0.1 verified draft — 2026-10-08. Derived from the codebase
(`app/Models`, `routes/`, migrations, `composer.json`/`composer.lock`) and the
project's prior design docs. Claims are tagged **[D]** verified against code,
**[P]** inherited from prior docs but not yet re-verified, **[O]** open.

## What FunLink is

**[D]** FunLink is a live social discovery app for spontaneous, niche activities.
Users post ephemeral activity ideas; engagement converts the best ones into
structured, ticketed events. Groups organize people around shared interests with
group chat. Hosts sell tickets and get paid through Stripe.

## Core domains

### Posts (ephemeral discovery)

- **[D]** Users create posts (`Post` model, `/posts/create`, `/posts/{post}`).
- **[D]** Posts carry reactions (`PostReaction` — "I'm down" / "Join me" style engagement).
- **[D]** Posts have chat (`PostChat` at `/posts/{post}/chat`, `Conversation`/`Message` models) — group chat instead of comment sections.
- **[P]** Posts are ephemeral (24–48h) with ~5–10km discovery radius; expiry enforced server-side.
- **[D]** Engagement thresholds drive conversion: 5 reactions (soft prompt), 10 (strong prompt) — `Post::CONVERSION_*_THRESHOLD`, owner decision 2026-10-09 (spec 000, 000-D05).
- **[O]** Exact post expiry windows as currently configured.

### Events (structured activities)

- **[D]** Events are the `Activity` model: structured, persistent, with RSVPs (`Rsvp`), invitations, and edit history (`ActivityEditLog`).
- **[D]** Events support ticketing: checkout (`/events/{activity}/checkout`), tickets (`MyTickets`), QR check-in (`QrScanner`, `HostAttendeeManager`).
- **[D]** Paid events flow through Stripe: host onboarding (`StripeOnboarding`), `StripeAccount`, `Transaction` records, refund windows (`ActivityRefundWindow`).
- **[D]** Post-to-event conversion links events back via `originated_from_post_id` (`PostConversion`).
- **[P]** Events discoverable in ~25–50km radius.

### Groups

- **[D]** Users create and join groups (`Group`, `GroupMember`, `GroupJoinRequest`); groups have posts and group chat.
- **[D]** Group landing pages exist (`/groups`, public landing variants).

### Discovery

- **[D]** Nearby feed (`/feed/nearby`), for-you feed (`/feed/for-you`), map view (`/map`), user/post search (`/search`, `/search/users`).
- **[D]** All location-aware discovery runs on PostGIS via `matanyadaev/laravel-eloquent-spatial`.
- **[D]** Search indexing via Laravel Scout + Meilisearch.

### Social graph

- **[D]** Follows (`Follow`), profiles (`/u/{username}`), tags (`Tag`), flares (`Flare`), direct messages (`/messages`, `Conversation`/`Message`/`MessageReaction`).
- **[D]** Notifications (`Notification`, `/notifications`) with preferences (`/settings/notifications`).

### Trust and moderation

- **[D]** Reports (`Report`) against content/users; onboarding wizard (`/onboarding`).

## Non-negotiables

- Posts and Events are the central objects — everything attaches to them.
- Authorization is server-side only (policies/gates).
- Payments go through the payment service layer only — never Stripe from controllers/Livewire.
- Location queries go through the spatial layer only; radius contracts are law.
- Every page wears the galaxy theme; mobile-first is a gate.
- The timeline must feel instant.

## Stack [D]

Laravel 12 · PHP 8.2+ · PostgreSQL + PostGIS · Filament v4 · Livewire v3.6 ·
Blade + Tailwind + DaisyUI · Pest v4 · Pint · Laravel Reverb · Laravel Octane ·
Laravel Horizon · Laravel Scout + Meilisearch · Stripe · S3 storage.

## Open questions [O]

- Exact live production URL and Laravel Cloud deploy trigger configuration.
- Exact post expiry windows as currently configured.
- Whether legacy agent-system dirs (`context-engine/`, `agent-swarm/`, `dev-logs/`) are archived or kept read-only.

## Resolved [D]

- 2026-10-11 — Roadmap and pilot cut approved (`BUILD_PROTOCOL.md` §7).
- 2026-10-11 — Timeline performance budget: server feed data < 200 ms at 10k posts; first cards visible < 1 s on a phone over 4G.
- 2026-10-11 — Pilot: fewer than 100 users, launching ~2026-10-21. Live-app rules relaxed until launch day (`BUILD_PROTOCOL.md`, Brownfield rules).
