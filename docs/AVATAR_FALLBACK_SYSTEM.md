# Avatar Fallback System

## Problem Statement

**Current Issue**: Profile picture upload is required, but if MinIO/S3 is down, users can't proceed.

**Security Concern**: We want real photos for trust/safety, but can't block users if storage fails.

**Solution**: Multi-tier fallback system with gentle nudges toward real photos.

---

## Proposed System

### Tier 1: Real Photo (Preferred)
- User uploads actual profile picture
- Stored in MinIO/S3
- **Trust Score**: 100%

### Tier 2: Avatar Selection (Fallback)
- User selects from 20-30 illustrated avatars
- Stored as database enum (no S3 dependency)
- **Trust Score**: 60%

### Tier 3: Default Avatar (Last Resort)
- System-generated avatar (initials + color)
- No storage required
- **Trust Score**: 30%

---

## Database Changes

### Add to `users` table:

```php
Schema::table('users', function (Blueprint $table) {
    $table->enum('avatar_type', ['photo', 'avatar', 'default'])->default('default');
    $table->string('avatar_selection')->nullable(); // e.g., 'avatar_01', 'avatar_02'
    $table->integer('profile_trust_score')->default(30);
});
```

### Migration:

```bash
php artisan make:migration add_avatar_fallback_to_users_table
```

---

## Avatar Library

### Option A: DiceBear API (Free, No Storage)

```php
// Generate avatar URL dynamically
$avatarUrl = "https://api.dicebear.com/7.x/avataaars/svg?seed={$user->id}";
```

**Pros**: Free, infinite variations, no storage
**Cons**: External dependency

### Option B: Local SVG Avatars (Recommended)

Store 30 SVG avatars in `public/avatars/`:
- `avatar_01.svg` - Friendly face
- `avatar_02.svg` - Cool sunglasses
- `avatar_03.svg` - Smiling person
- ... (30 total)

**Pros**: No external dependency, fast, customizable
**Cons**: Need to create/source avatars

### Option C: Initials + Color

```php
// Generate initials avatar
$initials = strtoupper(substr($user->name, 0, 2));
$color = sprintf('#%06X', crc32($user->email) & 0xFFFFFF);

// Render as SVG or use CSS
```

**Pros**: Zero storage, always works
**Cons**: Less personality

---

## User Flow

### Registration Flow (Updated)

```
1. Create Account (email, password, name)
   ↓
2. Profile Setup
   ↓
3. Profile Picture (NEW: Optional with choices)
   ├─ Upload Photo (preferred) → Trust Score: 100%
   ├─ Choose Avatar (fallback) → Trust Score: 60%
   └─ Skip for Now (default) → Trust Score: 30%
   ↓
4. Complete Onboarding
```

### Gentle Nudges

**Low Trust Score Users** (30-60%) see occasional prompts:
- "Add a real photo to build trust" (banner)
- "Users with photos get 3x more responses" (tooltip)
- "Verify your identity with a photo" (modal after 7 days)

**Never block** - just encourage.

---

## Code Implementation

### 1. User Model Method

```php
// app/Models/User.php

public function getAvatarUrlAttribute(): string
{
    return match($this->avatar_type) {
        'photo' => Storage::disk('s3')->url($this->profile_picture),
        'avatar' => asset("avatars/{$this->avatar_selection}.svg"),
        'default' => $this->generateDefaultAvatar(),
    };
}

protected function generateDefaultAvatar(): string
{
    // Option 1: DiceBear
    return "https://api.dicebear.com/7.x/avataaars/svg?seed={$this->id}";
    
    // Option 2: Initials
    $initials = strtoupper(substr($this->name, 0, 2));
    $color = sprintf('%06X', crc32($this->email) & 0xFFFFFF);
    return "https://ui-avatars.com/api/?name={$initials}&background={$color}&color=fff";
}
```

### 2. Profile Picture Component (Livewire)

