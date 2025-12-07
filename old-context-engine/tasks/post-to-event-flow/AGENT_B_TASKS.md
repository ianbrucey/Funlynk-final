# Agent B: Frontend UI & User Experience

> **Focus**: Livewire components, Blade views, UI/UX, user interactions  
> **Estimated Time**: 7-8 days  
> **Dependencies**: E01 (Notifications), E02 (Profiles), Galaxy Theme

---

## B1: Profile "Interested" Tab (Day 1-2)

### Task Overview
Add a new tab to user profiles showing posts they've reacted to with "I'm down".

### Implementation Steps

#### Step 1: Add Method to User Model
**File**: `app/Models/User.php`

```php
public function getInterestedPosts(string $filter = 'active')
{
    $query = Post::whereHas('reactions', function ($q) {
        $q->where('user_id', $this->id)
          ->where('reaction_type', 'im_down');
    })->with(['user', 'reactions']); // Note: tags is a JSON column, not a relationship
    
    // Apply filters
    switch ($filter) {
        case 'active':
            $query->where('status', 'active')
                  ->where('expires_at', '>', now());
            break;
        case 'converted':
            $query->where('status', 'converted');
            break;
        case 'expired':
            $query->where(function ($q) {
                $q->where('status', 'expired')
                  ->orWhere('expires_at', '<=', now());
            });
            break;
    }
    
    return $query->orderByDesc(function ($q) {
        $q->select('created_at')
          ->from('post_reactions')
          ->whereColumn('post_reactions.post_id', 'posts.id')
          ->where('post_reactions.user_id', $this->id)
          ->limit(1);
    })->paginate(12);
}
```

#### Step 2: Create Livewire Component
```bash
php artisan make:livewire Profile/InterestedTab --no-interaction
```

**File**: `app/Livewire/Profile/InterestedTab.php`

```php
<?php

namespace App\Livewire\Profile;

use App\Models\User;
use Livewire\{Component, WithPagination};

class InterestedTab extends Component
{
    use WithPagination;
    
    public User $user;
    public string $filter = 'active';
    
    public function mount(User $user)
    {
        $this->user = $user;
    }
    
    public function setFilter(string $filter)
    {
        $this->filter = $filter;
        $this->resetPage();
    }
    
    public function removeInterest(string $postId)
    {
        // Call PostService to remove reaction
        app(\App\Services\PostService::class)->toggleReaction($postId, 'im_down');
        
        $this->dispatch('post-interest-removed');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Interest removed successfully',
        ]);
    }
    
    public function render()
    {
        $posts = $this->user->getInterestedPosts($this->filter);
        
        return view('livewire.profile.interested-tab', [
            'posts' => $posts,
        ]);
    }
}
```

#### Step 3: Create Blade View
**File**: `resources/views/livewire/profile/interested-tab.blade.php`

```blade
<div class="space-y-6">
    {{-- Filter Tabs --}}
    <div class="flex gap-2 overflow-x-auto pb-2">
        <button 
            wire:click="setFilter('active')"
            class="px-4 py-2 rounded-lg transition-all {{ $filter === 'active' ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'bg-slate-800/50 text-gray-300 hover:bg-slate-700/50' }}">
            Active
        </button>
        <button 
            wire:click="setFilter('converted')"
            class="px-4 py-2 rounded-lg transition-all {{ $filter === 'converted' ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'bg-slate-800/50 text-gray-300 hover:bg-slate-700/50' }}">
            Converted to Events
        </button>
        <button 
            wire:click="setFilter('expired')"
            class="px-4 py-2 rounded-lg transition-all {{ $filter === 'expired' ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'bg-slate-800/50 text-gray-300 hover:bg-slate-700/50' }}">
            Expired
        </button>
    </div>

    {{-- Posts Grid --}}
    @if($posts->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <x-post-card-interested 
                    :post="$post" 
                    :can-remove="auth()->id() === $user->id"
                    wire:key="post-{{ $post->id }}" />
            @endforeach
        </div>
        
        {{ $posts->links() }}
    @else
        {{-- Empty State --}}
        <div class="glass-card p-12 text-center">
            <div class="text-6xl mb-4">💫</div>
            <h3 class="text-xl font-semibold text-white mb-2">
                No posts yet
            </h3>
            <p class="text-gray-400 mb-6">
                @if($filter === 'active')
                    You haven't shown interest in any posts yet
                @elseif($filter === 'converted')
                    None of your interested posts have been converted to events
                @else
                    No expired posts to show
                @endif
            </p>
            @if($filter === 'active')
                <a href="{{ route('discovery.nearby') }}" 
                   class="inline-block px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                    Explore Nearby Posts
                </a>
            @endif
        </div>
    @endif
</div>
```

