# Team 3 (Frontend) - Next Steps

## ⚠️ Your proposal NEEDS REVISION before implementation

You must fix **5 issues** and resubmit your proposal.

---

## 📋 What You Need To Do

### **Step 1: Read the Review**
Read your section in: `PROPOSAL_REVIEW_RESULTS.md`

It contains detailed explanations and code examples for all required fixes.

### **Step 2: Fix All Issues**

#### **Issue #1: Tag Validation**
Tags should be optional, not required.

**Change from**:
```php
'tags' => 'required|array|min:1'
```

**Change to**:
```php
'tags' => 'nullable|array',
'tags.*' => 'exists:tags,id'
```

#### **Issue #2: Channel Naming**
Use singular form for channel names (Laravel convention).

**Change from**:
```php
"echo-private:groups.{$this->group->id},NewGroupPost"
```

**Change to**:
```php
"echo-private:group.{$this->group->id},GroupPostCreated"
```

#### **Issue #3: Event Names**
Align with Backend event names.

**Change from**:
- `NewGroupPost` → `GroupPostCreated`
- `NewGroupEvent` → `GroupEventCreated`
- `UserJoinedGroup` → `GroupMemberJoined`
- `UserLeftGroup` → `GroupMemberRemoved`

**Example**:
```php
public function getListeners(): array
{
    return [
        "echo-private:group.{$this->group->id},GroupPostCreated" => 'onPostCreated',
        "echo-private:group.{$this->group->id},GroupEventCreated" => 'onEventCreated',
        "echo-private:group.{$this->group->id},GroupMemberJoined" => 'onMemberJoined',
        "echo-private:group.{$this->group->id},GroupMemberRemoved" => 'onMemberRemoved',
    ];
}
```

#### **Issue #4: Avatar Upload Implementation**
Specify how avatar upload will work.

**Add this to your revised proposal**:

**CreateGroup Component**:
```php
use Livewire\WithFileUploads;

class CreateGroup extends Component
{
    use WithFileUploads;
    
    public string $name = '';
    public string $description = '';
    public $avatar; // File upload
    public array $selectedTags = [];
    public string $privacy = 'public';
    
    public function createGroup(): void
    {
        $this->validate([
            'name' => 'required|string|max:100|unique:groups,name',
            'description' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|max:1024', // 1MB max
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'privacy' => 'required|in:public,private',
        ]);
        
        $avatarUrl = null;
        if ($this->avatar) {
            $avatarUrl = $this->avatar->store('group-avatars', 'public');
        }
        
        $group = app(GroupService::class)->createGroup(auth()->user(), [
            'name' => $this->name,
            'description' => $this->description,
            'avatar_url' => $avatarUrl,
            'privacy' => $this->privacy,
            'tag_ids' => $this->selectedTags,
        ]);
        
        session()->flash('success', 'Group created successfully!');
        return redirect()->route('groups.show', $group->slug);
    }
}
```

**Blade View**:
```blade
<input type="file" wire:model="avatar" accept="image/*" class="...">
@error('avatar') <span class="error">{{ $message }}</span> @enderror

@if ($avatar)
    <img src="{{ $avatar->temporaryUrl() }}" class="preview">
@endif
```

#### **Issue #5: Dashboard Routing Logic**
Specify how `/dashboard` replaces profile hero for authenticated users.

**Add this to your revised proposal**:

**In `routes/web.php`**:
```php
Route::get('/profile/{user:username}', function (User $user) {
    // If viewing own profile, redirect to dashboard
    if (auth()->check() && auth()->id() === $user->id) {
        return redirect()->route('dashboard');
    }
    
    // Otherwise show public profile
    return app(\App\Livewire\Profile\ProfileShow::class);
})->name('profile.show');
```

**Explanation**: When authenticated users visit their own profile URL, they're automatically redirected to the dashboard. Visitors see the normal profile page.

### **Step 3: Resubmit Proposal**
Create a new file: `FRONTEND_PROPOSAL_v2.md`

Include all fixes from above.

### **Step 4: Report Back**
When complete, update `PROPOSAL_REVIEW_RESULTS.md` at the bottom:

```markdown
## Team Progress Updates

### Team 3 (Frontend) - [DATE/TIME]
✅ Revised proposal submitted as FRONTEND_PROPOSAL_v2.md
- Changed tag validation to nullable
- Updated channel names to singular form
- Aligned event names with Backend
- Added avatar upload implementation (Livewire WithFileUploads)
- Added dashboard routing logic (redirect from profile)
```

---

## 🚫 DO NOT START IMPLEMENTATION

You are **BLOCKED** until:
1. Your revised proposal is approved
2. Team 2 completes their implementation (you need their API endpoints)

**Timeline**: Submit revised proposal within 1 hour.

**Questions?** Ask the Architect Agent (me) in `PROPOSAL_REVIEW_RESULTS.md`

---

## ✅ Checklist Before Resubmitting

- [ ] Changed tag validation to `nullable|array`
- [ ] Updated all channel names to singular form (`group.{id}`)
- [ ] Updated all event names to match Backend (`GroupPostCreated`, etc.)
- [ ] Added avatar upload implementation with `WithFileUploads` trait
- [ ] Added complete code example for avatar upload
- [ ] Added dashboard routing logic with redirect
- [ ] Created `FRONTEND_PROPOSAL_v2.md`
- [ ] Reported completion in `PROPOSAL_REVIEW_RESULTS.md`

