# Group chat review — 2026-10-23

Goal from the owner: not a comment section, a real group chat where plans get made.
Method: read the code (`ChatComponent`, `ChatService`, `MessageSent`, the five places the chat is embedded, policies, channels) and probe it with throwaway tests. Not browser-tested; real-time (Reverb) behaviour unverified.

## Where chat lives today

One Livewire component (`App\Livewire\Chat\ChatComponent`) serves four contexts: post page (`/posts/{id}`, embedded), post chat page (`/posts/{id}/chat`), event page, group page "chat" tab, and direct messages. There are no tests for it.

## A. Security problems (fixed in the PR that carries this file)

Each was reproduced with a probe test before fixing.

| # | Finding | Impact |
|---|---|---|
| S1 | `conversationId` was a public Livewire property, so a user could change it from the browser | Any logged-in user could **read and write any conversation, including other people's DMs** |
| S2 | No authorization when opening or sending | Same |
| S3 | `/posts/{id}/chat` and `/posts/{id}` returned 200 to non-members for **private-group posts** | Private group posts and chats readable by anyone with the link |
| S4 | Anyone who could view a group post could post in its chat and become a participant | Outsiders joining private conversations |
| S5 | Messages broadcast on a **public** channel `conversation.{id}` | Anyone who knows an id can listen live |
| S6 | No length limit (a 100,000-character message was stored) and no rate limit | Abuse, storage |

Fixes: `ConversationPolicy` now defers to the post/event policy for post and event chats (and requires group membership to send in group-post chats); `conversationId` is locked; open and send are authorized; messages are validated (max 2000) and rate limited (30 per minute); broadcast moved to a private channel with a channel authorization rule; post pages authorize `view`. DM recipients still cannot reply until they accept a request (existing rule, now tested).

Other things noticed, not changed: `group.{id}` channels (used by the group timeline) are not registered in `routes/channels.php`, and the notification bell listens on a public `user.{id}` channel while the registered channel is private. Both may silently fail. Unverified.

## B. Why it does not feel like a group chat

1. **Layout.** The thread sits in a `min-h-screen` page with no bounded height, so the whole page scrolls and the composer is at the bottom of a long page. On a phone the composer should be pinned above the bottom nav and the thread should scroll inside it.
2. **Sending blocks the screen.** Every send shows a full-thread "Sending…" overlay and reloads all 50 messages. Chat should append instantly.
3. **Reads like comments.** One bubble per message with a name and "3 minutes ago" on every one. No grouping of consecutive messages, no day separators, no real clock times.
4. **Avatars are initials only** even when the user has a photo; bubbles are capped at 70% width, which is narrow on a phone.
5. **History stops at 50 messages** with no "load earlier".
6. **No unread state.** No unread divider, no unread count on cards, and the bell button in the header does nothing (mute exists in the service but not the UI).
7. **Replies are one-way.** You can only reply to other people's messages and the quote does not jump to the original.
8. **Reactions exist in the database and service but not in the UI.**
9. **No chat notifications** for post, event or group chats (only DMs notify).
10. **The header says "Group Chat, N participants"** with no member list, no link to the post or event the chat is for.
11. **Two pages for one post** (`/posts/{id}` embeds the chat, `/posts/{id}/chat` is the full page). Triage item D2.

## C. What would make plans happen (proposed, nothing decided)

**Phase 1: make it feel like chat**
- Pinned composer and inner-scrolling thread at 390px; Enter sends, Shift+Enter adds a line; auto-growing input.
- Send instantly (add the message locally, confirm from the server), no overlay.
- Group consecutive messages, day separators, clock times, real avatars, wider bubbles.
- "Load earlier" and a "new messages" divider; unread dot on cards.
- Tap a quote to jump to the original; reply to anyone including yourself.
- One-tap reactions (use the existing backend). A thumbs-up becomes "I'm in".

**Phase 2: plan-making features (the part that sets FunLynk apart)**
- **Pinned message** for the host or admin ("Meet at the pier, 6pm").
- **Plan card:** anyone proposes a time and place as a special message (the `messages.type` column already exists); members tap In / Maybe / Can't. When enough say In, a one-tap **"Make this an event"** reuses the post-to-event conversion.
- **Quick poll** ("Which night?").
- **@mentions** with a notification, and **system messages** ("Sam joined", "Event created").
- **Chat notifications** with per-chat mute (make the bell real).

**Phase 3:** photos, search inside a chat (owner asked about finding a detail on 2026-10-18), edit and delete own messages, report a message.

## D. Recommendation

1. Merge the security fixes first (they stand alone).
2. One spec, "chat that feels real", covering Phase 1 plus pinned message and reactions. Needs a mockup before building (AGENTS.md).
3. A second spec for Plan card and polls, because it adds tables and ties into event conversion.
4. Resolve D2: I would keep one place. The chat page is the room; the post page shows the last few messages and an "Open chat" button.
5. Split the shared component by context (post/event/group vs DM) so each header and rule is simple.

## E. Decisions needed from the owner

- Phase 1 only, or Phase 1 plus Phase 2 planned together?
- D2: keep both pages or one?
- Should group-post chats stay members-only for sending (now enforced) while anyone who can view the post can read?
- Mute and notifications: every message, mentions only, or off by default?