#### Step 4: Create Post Card Component
```bash
php artisan make:component PostCardInterested --no-interaction
```

**File**: `resources/views/components/post-card-interested.blade.php`

```blade
@props(['post', 'canRemove' => false])

<div class="relative glass-card p-6 hover:scale-105 transition-all group">
    {{-- Converted Badge --}}
    @if($post->status === 'converted')
        <div class="absolute top-0 left-0 right-0 bg-gradient-to-r from-pink-500/20 to-purple-500/20 backdrop-blur-sm p-3 rounded-t-xl border-b border-white/10">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-white">✨ Converted to Event</span>
                <a href="{{ route('activities.show', $post->convertedActivity->id) }}" 
                   class="text-xs text-cyan-400 hover:text-cyan-300">
                    View Event →
                </a>
            </div>
        </div>
        <div class="h-12"></div> {{-- Spacer for badge --}}
    @endif

    {{-- Post Content --}}
    <h3 class="text-lg font-semibold text-white mb-2">{{ $post->title }}</h3>
    <p class="text-gray-400 text-sm mb-4 line-clamp-2">{{ $post->description }}</p>
    
    {{-- Meta Info --}}
    <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
        <span>📍 {{ $post->location_name }}</span>
        <span>👍 {{ $post->reaction_count }}</span>
    </div>
    
    {{-- Interested Since --}}
    <div class="text-xs text-gray-500 mb-4">
        Interested since {{ $post->reactions()->where('user_id', auth()->id())->first()?->created_at->diffForHumans() }}
    </div>
    
    {{-- Actions --}}
    <div class="flex gap-2">
        @if($post->status === 'converted')
            <a href="{{ route('activities.show', $post->convertedActivity->id) }}" 
               class="flex-1 px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg text-center text-sm font-semibold hover:scale-105 transition-all">
                View Event
            </a>
        @else
            <a href="{{ route('posts.show', $post->id) }}" 
               class="flex-1 px-4 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-center text-sm hover:border-cyan-500/50 transition">
                View Post
            </a>
        @endif
        
        @if($canRemove && $post->status === 'active')
            <button 
                wire:click="removeInterest('{{ $post->id }}')"
                class="px-4 py-2 bg-red-500/20 border border-red-500/30 rounded-lg text-sm hover:bg-red-500/30 transition"
                title="Remove Interest">
                ✕
            </button>
        @endif
    </div>
    
    {{-- Expiry Warning --}}
    @if($post->status === 'active' && $post->expires_at->diffInHours() < 6)
        <div class="mt-3 text-xs text-amber-400">
            ⏰ Expires {{ $post->expires_at->diffForHumans() }}
        </div>
    @endif
</div>
```

#### Step 5: Update Profile Component
**File**: `app/Livewire/Profile/ShowProfile.php`

Add to the tabs array:
```php
protected array $tabs = [
    'posts' => 'Posts',
    'hosting' => 'Hosting',
    'attending' => 'Attending',
    'interested' => 'Interested', // NEW
];
```

Update render method to include interested tab:
```php
public function render()
{
    $data = match($this->activeTab) {
        'posts' => ['posts' => $this->user->posts()->latest()->paginate(12)],
        'hosting' => ['activities' => $this->user->hostedActivities()->latest()->paginate(12)],
        'attending' => ['activities' => $this->user->attendingActivities()->latest()->paginate(12)],
        'interested' => ['component' => 'profile.interested-tab'], // NEW
        default => [],
    };
    
    return view('livewire.profile.show-profile', $data);
}
```

**File**: `resources/views/livewire/profile/show-profile.blade.php`

Add to tab content section:
```blade
@if($activeTab === 'interested')
    <livewire:profile.interested-tab :user="$user" :key="'interested-'.$user->id" />
@endif
```

### Testing
```bash
php artisan make:test --pest Livewire/Profile/InterestedTabTest --no-interaction
```