```php
// app/Livewire/Profile/ProfilePictureSelector.php

class ProfilePictureSelector extends Component
{
    public $mode = 'photo'; // 'photo', 'avatar', 'skip'
    public $selectedAvatar = null;
    public $photo = null;

    public function save()
    {
        $user = auth()->user();

        match($this->mode) {
            'photo' => $this->uploadPhoto($user),
            'avatar' => $this->selectAvatar($user),
            'skip' => $this->useDefault($user),
        };

        return redirect()->route('dashboard');
    }

    protected function uploadPhoto($user)
    {
        try {
            $path = $this->photo->store('profile-pictures', 's3');
            $user->update([
                'profile_picture' => $path,
                'avatar_type' => 'photo',
                'profile_trust_score' => 100,
            ]);
        } catch (\Exception $e) {
            // S3 failed - fallback to avatar selection
            session()->flash('error', 'Upload failed. Please choose an avatar instead.');
            $this->mode = 'avatar';
        }
    }

    protected function selectAvatar($user)
    {
        $user->update([
            'avatar_selection' => $this->selectedAvatar,
            'avatar_type' => 'avatar',
            'profile_trust_score' => 60,
        ]);
    }

    protected function useDefault($user)
    {
        $user->update([
            'avatar_type' => 'default',
            'profile_trust_score' => 30,
        ]);
    }
}
```

### 3. Blade View

```blade
<div class="space-y-6">
    <!-- Mode Selector -->
    <div class="flex gap-4">
        <button wire:click="$set('mode', 'photo')" 
                class="btn {{ $mode === 'photo' ? 'btn-primary' : 'btn-outline' }}">
            📸 Upload Photo
        </button>
        <button wire:click="$set('mode', 'avatar')" 
                class="btn {{ $mode === 'avatar' ? 'btn-primary' : 'btn-outline' }}">
            🎨 Choose Avatar
        </button>
        <button wire:click="$set('mode', 'skip')" 
                class="btn {{ $mode === 'skip' ? 'btn-primary' : 'btn-outline' }}">
            ⏭️ Skip for Now
        </button>
    </div>

    <!-- Photo Upload -->
    @if($mode === 'photo')
        <input type="file" wire:model="photo" accept="image/*">
        @error('photo') <span class="error">{{ $message }}</span> @enderror
    @endif

    <!-- Avatar Selection -->
    @if($mode === 'avatar')
        <div class="grid grid-cols-6 gap-4">
            @for($i = 1; $i <= 30; $i++)
                <img src="{{ asset("avatars/avatar_" . str_pad($i, 2, '0', STR_PAD_LEFT) . ".svg") }}"
                     wire:click="$set('selectedAvatar', 'avatar_{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}')"
                     class="cursor-pointer {{ $selectedAvatar === 'avatar_' . str_pad($i, 2, '0', STR_PAD_LEFT) ? 'ring-4 ring-primary' : '' }}">
            @endfor
        </div>
    @endif

    <!-- Skip Message -->
    @if($mode === 'skip')
        <p class="text-sm text-gray-500">
            You can add a photo later from your profile settings.
        </p>
    @endif

    <button wire:click="save" class="btn btn-primary">Continue</button>
</div>
```

---

## Trust Score System

### How It Works

```php
// Low trust users see gentle prompts
if (auth()->user()->profile_trust_score < 70) {
    // Show banner: "Add a photo to build trust"
}

// High trust users get benefits
if (auth()->user()->profile_trust_score >= 100) {
    // Verified badge
    // Higher visibility in search
    // Can host paid events
}
```

---

## Recommendation

**Phase 1** (Immediate):
1. Make profile picture **optional** during onboarding
2. Use **DiceBear API** for default avatars (zero setup)
3. Add gentle nudge banner for users without photos

**Phase 2** (Later):
1. Create custom avatar library (30 SVGs)
2. Implement trust score system
3. Add avatar selection UI

**Phase 3** (Future):
1. Verified photo badges
2. Trust score affects search ranking
3. Photo verification for paid event hosts

---

## Next Steps

1. **Fix MinIO SSL** (Issue 1) - Add DNS record for `storage.funlynk.com`
2. **Implement Avatar Fallback** (Issue 2) - Make profile picture optional
3. **Test both flows** - Upload photo vs choose avatar

Which would you like to tackle first?

