# 012 — Chat that feels like a real group chat — Strategic brief

**Status:** APPROVED 2026-10-23 (owner: "you make your own recommendations and proceed")
**Author:** Augment Agent (orchestrator draft)
**Date:** 2026-10-23

> The spec number 012 is a placeholder; the decided roadmap (BUILD_PROTOCOL §7) ends at 005 (004 is group chat).
> Review and findings: `dev-journal/progress/2026-10-23-group-chat-review.md`. Security fixes shipped separately in PR #31.

---

## Goal

A person in a post, event or group chat can follow a conversation, react, reply, and see what the plan is, on a phone, without the page fighting them.

## Decisions (made by the builder under owner delegation, 2026-10-23)

- **[D]** Scope is Phase 1 of the review plus **pinned message** and **reactions**. Plan cards and polls are a separate later spec.
- **[D]** One place for a post's chat: `/posts/{id}/chat` is the room. The post page shows the last few messages and an "Open chat" button instead of embedding the full chat.
- **[D]** Sending in a group-post chat stays members-only; anyone who can view the post can read (PR #31).
- **[D]** Chat notifications and mute are out of scope here (nothing sends them yet). The dead bell button is removed.

## Archaeology

- `ChatComponent` serves post page, post chat page, event page, group tab and DMs. It reloads all 50 messages on every send, shows a blocking overlay, and keeps messages as arrays in Livewire state.
- `messages` already has `type` and `reply_to_message_id`; `message_reactions` exists with `ChatService::reactToMessage`, but no UI reads it.
- Conversations have no pinned message today. Posts, events and groups each own one conversation.

## Claims

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | Sending appends the message without reloading the thread; a pending bubble shows instantly in the browser | `test_send_appends_without_reloading_history` | Real-time |
| C-02 | Consecutive messages from one sender within 5 minutes group; a day separator appears when the day changes | `test_messages_group_and_day_separators` | UX |
| C-03 | "Load earlier" fetches the previous 50 without duplicates | `test_load_earlier_pages_without_duplicates` | Timeline |
| C-04 | A "new messages" divider appears before the first message the viewer has not read | `test_unread_divider_marks_first_unread` | UX |
| C-05 | A user can reply to their own message and to others; the quote carries the original id | `test_reply_to_own_and_others` | UX |
| C-06 | Reactions toggle per user and emoji, show counts, and only from people allowed to read the chat | `test_reactions_toggle_and_are_authorized` | Chat |
| C-07 | The post owner, event host or group admin can pin and unpin one message; others cannot; DMs have no pin | `test_pin_rules` | Authorization |
| C-08 | The pinned message shows at the top of the room and the post page preview | `test_pinned_message_shown` | UX |
| C-09 | The post page shows the last 3 messages and "Open chat" instead of the full chat | `test_post_page_shows_preview_not_full_chat` | UX |
| C-10 | The thread is bounded to the viewport with the composer pinned, Enter sends and Shift+Enter adds a line | browser check in the mockup acceptance (not a PHP test) | Mobile-first |

## Blueprint anchors

- [D] Authorization is server-side only: pin, react and send each authorize on the server.
- [D] Mobile-first at 390px; touch targets 44px.
- [D] 2026-10-23 PR #31 chat authorization rules stay in force.
- [O] Notifications for chat messages. → **Stop condition:** not built here.

## Production impact

- One nullable column (`conversations.pinned_message_id`). Backward compatible.
- Broadcast payload gains fields (user id, avatar, reactions summary); old clients ignore extras.
- Rollback: hide the pin and reaction controls; data is additive.

## Security and privacy classification

- **Actors:** participant, post owner, event host, group admin, outsider.
- **Data touched:** messages, reactions, pinned message id.
- **Authorization rules:** view and send via `ConversationPolicy`; new `pin` rule; react requires `view`.
- **Location exposure:** none. **Payment touchpoints:** none.

## Pre-mortem

- **Likely failure:** reactions and pins do not update live for other people (only on next refresh) and feel broken.
- **Assumption:** real-time delivery over Reverb works; unverified here.
- **Unknown:** how long real conversations get in the pilot.
- **Mitigation:** broadcast a lightweight "refresh" for reactions and pins; the room re-reads on that signal.

## Non-goals

- Plan cards, polls, @mentions, attachments, search, edit/delete, report, notifications, mute.

## Approval gate

- [x] Orchestrator verdicts written
- [x] Builder has reviewed and added risks to the pre-mortem
- [x] Governing [D]/[P]/[O] items linked; [O] stop conditions stated
- [x] Production impact + rollback plan stated
- [x] Security and privacy classification complete
- [x] Product owner approved — **approver:** product owner (delegated) · **date:** 2026-10-23