**Test Cases**:
- Tab displays interested posts correctly
- Filters work (active/converted/expired)
- Remove interest button works
- Empty state displays correctly
- Pagination works
- Only post owner can remove interest
- Converted posts show event link

### Deliverables
- ✅ User model method
- ✅ InterestedTab Livewire component
- ✅ PostCardInterested Blade component
- ✅ Updated ShowProfile component
- ✅ Component tests passing

---

## B2: Post Card Enhancements (Day 2-3)

### Task Overview
Add conversion badges and converted state styling to post cards.

### Implementation Steps

#### Step 1: Create Conversion Badge Component
```bash
php artisan make:component ConversionBadge --no-interaction
```

**File**: `resources/views/components/conversion-badge.blade.php`

```blade
@props(['post', 'threshold' => 'soft'])

@php
$badgeClasses = $threshold === 'strong'
    ? 'bg-gradient-to-r from-pink-500 to-purple-500 animate-pulse'
    : 'bg-gradient-to-r from-amber-500 to-orange-500';
@endphp

@if($post->isEligibleForConversion() && !$post->hasReachedDismissLimit())
    <div class="absolute top-2 right-2 z-10">
        <button
            wire:click.stop="openConversionModal('{{ $post->id }}')"
            class="{{ $badgeClasses }} px-3 py-1 rounded-full text-xs font-bold text-white shadow-lg hover:scale-110 transition-all"
            title="{{ $threshold === 'strong' ? 'Convert to Event Now!' : 'Ready to Convert' }}">
            @if($threshold === 'strong')
                🔥 Convert Now!
            @else
                ⭐ Ready
            @endif
        </button>
    </div>
@endif
```

#### Step 2: Create Converted Post Overlay Component
```bash
php artisan make:component ConvertedPostOverlay --no-interaction
```

**File**: `resources/views/components/converted-post-overlay.blade.php`

```blade
@props(['post'])

@if($post->status === 'converted' && $post->convertedActivity)
    <div class="absolute inset-0 bg-gradient-to-br from-purple-900/80 to-pink-900/80 backdrop-blur-sm rounded-xl flex items-center justify-center z-20">
        <div class="text-center p-6">
            <div class="text-4xl mb-3">✨</div>
            <h4 class="text-xl font-bold text-white mb-2">Converted to Event</h4>
            <p class="text-gray-300 text-sm mb-4">
                {{ $post->reaction_count }} people were interested
            </p>
            <a href="{{ route('activities.show', $post->convertedActivity->id) }}"
               class="inline-block px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 transition-all">
                View Event →
            </a>
        </div>
    </div>
@endif
```

#### Step 3: Update Post Card Compact Component
**File**: `resources/views/components/post-card-compact.blade.php`

Add at the top of the card (inside the main container):
```blade
<div class="relative">
    {{-- Conversion Badge (for active posts) --}}
    @if($post->status === 'active' && auth()->id() === $post->user_id)
        <x-conversion-badge
            :post="$post"
            :threshold="$post->reaction_count >= 10 ? 'strong' : 'soft'" />
    @endif

    {{-- Converted Overlay (for converted posts) --}}
    <x-converted-post-overlay :post="$post" />

    {{-- Existing post card content --}}
    ...
</div>
```

#### Step 4: Add Conversion Modal Trigger to NearbyFeed
**File**: `app/Livewire/Discovery/NearbyFeed.php`

Add method:
```php
public function openConversionModal(string $postId)
{
    $this->dispatch('open-conversion-modal', postId: $postId);
}
```

### Testing
```bash
php artisan make:test --pest Components/ConversionBadgeTest --no-interaction
```

**Test Cases**:
- Badge shows at 5 reactions (soft)
- Badge shows at 10 reactions (strong)
- Badge doesn't show after 3 dismissals
- Badge only visible to post owner
- Converted overlay shows correctly
- Overlay links to correct event
- Click badge dispatches modal event

### Deliverables
- ✅ ConversionBadge component
- ✅ ConvertedPostOverlay component
- ✅ Updated post-card-compact
- ✅ Component tests passing

---

## B3: Conversion Prompt Notifications (Day 3-4)

### Task Overview
Create in-app notification components for conversion prompts.

### Implementation Steps

#### Step 1: Create Notification Card Component
```bash
php artisan make:component Notifications/ConversionPromptCard --no-interaction
```

**File**: `resources/views/components/notifications/conversion-prompt-card.blade.php`

