# 02 Research

## Current State Analysis
- **Existing Comment System**:
    - Models: `Comment`, `CommentReaction`.
    - Livewire: `CommentSection`, `CommentForm`, `CommentItem`.
    - Notifications: `CommentMentionNotification`, `CommentOnYourContentNotification`, `ReplyToYourCommentNotification`.
    - **Action**: All of this will be **removed** and replaced by the new Chat System.

## Requirements Analysis
1.  **Fresh Start**: No data migration needed. We can drop old tables.
2.  **Threading**: "Quote Reply" style (referencing a parent message, but displayed linearly or with a small visual link).
3.  **Muting**: Essential for "Group Chats" (Post discussions).
4.  **Unified UI**: Same component for DMs and Post Chats.

## Technical Architecture
- **Database Schema**:
    - `conversations`: The container. Polymorphic for Posts/Activities.
    - `conversation_participants`: Many-to-Many User-Conversation. Stores `is_muted`, `last_read_at`.
    - `messages`: The actual content. `reply_to_message_id` for quote replies.
    - `message_reactions`: For "liking" messages.

- **Real-time**:
    - Laravel Reverb is already the standard.
    - Channel: `conversation.{id}`.
    - Events: `MessageSent`, `MessageReactionAdded`, `TypingIndicator`.

- **Implicit Roster Logic**:
    - **Post Chat**: Users are NOT participants by default.
    - When a user *sends* a message, they are added to `conversation_participants`.
    - When a user *RSVPs*, they are added to `conversation_participants`.
    - Users can manually "Join" or "Leave" (Mute/Hide) the chat.

## UI/UX Design (Galaxy Theme)
- **Glassmorphism**: Standard app styling.
- **Bubbles**:
    - Me: Right aligned, gradient background.
    - Others: Left aligned, glass background.
- **Input**:
    - Bottom fixed bar.
    - Attachment button (left).
    - Send button (right).
- **Header**:
    - Context info (Post Title or User Name).
    - "Mute" toggle.
