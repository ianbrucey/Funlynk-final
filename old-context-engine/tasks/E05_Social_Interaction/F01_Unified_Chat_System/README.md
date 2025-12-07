# F01 Unified Chat System

## Feature Overview
The Unified Chat System replaces traditional static comments with a dynamic, real-time chat experience. It serves two primary functions:
1.  **Post/Activity Chat**: A group chat context for every Post or Activity, where all interested users can coordinate, discuss, and plan in real-time.
2.  **Direct Messaging (DM)**: Private 1-on-1 conversations between users.

This unified approach aligns with FunLynk's ephemeral, spontaneous nature, treating every interaction as a "live conversation" rather than an archival record.

## Architecture
- **Polymorphic `Conversation` Model**: A single model that can be attached to a `Post`, `Activity`, or exist independently (for DMs).
- **Unified `ChatComponent`**: A single, robust Livewire component used for both contexts.
- **Real-time**: Powered by Laravel Reverb (WebSocket) for instant message delivery.

## Scope
- **In Scope**:
    - Database schema for Conversations and Messages.
    - Migration from `Comment` model to `Message` model (or adapting `Comment` to be `Message`).
    - Real-time broadcasting.
    - UI for Chat (bubbles, timestamps, avatars).
    - Integration with Posts and Activities.
    - Private DMs.
- **Out of Scope**:
    - Voice/Video calls (future).
    - Advanced moderation AI (future).