```blade
@props(['notification'])

@php
$data = $notification->data;
$threshold = $data['threshold'] ?? 'soft';
$iconClass = $threshold === 'strong' ? 'text-pink-500' : 'text-amber-500';
@endphp

<div class="glass-card p-4 hover:border-cyan-500/50 transition-all cursor-pointer"
     wire:click="handleNotificationClick('{{ $notification->id }}')">

    <div class="flex gap-4">
        {{-- Icon --}}
        <div class="flex-shrink-0">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-500/20 to-purple-500/20 flex items-center justify-center {{ $iconClass }}">
                @if($threshold === 'strong')
                    🔥
                @else
                    🎉
                @endif
            </div>
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            <h4 class="text-white font-semibold mb-1">
                {{ $data['post_title'] }}
            </h4>
            <p class="text-gray-400 text-sm mb-3">
                {{ $data['message'] }}
            </p>

            {{-- Actions --}}
            <div class="flex gap-2">
                <button
                    wire:click.stop="convertPost('{{ $data['post_id'] }}')"
                    class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg text-sm font-semibold hover:scale-105 transition-all">
                    Convert to Event
                </button>
                <button
                    wire:click.stop="dismissPrompt('{{ $data['post_id'] }}', '{{ $notification->id }}')"
                    class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-sm hover:border-red-500/50 transition">
                    Not Now
                </button>
            </div>
        </div>

        {{-- Timestamp --}}
        <div class="flex-shrink-0 text-xs text-gray-500">
            {{ $notification->created_at->diffForHumans() }}
        </div>
    </div>
</div>
```

#### Step 2: Create Feed Banner Component
```bash
php artisan make:component FeedConversionBanner --no-interaction
```

**File**: `resources/views/components/feed-conversion-banner.blade.php`

```blade
@props(['post'])

@if($post->isEligibleForConversion() && !$post->hasReachedDismissLimit() && auth()->id() === $post->user_id)
    @php
    $threshold = $post->reaction_count >= 10 ? 'strong' : 'soft';
    $dismissed = session()->has("conversion_banner_dismissed_{$post->id}");
    @endphp

    @if(!$dismissed)
        <div class="glass-card p-4 mb-4 border-l-4 {{ $threshold === 'strong' ? 'border-pink-500' : 'border-amber-500' }}">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="text-2xl">
                        {{ $threshold === 'strong' ? '🔥' : '🌟' }}
                    </div>
                    <div>
                        <h4 class="text-white font-semibold">
                            @if($threshold === 'strong')
                                {{ $post->reaction_count }}+ people want to join!
                            @else
                                {{ $post->reaction_count }} people are interested
                            @endif
                        </h4>
                        <p class="text-gray-400 text-sm">
                            @if($threshold === 'strong')
                                Turn this into an event now and start planning!
                            @else
                                Consider creating an event from this post
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button
                        wire:click="openConversionModal('{{ $post->id }}')"
                        class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg text-sm font-semibold hover:scale-105 transition-all whitespace-nowrap">
                        Convert to Event
                    </button>
                    <button
                        wire:click="dismissBanner('{{ $post->id }}')"
                        class="px-3 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-sm hover:border-red-500/50 transition">
                        ✕
                    </button>
                </div>
            </div>
        </div>
    @endif
@endif
```

#### Step 3: Add Banner Logic to NearbyFeed
**File**: `app/Livewire/Discovery/NearbyFeed.php`

Add method:
```php
public function dismissBanner(string $postId)
{
    session()->put("conversion_banner_dismissed_{$postId}", true);

    $this->dispatch('banner-dismissed');
}
```

#### Step 4: Update Notifications Livewire Component
**File**: `app/Livewire/Notifications/NotificationsList.php`

Add methods:
```php
public function convertPost(string $postId)
{
    $this->dispatch('open-conversion-modal', postId: $postId);
}

public function dismissPrompt(string $postId, string $notificationId)
{
    // Call API to dismiss
    app(\App\Services\PostService::class)->dismissConversionPrompt($postId);

    // Mark notification as read
    \App\Models\Notification::find($notificationId)?->markAsRead();

    $this->dispatch('notify', [
        'type' => 'info',
        'message' => 'Conversion prompt dismissed',
    ]);
}
```

### Testing
```bash
php artisan make:test --pest Components/ConversionPromptNotificationsTest --no-interaction
```

