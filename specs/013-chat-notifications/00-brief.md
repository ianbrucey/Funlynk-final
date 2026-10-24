# 013 — Chat notifications, mentions and mute — Strategic brief

**Status:** APPROVED 2026-10-24 (owner: "you decide"; built, see Build notes)
**Author:** Augment Agent (orchestrator draft)
**Date:** 2026-10-24

> The spec number 013 is a placeholder; the decided roadmap (BUILD_PROTOCOL §7) ends at 005.
> Follows spec 012 (chat experience). Owner said "yes" to drafting with the defaults below.

---

## Goal

A member of a post, event or group chat hears about the messages that matter (mentions, replies, a changed plan) without being spammed by every message, and can mute a chat.

## Archaeology (what exists today, read from code)

- Only direct messages notify (`SendDirectMessageNotification`, plus message requests). It checks `notification_preferences.direct_messages === false` only; no UI sets it, and the global `notification_preference` is ignored.
- Post, event and group chats: `ChatService::sendMessage` broadcasts to open screens. No notification is created.
- `conversation_participants.is_muted` and `ChatService::toggleMute` exist; nothing reads the flag and no screen sets it. `last_read_at` is maintained and drives the "New messages" divider.
- `messages.metadata` (json) exists and can carry a mention list. No mention parsing exists.
- The notification bell listens on a public `user.{id}` channel while `routes/channels.php` registers it as private; live delivery is unverified.
- Follow notifications (spec 008) set the pattern: a queued job, in-app only, recipients filtered through the policy, global preference honoured.

## Proposed behaviour [P]

- **Always notify:** @mention of you, a reply to your message, a newly pinned message.
- **Everything else:** one collapsed notification per chat ("5 new messages in Thursday skate"), at most once per 15 minutes, only if you have not read the chat since.
- **Never notify:** your own messages; a chat you are viewing right now (last read within the last minute); a muted chat.
- **Mute:** a bell on the room header with three states: All, Mentions and replies only, Muted. Default: "Mentions and replies only" when the chat has more than 10 participants, otherwise All.
- **Direct messages:** same collapse and mute rules; honour the global preference (`none` and `email_only` get no in-app notification).
- **Recipients** must still pass the chat's view rule (still a member or allowed to view).
- **@mentions:** typing `@` opens a picker of people in the chat; selected names are stored on the message and highlighted.
- **Unread dot:** on feed cards and the nav for chats with unread messages.
- In-app only. No email or push yet.

## Claims

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | A mention notifies the mentioned participant, even in a mentions-only chat | `test_mention_notifies_in_mentions_only_chat` | Notifications |
| C-02 | A reply notifies the author of the original message | `test_reply_notifies_original_author` | Notifications |
| C-03 | A new pin notifies the other participants once | `test_pin_notifies_participants` | Notifications |
| C-04 | Ordinary messages collapse into one notification per chat per 15 minutes and update its count | `test_ordinary_messages_collapse` | Notifications |
| C-05 | Sender, viewers who read within the last minute, and muted participants get nothing | `test_sender_viewer_and_muted_excluded` | Notifications |
| C-06 | Mute state is stored per user and per chat; defaults follow chat size | `test_mute_states_and_defaults` | Chat |
| C-07 | Someone no longer allowed to view the chat gets nothing | `test_recipient_must_pass_view_rule` | Authorization |
| C-08 | Global preference `none` or `email_only` suppresses in-app chat and DM notifications | `test_global_preference_respected` | Notifications |
| C-09 | Mentions only resolve to participants of that chat and are stored on the message | `test_mentions_limited_to_participants` | Authorization |
| C-10 | Notifications are queued, not sent inside the request | `test_chat_notifications_are_queued` | Performance |
| C-11 | Unread state is true after others post and false after the viewer reads | `test_unread_state` | UX |
| C-12 | The notification opens the chat room and jumps to the message (post chats; events and groups open the page) | `test_a_chat_notification_opens_the_room_and_jumps_to_the_message` | UX |

## Blueprint anchors

- [D] Authorization is server-side: recipients and mention targets are filtered by policy, never by the client.
- [D] Every notification attaches to a post, event or group (the chat's subject).
- [D] 2026-10-23: chat notifications were out of scope of spec 012 and are this spec.
- [D] 2026-10-24 **Mute threshold:** chats over 10 participants default to Mentions and replies only; 15-minute collapse window (owner delegated; builder confirmed the proposals).
- [D] 2026-10-24 **Email and push** are out of scope (in-app only).
- [O] **Bell live delivery:** public vs private channel mismatch. → **Stop condition:** not changed here; verify as a ticket. Notifications appear on page load regardless.

## Production impact

- One migration: `conversation_participants.notify_level` (string, default `default`) so "no choice yet" differs from an explicit "All". Backward compatible; existing `is_muted` rows map to `muted`.
- `messages.metadata` already exists for mentions; no change.
- Rollback: unregister the listener; data is additive.

## Security and privacy classification

- **Actors:** participant, post owner, host, group admin, former member.
- **Data touched:** messages (mention list), notifications, participant settings.
- **Authorization rules:** recipients pass `ConversationPolicy::view`; mention picker lists participants only; mute is changeable only by the participant themself.
- **Location exposure:** none. Notification text is a truncated message body: it can show private-group content on a lock screen later if push is added. In-app only for now.
- **Payment touchpoints:** none.

## Pre-mortem

- **Likely failure:** notification storms in active chats, or the collapse count drifting.
- **Assumption:** a queue worker and the bell channel work in production; both unverified.
- **Unknown:** pilot group sizes (under 100 users in total).
- **Mitigation:** collapse and window are enforced server-side and tested; C-04 and C-10 are release gates.

## Non-goals

- Email and push, quiet hours, per-keyword alerts, notification digests, plan cards and polls.

## Approval gate

- [x] Orchestrator verdicts written (claims table complete)
- [x] Builder has reviewed and added risks to the pre-mortem
- [x] Governing [D]/[P]/[O] items linked; [O] stop conditions stated
- [x] Production impact + rollback plan stated
- [x] Security and privacy classification complete
- [x] Product owner approved — **approver:** product owner (delegated) · **date:** 2026-10-24

## Build notes (2026-10-24)

- `ChatNotifier` service and queued `NotifyChatParticipants` job, dispatched after commit from `ChatService::sendMessage` (non-DM chats) and `pinMessage`.
- Migration `conversation_participants.notify_level` (default `default`; existing muted rows become `muted`).
- Mentions are resolved server-side to participants whose @handle is in the text and stored in `messages.metadata`.
- Collapse window is recorded inside the notification data (`started_at`) and compared in PHP, because comparing PHP times with DB timestamps is off by the DB session timezone offset.
- Opening a chat marks its notifications read; live-delivered messages also mark the chat read, so an open chat is "being viewed".
- Tests: `ChatNotificationTest.php` (12). Disabling the view rule, the global preference and the mute rule each makes a test fail.
- Not verified: browser behaviour of the @ picker and bell sheet, queue worker in production, live bell delivery.
