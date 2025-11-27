# 04 Implementation

## Checklist

### Phase 1: Database & Models
- [ ] Create migration `create_chat_system_tables`
- [ ] Create `Conversation` Model
- [ ] Create `ConversationParticipant` Model
- [ ] Create `Message` Model
- [ ] Create `MessageReaction` Model
- [ ] Delete `Comment` system (migrations, models)

### Phase 2: Core Logic
- [ ] Create `ChatService`
- [ ] Implement `sendMessage` logic
- [ ] Create `MessageSent` Event
- [ ] Create `NewMessageNotification`

### Phase 3: UI Components
- [ ] Create `ChatComponent` (Livewire)
- [ ] Implement `chat-bubble` Blade component
- [ ] Implement `chat-input` Blade component

### Phase 4: Integration
- [ ] Add Chat to `Post` view
- [ ] Add Chat to `Activity` view
- [ ] Add "Message" button to User Profile

## Notes
- [ ] Remember to run `php artisan migrate:fresh` if needed (or just migrate) since we are dropping tables.