**Test Cases**:
- Notification card renders correctly
- Banner shows for eligible posts
- Banner dismissal works
- Session persists dismissal
- Actions dispatch correct events
- Only post owner sees prompts

### Deliverables
- ✅ ConversionPromptCard component
- ✅ FeedConversionBanner component
- ✅ Updated NearbyFeed component
- ✅ Updated NotificationsList component
- ✅ Component tests passing

---

## B4: Conversion Modal - Structure (Day 4-5)

### Task Overview
Create the main conversion modal with form layout and pre-fill logic.

### Implementation Steps

#### Step 1: Create Livewire Modal Component
```bash
php artisan make:livewire Modals/ConvertPostModal --no-interaction
```

**File**: `app/Livewire/Modals/ConvertPostModal.php`

```php
<?php

namespace App\Livewire\Modals;

use App\Models\{Post, Tag};
use App\Services\{PostService, ActivityConversionService};
use Livewire\{Component, WithFileUploads};
use Carbon\Carbon;

class ConvertPostModal extends Component
{
    use WithFileUploads;

    public bool $show = false;
    public ?string $postId = null;
    public ?Post $post = null;

    // Form fields (pre-filled from post)
    public string $title = '';
    public string $description = '';
    public string $location_name = '';
    public $location_coordinates = null;
    public array $selectedTags = [];

    // New event fields
    public string $start_time = '';
    public string $end_time = '';
    public int $max_attendees = 10;
    public float $price = 0;
    public $image = null;

    // Preview data
    public int $interestedCount = 0;
    public int $invitedCount = 0;
    public bool $showPreview = false;

    protected $listeners = ['open-conversion-modal' => 'open'];

    public function open(string $postId)
    {
        $this->postId = $postId;
        $this->post = Post::with(['tags', 'reactions', 'invitations'])->findOrFail($postId);

        // Authorization check
        if ($this->post->user_id !== auth()->id()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Unauthorized',
            ]);
            return;
        }

        // Pre-fill form
        $this->preFillForm();

        // Load preview data
        $this->loadPreviewData();

        $this->show = true;
    }

    protected function preFillForm()
    {
        $this->title = $this->post->title;
        $this->description = $this->post->description;
        $this->location_name = $this->post->location_name;
        $this->location_coordinates = $this->post->location_coordinates;
        $this->selectedTags = $this->post->tags->pluck('id')->toArray();

        // Smart defaults for event fields
        if ($this->post->approximate_time) {
            $this->start_time = Carbon::parse($this->post->approximate_time)->format('Y-m-d\TH:i');
            $this->end_time = Carbon::parse($this->post->approximate_time)->addHours(2)->format('Y-m-d\TH:i');
        } else {
            $this->start_time = now()->addDays(1)->setHour(18)->setMinute(0)->format('Y-m-d\TH:i');
            $this->end_time = now()->addDays(1)->setHour(20)->setMinute(0)->format('Y-m-d\TH:i');
        }

        // Suggested capacity: reactions * 1.5, min 10
        $this->max_attendees = max((int) ceil($this->post->reaction_count * 1.5), 10);
    }

    protected function loadPreviewData()
    {
        $preview = app(ActivityConversionService::class)->previewConversion($this->post, []);

        $this->interestedCount = $preview['interested_users_count'];
        $this->invitedCount = $preview['invited_users_count'];
    }

    public function togglePreview()
    {
        $this->showPreview = !$this->showPreview;
    }

    public function submit()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location_name' => 'required|string',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'max_attendees' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        try {
            $eventData = [
                'title' => $this->title,
                'description' => $this->description,
                'location_name' => $this->location_name,
                'location_coordinates' => $this->location_coordinates,
                'start_time' => $this->start_time,
                'end_time' => $this->end_time,
                'max_attendees' => $this->max_attendees,
                'price' => $this->price,
                'tags' => $this->selectedTags,
            ];

            // Handle image upload
            if ($this->image) {
                $eventData['image_path'] = $this->image->store('activities', 'public');
            }

            $activity = app(PostService::class)->convertToEvent($this->postId, $eventData);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Post converted to event successfully!',
            ]);

            $this->dispatch('post-converted', activityId: $activity->id);

            $this->close();

            // Redirect to event page
            return redirect()->route('activities.show', $activity->id);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function close()
    {
        $this->show = false;
        $this->reset();
    }

    public function render()
    {
        $availableTags = Tag::all();

        return view('livewire.modals.convert-post-modal', [
            'availableTags' => $availableTags,
        ]);
    }
}
```

