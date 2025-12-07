# 03 Plan

## Step 1: Database Architecture
We will create a robust schema to support both DMs and Group Chats.

### Tables
1.  **`conversations`**
    - `id` (UUID, PK)
    - `type` (string: 'private', 'group', 'public')
    - `conversationable_type` (nullable, index) -> Polymorphic (Post, Activity)
    - `conversationable_id` (nullable, index)
    - `last_message_at` (timestamp, index) -> For sorting inbox
    - `metadata` (json) -> For group names, icons, etc.
    - `created_at`, `updated_at`, `deleted_at`

2.  **`conversation_participants`**
    - `id` (UUID, PK)
    - `conversation_id` (UUID, FK)
    - `user_id` (UUID, FK)
    - `role` (string: 'member', 'admin')
    - `is_muted` (boolean, default false)
    - `last_read_at` (timestamp)
    - `created_at`, `updated_at`
    - *Unique Constraint*: `[conversation_id, user_id]`

3.  **`messages`**
    - `id` (UUID, PK)
    - `conversation_id` (UUID, FK)
    - `user_id` (UUID, FK) -> Sender
    - `reply_to_message_id` (UUID, nullable, FK) -> For quote replies
    - `body` (text, nullable)
    - `type` (string: 'text', 'image', 'system')
    - `attachment_path` (string, nullable)
    - `metadata` (json) -> For extra data
    - `created_at`, `updated_at`, `deleted_at`

4.  **`message_reactions`**
    - `id` (UUID, PK)
    - `message_id` (UUID, FK)
    - `user_id` (UUID, FK)
    - `reaction` (string) -> e.g., '👍', '❤️'
    - `created_at`

## Step 2: Backend Services (`ChatService`)
- **`createConversation(type, participants, relatedModel)`**: Handles creating DMs (checking if exists) or new Post chats.
- **`sendMessage(conversation, user, body, ...)`**: Creates message, updates `last_message_at`, broadcasts event.
- **`addParticipant(conversation, user)`**: Adds user to roster.
- **`markAsRead(conversation, user)`**: Updates `last_read_at`.

## Step 3: Frontend (`ChatComponent`)
- **Livewire Component**: `App\Livewire\Chat\ChatComponent`
- **Props**: `conversationId` OR `conversationable` (to resolve conversation on fly).
- **State**: `messages` (array), `newMessage` (string), `isTyping` (bool).
- **Real-time**: Listen to `conversation.{id}` channel.

## Step 4: Integration
- **Post Detail**: Embed `<livewire:chat.chat-component :conversationable="$post" />`
- **Activity Detail**: Embed `<livewire:chat.chat-component :conversationable="$activity" />`
- **User Profile**: "Message" button triggers `ChatService::createConversation` and redirects to Chat view.
