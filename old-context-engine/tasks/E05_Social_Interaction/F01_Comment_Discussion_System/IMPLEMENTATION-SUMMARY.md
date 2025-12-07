# Comment System - Implementation Summary

## Quick Reference

**Status**: Ready for Implementation  
**Epic**: E05 Social Interaction  
**Feature**: F01 Comment & Discussion System  
**Estimated Time**: 28-36 hours  
**Priority**: High (Core social feature)

## Key Decisions Made

### ✅ Architecture
- **Single Polymorphic Component**: One `CommentSection` component works for both Posts and Activities
- **Navigate to Detail**: Feed cards show comment count only, clicking navigates to detail page
- **Nested Replies**: Support threading up to 10 levels deep (not flat comments)
- **Real-time**: Use Laravel Reverb (WebSocket), not polling

### ✅ Notification Strategy
1. **Content Owner**: Always notified when someone comments on their Post/Activity
2. **Comment Author**: Notified when someone replies to their comment
3. **@Mentions**: Notified when mentioned (future enhancement)
4. **NOT Notified**: Other commenters on the same post (too noisy)

### ✅ Feed Integration
- **Feed Cards**: Show "💬 12 comments" (clickable, navigates to detail)
- **Detail Pages**: Full comment section with threading, form, real-time updates
- **No Inline Comments**: Keeps feed fast and clean

### ✅ Moderation & Safety
- **Rate Limiting**: Max 5 comments/minute, 20 comments/hour per user
- **Character Limit**: 500 characters max
- **Spam Detection**: Duplicate content detection
- **User Actions**: Delete own comments, report others
- **Admin Actions**: Delete any comment, ban users

## Component Structure

```
app/Livewire/Comments/
├── CommentSection.php      # Main polymorphic component
├── CommentForm.php         # Comment input with validation
└── CommentItem.php         # Single comment with threading

app/Services/
└── CommentService.php      # Business logic, threading, notifications

app/Notifications/
├── CommentOnYourContentNotification.php
├── ReplyToYourCommentNotification.php
└── CommentMentionNotification.php
```

## Implementation Phases

### Phase 1: Core System (18-22 hours)
- T01: Enhance Comment Model & Relationships (3-4h)
- T02: CommentService with Threading Logic (4-5h)
- T03: Filament CommentResource Enhancement (3-4h)
- T04: Livewire Comment Components (6-7h)

### Phase 2: Authorization (3-4 hours)
- T05: Comment Moderation Policies (3-4h)

### Phase 3: Real-time (5-6 hours)
- T06: Real-time Updates & Notifications (5-6h)

### Phase 4: Testing (3-4 hours)
- T07: Comment Tests (3-4h)

## Technical Stack

- **Laravel 12**: `casts()` method, `->withPolicies()`
- **Livewire v3**: Real-time components, `wire:model.live`
- **Filament v4**: `->components([])` for admin
- **Laravel Reverb**: WebSocket server for real-time
- **Laravel Echo**: Client-side WebSocket listener
- **Pest v4**: Testing framework
- **DaisyUI + Galaxy Theme**: UI styling

## Database Tables

### Existing (from E01)
- ✅ `comments` (commentable_type, commentable_id, parent_id, user_id, content)
- ✅ `users`
- ✅ `posts`
- ✅ `activities`

### New (to create)
- `comment_reactions` (user_id, comment_id, reaction_type)

## Next Steps

1. **Review** the full task document: `README.md`
2. **Start with T01**: Enhance Comment Model & Relationships
3. **Follow implementation order**: T01 → T02 → T03 → T04 → T05 → T06 → T07
4. **Test thoroughly**: Write Pest tests for each component

## Success Criteria

- [ ] Users can comment on Posts and Activities
- [ ] Comments support nested replies (up to 10 levels)
- [ ] Real-time updates work via Reverb
- [ ] Notifications sent correctly (content owner, reply author)
- [ ] Rate limiting prevents spam
- [ ] Galaxy theme applied to all components
- [ ] All Pest tests pass

---

**Ready to implement!** See `README.md` for detailed task breakdown.