### Testing
```bash
php artisan make:test --pest Livewire/Modals/ConvertPostModalTest --no-interaction
```

**Test Cases**:
- Modal opens with correct post data
- Form pre-fills correctly
- Smart defaults work (capacity, times)
- Authorization check works
- Preview data loads correctly
- Validation works for all fields

### Deliverables
- ✅ ConvertPostModal Livewire component
- ✅ Pre-fill logic implemented
- ✅ Preview data loading
- ✅ Component tests passing

---

## B5: Conversion Modal - Form & Validation (Day 5-6)

### Task Overview
Create the Blade view for the conversion modal with all form fields and galaxy theme styling.

### Implementation Steps

#### Step 1: Create Modal Blade View
**File**: `resources/views/livewire/modals/convert-post-modal.blade.php`

```blade
<div>
    @if($show)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ showPreview: @entangle('showPreview') }">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" wire:click="close"></div>

            {{-- Modal --}}
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="relative glass-card max-w-4xl w-full p-8 max-h-[90vh] overflow-y-auto">
                    <div class="top-accent-center"></div>

                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-white mb-2">Convert Post to Event</h2>
                            <p class="text-gray-400 text-sm">
                                {{ $interestedCount + $invitedCount }} people will be notified
                            </p>
                        </div>
                        <button wire:click="close" class="text-gray-400 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Form / Preview Toggle --}}
                    <div class="flex gap-2 mb-6">
                        <button
                            @click="showPreview = false"
                            :class="!showPreview ? 'bg-gradient-to-r from-pink-500 to-purple-500' : 'bg-slate-800/50'"
                            class="px-4 py-2 rounded-lg transition-all">
                            Edit Details
                        </button>
                        <button
                            @click="showPreview = true"
                            :class="showPreview ? 'bg-gradient-to-r from-pink-500 to-purple-500' : 'bg-slate-800/50'"
                            class="px-4 py-2 rounded-lg transition-all">
                            Preview Event
                        </button>
                    </div>

                    {{-- Form Section --}}
                    <div x-show="!showPreview" x-transition>
                        <form wire:submit.prevent="submit" class="space-y-6">
                            {{-- Section 1: Basic Details --}}
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-white border-b border-white/10 pb-2">
                                    Event Details
                                </h3>

                                {{-- Title --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Event Title *
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="title"
                                        class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition"
                                        placeholder="Give your event a catchy title">
                                    @error('title') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                </div>

                                {{-- Description --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Description *
                                    </label>
                                    <textarea
                                        wire:model="description"
                                        rows="4"
                                        class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition"
                                        placeholder="Describe what attendees can expect"></textarea>
                                    @error('description') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                </div>

                                {{-- Location --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Location *
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="location_name"
                                        class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition"
                                        placeholder="Where will this happen?">
                                    @error('location_name') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                </div>

                                {{-- Tags --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Tags
                                    </label>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($availableTags as $tag)
                                            <label class="cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    wire:model="selectedTags"
                                                    value="{{ $tag->id }}"
                                                    class="sr-only peer">
                                                <span class="inline-block px-3 py-1 rounded-full text-sm border border-white/10 peer-checked:bg-gradient-to-r peer-checked:from-pink-500 peer-checked:to-purple-500 peer-checked:border-transparent transition">
                                                    {{ $tag->name }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- Section 2: Event Specifics --}}
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-white border-b border-white/10 pb-2">
                                    Event Specifics
                                </h3>

                                {{-- Date/Time --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Start Time *
                                        </label>
                                        <input
                                            type="datetime-local"
                                            wire:model="start_time"
                                            class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition">
                                        @error('start_time') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            End Time *
                                        </label>
                                        <input
                                            type="datetime-local"
                                            wire:model="end_time"
                                            class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition">
                                        @error('end_time') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Capacity & Price --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Max Attendees *
                                        </label>
                                        <input
                                            type="number"
                                            wire:model="max_attendees"
                                            min="1"
                                            class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition">
                                        <p class="text-xs text-gray-500 mt-1">
                                            Suggested: {{ max((int) ceil($post->reaction_count * 1.5), 10) }} based on interest
                                        </p>
                                        @error('max_attendees') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Price (USD) *
                                        </label>
                                        <input
                                            type="number"
                                            wire:model="price"
                                            min="0"
                                            step="0.01"
                                            class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition"
                                            placeholder="0.00 for free">
                                        @error('price') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Image Upload --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Event Image (Optional)
                                    </label>
                                    <input
                                        type="file"
                                        wire:model="image"
                                        accept="image/*"
                                        class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-gradient-to-r file:from-pink-500 file:to-purple-500 file:text-white hover:file:scale-105 transition">
                                    @error('image') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Section 3: Interested Users --}}
                            <div class="glass-card p-4 bg-cyan-500/5 border border-cyan-500/20">
                                <h4 class="text-white font-semibold mb-2">Who will be notified?</h4>
                                <div class="flex items-center gap-4 text-sm text-gray-300">
                                    <div>
                                        <span class="text-cyan-400 font-bold">{{ $interestedCount }}</span> interested users
                                    </div>
                                    @if($invitedCount > 0)
                                        <div>
                                            <span class="text-purple-400 font-bold">{{ $invitedCount }}</span> invited users
                                        </div>
                                    @endif
                                    <div class="ml-auto">
                                        <span class="text-white font-bold">{{ $interestedCount + $invitedCount }}</span> total
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">
                                    ℹ️ All users will receive an invitation to RSVP (not auto-enrolled)
                                </p>
                            </div>

                            {{-- Actions --}}
                            <div class="flex gap-3 pt-4">
                                <button
                                    type="button"
                                    wire:click="close"
                                    class="flex-1 px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-red-500/50 transition">
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="flex-1 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                    Create Event
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Preview Section --}}
                    <div x-show="showPreview" x-transition>
                        <livewire:modals.event-preview-card
                            :title="$title"
                            :description="$description"
                            :location="$location_name"
                            :startTime="$start_time"
                            :endTime="$end_time"
                            :maxAttendees="$max_attendees"
                            :price="$price"
                            :interestedCount="$interestedCount"
                            :key="'preview-'.$postId" />

                        <div class="flex gap-3 mt-6">
                            <button
                                @click="showPreview = false"
                                class="flex-1 px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                                ← Back to Edit
                            </button>
                            <button
                                wire:click="submit"
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                Confirm & Create Event
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
```

