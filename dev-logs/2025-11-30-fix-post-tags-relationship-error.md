# Fix: Post Tags Relationship Error

**Date**: November 30, 2025  
**Issue**: `RelationNotFoundException` - Call to undefined relationship [tags] on model [App\Models\Post]  
**Status**: ✅ FIXED

## Problem

The application was throwing a `RelationNotFoundException` when trying to eager load `tags` as a relationship on the `Post` model:

```
Illuminate\Database\Eloquent\RelationNotFoundException
Call to undefined relationship [tags] on model [App\Models\Post].
```

**Root Cause**: The code was treating `tags` as a relationship (using `with(['tags'])`) when it's actually a JSON column cast to an array.

## Architecture Context

### Post Model vs Activity Model

**Post Model** (`app/Models/Post.php`):
- `tags` is a **JSON column** stored in the `posts` table
- Cast to `array` in the model's `casts()` method
- Accessed directly as `$post->tags` (returns array)
- No relationship needed

**Activity Model** (`app/Models/Activity.php`):
- `tags` is a **many-to-many relationship** with the `Tag` model
- Uses `activity_tag` pivot table
- Accessed via `$activity->tags` (returns collection of Tag models)
- Requires eager loading with `with(['tags'])`

## Files Fixed

### 1. `app/Models/User.php` (Line 274)

**Before**:
```php
$query = Post::whereHas('reactions', function ($q) {
    $q->where('user_id', $this->id)
        ->where('reaction_type', 'im_down');
})->with(['user', 'tags', 'reactions']); // ❌ tags is not a relationship
```

**After**:
```php
$query = Post::whereHas('reactions', function ($q) {
    $q->where('user_id', $this->id)
        ->where('reaction_type', 'im_down');
})->with(['user', 'reactions']); // ✅ removed 'tags'
```

### 2. `app/Livewire/Modals/ConvertPostModal.php` (Line 55)

**Before**:
```php
$this->post = Post::with(['tags', 'reactions', 'invitations'])->findOrFail($postId);
```

**After**:
```php
$this->post = Post::with(['reactions', 'invitations'])->findOrFail($postId);
```

### 3. `app/Services/PostService.php` (Line 355)

**Before**:
```php
$post = Post::with(['tags', 'reactions', 'invitations'])->findOrFail($postId);
```

**After**:
```php
$post = Post::with(['reactions', 'invitations'])->findOrFail($postId);
```

### 4. `context-engine/tasks/post-to-event-flow/AGENT_B_TASKS.md` (Line 25)

**Before**:
```php
})->with(['user', 'tags', 'reactions']);
```

**After**:
```php
})->with(['user', 'reactions']); // Note: tags is a JSON column, not a relationship
```

## Verification

Tested that `tags` works correctly as an array:

```bash
php artisan tinker --execute="
    \$post = App\Models\Post::first();
    var_dump(\$post->tags);
"

# Output:
array(2) {
  [0]=> string(6) "travel"
  [1]=> string(8) "outdoors"
}
```

## Key Takeaways

1. **Post.tags** = JSON column (array cast) - No eager loading needed
2. **Activity.tags** = Many-to-many relationship - Requires eager loading
3. When accessing `$post->tags`, Laravel automatically casts the JSON to an array
4. No performance benefit from "eager loading" a JSON column - it's always loaded with the model

## Related Models

**Post Model Relationships** (for reference):
- `user()` - BelongsTo User
- `reactions()` - HasMany PostReaction
- `conversion()` - HasOne PostConversion
- `conversation()` - MorphOne Conversation
- `convertedActivity()` - BelongsTo Activity
- `invitations()` - HasMany PostInvitation

**Activity Model Relationships** (for reference):
- `host()` - BelongsTo User
- `tags()` - BelongsToMany Tag (via activity_tag pivot)
- `rsvps()` - HasMany Rsvp
- `conversation()` - MorphOne Conversation
- `postOrigin()` - BelongsTo Post

## Impact

This fix resolves the error that was preventing users from viewing their "Interested" posts tab on their profile page. The application now correctly handles the `tags` field as a JSON column instead of trying to eager load it as a relationship.

**Status**: ✅ Production Ready

