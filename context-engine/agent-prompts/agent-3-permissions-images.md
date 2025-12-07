# Agent 3: Permissions & Image Handling on Settings

## Project Context
- **Project:** FunLynk - Laravel 12 activity discovery platform  
- **Tech Stack:** Laravel 12, Livewire v3, WithFileUploads trait
- **Storage:** Local disk or S3 (check `config/filesystems.php`)

## Your Focus Area
Fix 403 permission error and ensure image handling works correctly.

---

## Task 1: Fix 403 Error When Creator Accesses Settings
**Priority: CRITICAL**

**Problem:** User creates a group, then tries to access settings and gets 403 Forbidden.

**Root Cause Hypothesis:** When a group is created, the creator is not being added as an admin member, OR there's a race condition where the membership check fails.

**Files to Investigate:**
1. `app/Services/GroupService.php` - `createGroup()` method
2. `app/Livewire/Groups/GroupSettings.php` - `mount()` method (line 36)
3. `app/Policies/GroupPolicy.php` - any relevant methods

**Current Settings Check (GroupSettings.php line 33-38):**
```php
public function mount(Group $group): void
{
    if (! $group->memberships()->where('user_id', auth()->id())->where('role', 'admin')->exists()) {
        abort(403, 'Only group admins can access settings.');
    }
    // ...
}
```

**Verify GroupService::createGroup() adds creator as admin:**
```php
// Should include something like:
$this->addMember($group, $user, 'admin');
```

**Fix:** Ensure `createGroup()` in GroupService adds the creator as an admin member immediately.

---

## Task 2: Alternative - Use Policy Instead of Manual Check
**Priority: MEDIUM (if Task 1 doesn't resolve)**

Consider replacing the manual check with a policy:

```php
public function mount(Group $group): void
{
    $this->authorize('update', $group);
    // ...
}
```

And ensure `GroupPolicy@update` checks for admin role properly.

---

## Task 3: Verify Image Upload in Group Creation
**Priority: HIGH**

**Problem:** User cannot add profile picture during group creation - only after in settings.

**Files to Modify:**
1. `app/Livewire/Groups/CreateGroup.php` - Add image upload support
2. `resources/views/livewire/groups/create-group.blade.php` - Add image input
3. `app/Services/GroupService.php` - Handle image in createGroup()

**Requirements:**
1. Add `use WithFileUploads` trait to CreateGroup
2. Add `$avatarImage` property with validation
3. Add file input in the view with preview
4. Process upload in `createGroup()` method
5. Pass `avatar_url` to GroupService

**Reference:** See `app/Livewire/Groups/GroupSettings.php` for existing image handling pattern

---

## Task 4: Verify Image Storage Path
**Priority: LOW**

Check that images are being stored correctly:
- Path should be `groups/{group_id}/avatar.{ext}` or similar
- URL should be accessible publicly
- Check `Storage::disk('public')` vs `Storage::disk('s3')`

---

## Testing Checklist
- [ ] Create a new group → immediately access settings (no 403)
- [ ] Upload avatar during group creation → appears on group page
- [ ] Upload avatar in settings → preview shows before save
- [ ] All 97 Group tests still pass

## Commands
```bash
php artisan test --filter=Group
php artisan storage:link  # Ensure public storage is linked
```

## Debug Tips
If 403 persists, add temporary logging:
```php
// In GroupSettings mount()
\Log::info('Checking admin for group', [
    'group_id' => $group->id,
    'user_id' => auth()->id(),
    'memberships' => $group->memberships()->get()->toArray()
]);
```