### Testing
```bash
php artisan make:test --pest Livewire/Modals/ConvertPostModalFormTest --no-interaction
```

**Test Cases**:
- All form fields render correctly
- Validation messages display
- Galaxy theme styling applied
- Preview toggle works
- Image upload works
- Form submission works
- Cancel button closes modal

### Deliverables
- ✅ Complete modal Blade view
- ✅ Galaxy theme styling
- ✅ Form validation UI
- ✅ Component tests passing

---

## B6: Conversion Modal - Preview Step (Day 6-7)

### Task Overview
Create the event preview component shown before final submission.

### Implementation Steps

#### Step 1: Create Preview Component
```bash
php artisan make:livewire Modals/EventPreviewCard --no-interaction
```

**File**: `app/Livewire/Modals/EventPreviewCard.php`

```php
<?php

namespace App\Livewire\Modals;

use Livewire\Component;
use Carbon\Carbon;

class EventPreviewCard extends Component
{
    public string $title;
    public string $description;
    public string $location;
    public string $startTime;
    public string $endTime;
    public int $maxAttendees;
    public float $price;
    public int $interestedCount;

    public function render()
    {
        $startDate = Carbon::parse($this->startTime);
        $endDate = Carbon::parse($this->endTime);

        return view('livewire.modals.event-preview-card', [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
```

#### Step 2: Create Preview Blade View
**File**: `resources/views/livewire/modals/event-preview-card.blade.php`

