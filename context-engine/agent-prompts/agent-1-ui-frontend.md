# Agent 1: UI/UX & Frontend Display

## Project Context
- **Project:** FunLynk - Laravel 12 activity discovery platform
- **Tech Stack:** Laravel 12, Livewire v3, DaisyUI, Tailwind CSS, PostgreSQL
- **Theme:** Galaxy theme with glass morphism (see `context-engine/domain-contexts/ui-design-standards.md`)

## Your Focus Area
Frontend display logic and privacy controls for Groups feature.

---

## Task 1: Private Group Tab Visibility Fix
**Priority: HIGH**

**Problem:** Non-members can see "Timeline", "Members", and "Chat" tabs on private groups. They should only see a restricted view.

**Files to Modify:**
- `resources/views/livewire/groups/group-show.blade.php`

**Requirements:**
1. Wrap the tab navigation in a conditional: only show Timeline/Members/Chat tabs if user `$isMember` OR group is `public`
2. For private groups where user is NOT a member, show a "Private Group" message with just the Request to Join button
3. Keep the Requests tab admin-only (already working)

**Reference:** Check how `$isMember` is already used in the chat section (lines 102-127)

---

## Task 2: Members List - Display Profile Pictures
**Priority: MEDIUM**

**Problem:** The Members tab doesn't show profile pictures of members.

**Files to Modify:**
- `resources/views/livewire/groups/group-members.blade.php`

**Requirements:**
1. Add profile picture display for each member in the list
2. Use the member's `profile_image_url` from User model
3. Fallback to placeholder/initials if no image
4. Follow existing avatar patterns (rounded-xl, 10-12 width/height)

**Reference Pattern:**
```blade
<img src="{{ $member->user->profile_image_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($member->user->name) }}" 
     alt="{{ $member->user->name }}" 
     class="w-10 h-10 rounded-full object-cover">
```

---

## Task 3: Groups Index - Default Filter to "All"
**Priority: LOW**

**Problem:** The groups listing page defaults to "Public Only" filter. Should default to "All Groups".

**Files to Modify:**
- `app/Livewire/Groups/GroupsIndex.php`

**Current Code (line ~18):**
```php
public string $privacyFilter = 'public';
```

**Change to:**
```php
public string $privacyFilter = 'all';
```

---

## Task 4: Group Settings - Image Preview Before Save
**Priority: MEDIUM**

**Problem:** When uploading avatar/cover in settings, preview only shows AFTER saving.

**Files to Modify:**
- `resources/views/livewire/groups/group-settings.blade.php`

**Requirements:**
1. Show live preview when user selects an image file
2. Use Livewire's temporary URL for uploaded files before save
3. Pattern: `$avatarImage->temporaryUrl()` when `$avatarImage` is set, otherwise `$group->avatar_url`

**Reference:** Check `resources/views/livewire/profile/edit-profile.blade.php` for pattern

---

## Testing Checklist
After completing each task, verify:
- [ ] Private groups hide tabs for non-members
- [ ] Members list shows profile pictures
- [ ] Groups index defaults to "All" filter
- [ ] Image previews work in real-time on settings page

## Commands
```bash
php artisan test --filter=Group
```