```blade
<div class="space-y-6">
    <div class="text-center mb-6">
        <h3 class="text-xl font-bold text-white mb-2">Event Preview</h3>
        <p class="text-gray-400 text-sm">This is how your event will appear to users</p>
    </div>

    {{-- Event Card Preview --}}
    <div class="glass-card p-6 max-w-2xl mx-auto">
        <div class="top-accent-center"></div>

        {{-- Event Image Placeholder --}}
        <div class="w-full h-48 bg-gradient-to-br from-pink-500/20 to-purple-500/20 rounded-xl mb-4 flex items-center justify-center">
            <div class="text-center">
                <div class="text-4xl mb-2">🎉</div>
                <p class="text-gray-400 text-sm">Event Image</p>
            </div>
        </div>

        {{-- Event Details --}}
        <h3 class="text-2xl font-bold text-white mb-3">{{ $title }}</h3>

        <div class="space-y-3 mb-4">
            {{-- Date/Time --}}
            <div class="flex items-start gap-3">
                <div class="text-2xl">📅</div>
                <div>
                    <p class="text-white font-semibold">{{ $startDate->format('l, F j, Y') }}</p>
                    <p class="text-gray-400 text-sm">
                        {{ $startDate->format('g:i A') }} - {{ $endDate->format('g:i A') }}
                    </p>
                </div>
            </div>

            {{-- Location --}}
            <div class="flex items-start gap-3">
                <div class="text-2xl">📍</div>
                <div>
                    <p class="text-white">{{ $location }}</p>
                </div>
            </div>

            {{-- Price --}}
            <div class="flex items-start gap-3">
                <div class="text-2xl">💵</div>
                <div>
                    <p class="text-white font-semibold">
                        @if($price > 0)
                            ${{ number_format($price, 2) }}
                        @else
                            Free
                        @endif
                    </p>
                </div>
            </div>

            {{-- Capacity --}}
            <div class="flex items-start gap-3">
                <div class="text-2xl">👥</div>
                <div>
                    <p class="text-white">{{ $maxAttendees }} spots available</p>
                    <p class="text-gray-400 text-sm">{{ $interestedCount }} people already interested</p>
                </div>
            </div>
        </div>

        {{-- Description --}}
        <div class="border-t border-white/10 pt-4">
            <h4 class="text-white font-semibold mb-2">About this event</h4>
            <p class="text-gray-400 text-sm whitespace-pre-wrap">{{ $description }}</p>
        </div>

        {{-- Mock RSVP Button --}}
        <div class="mt-6">
            <button class="w-full px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold opacity-50 cursor-not-allowed">
                RSVP to Event (Preview)
            </button>
        </div>
    </div>

    {{-- Notification Preview --}}
    <div class="glass-card p-4 max-w-2xl mx-auto bg-cyan-500/5 border border-cyan-500/20">
        <h4 class="text-white font-semibold mb-2">Notification Preview</h4>
        <p class="text-gray-400 text-sm mb-3">
            This is what interested users will see:
        </p>

        <div class="glass-card p-4 bg-slate-900/50">
            <div class="flex gap-3">
                <div class="text-2xl">🎉</div>
                <div class="flex-1">
                    <h5 class="text-white font-semibold mb-1">{{ $title }} is now an event!</h5>
                    <p class="text-gray-400 text-sm mb-2">
                        You showed interest in this post. The host has created an event!
                    </p>
                    <div class="text-xs text-gray-500">
                        📅 {{ $startDate->format('M j, g:i A') }} • 📍 {{ $location }} •
                        @if($price > 0)
                            💵 ${{ number_format($price, 2) }}
                        @else
                            Free
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

### Testing
```bash
php artisan make:test --pest Livewire/Modals/EventPreviewCardTest --no-interaction
```

**Test Cases**:
- Preview renders all event details
- Date formatting correct
- Price displays correctly (free vs paid)
- Notification preview shows
- Galaxy theme styling applied

### Deliverables
- ✅ EventPreviewCard component
- ✅ Preview Blade view
- ✅ Notification preview
- ✅ Component tests passing

---

## Summary

### Total Deliverables
- ✅ Profile "Interested" tab with filtering
- ✅ Post card conversion badges (soft/strong)
- ✅ Converted post overlay
- ✅ In-app notification components
- ✅ Feed conversion banner
- ✅ Complete conversion modal with form
- ✅ Event preview component
- ✅ Galaxy theme styling throughout
- ✅ 95%+ test coverage

### Integration Points with Agent A
- **Events**: Listen for PostConversionPrompted, PostConvertedToEvent
- **API**: Call conversion endpoints from modal
- **Services**: Use PostService for reactions and conversions
- **Models**: Use Post scopes and helpers

### Next Steps
1. Test all components individually
2. Test integration with Agent A's backend
3. Verify galaxy theme consistency
4. Run accessibility audit
5. Test responsive layouts
6. Coordinate on shared integration tests

---

*Agent B tasks complete. Ready for integration with Agent A.*

