# Agent A Tasks - Discovery Feeds & Map View UI

**Agent**: Agent A (UI/UX Specialist)  
**Sprint**: E04 Discovery Engine  
**Duration**: Week 1 (Days 5-7) + Week 2 (Integration)

---

## 🎯 Your Mission

Build the user-facing discovery interfaces that make FunLynk's Posts vs Events architecture come alive.

**Key Principle**: Posts are ephemeral and spontaneous (24-48h), Events are structured and persistent. The UI must reflect this difference.

---

## 📋 Task List

### **Task 1: Nearby Feed UI** (Priority: P0)

**Route**: `/feed/nearby`

**Files to Create**:
- `app/Livewire/Discovery/NearbyFeed.php`
- `resources/views/livewire/discovery/nearby-feed.blade.php`

**Requirements**:
1. **Feed Layout**:
   - Infinite scroll with Livewire pagination
   - Mix of Posts and Events cards
   - Posts shown first (temporal priority)
   - Load 20 items per page

2. **Post Cards** (Ephemeral styling):
   - Smaller, more casual design
   - Expiration countdown timer (e.g., "Expires in 18h")
   - "I'm down" and "Join me" reaction buttons
   - Reaction count display
   - Location + distance (e.g., "2.3 km away")
   - Time hint (e.g., "Tonight around 8pm")

3. **Event Cards** (Structured styling):
   - Larger, more formal design
   - Exact date/time
   - RSVP button
   - Price (if paid)
   - Spots remaining
   - Badge if "Converted from Post"

4. **Filters**:
   - Distance slider (1-10km for posts, 1-50km for events)
   - Content type toggle (All / Posts Only / Events Only)
   - Time filter (Today / This Week / This Month)

**Livewire Component Structure**:
```php
class NearbyFeed extends Component
{
    public $radius = 10; // km
    public $contentType = 'all'; // all, posts, events
    public $timeFilter = 'all';
    
    public function render()
    {
        $feed = app(FeedService::class)->getNearbyFeed(
            auth()->user(),
            $this->radius,
            $this->contentType,
            $this->timeFilter
        );
        
        return view('livewire.discovery.nearby-feed', [
            'items' => $feed
        ]);
    }
    
    public function reactToPost($postId, $reactionType)
    {
        app(PostService::class)->reactToPost($postId, $reactionType);
        $this->dispatch('post-reacted');
    }
}
```

**Galaxy Theme Requirements**:
- Glass cards with `lg:rounded-xl`
- Edge-to-edge on mobile, rounded on desktop
- Gradient buttons for reactions
- Cyan glow on hover
- Aurora background

---

### **Task 2: For You Feed UI** (Priority: P1)

**Route**: `/feed/for-you`

**Files to Create**:
- `app/Livewire/Discovery/ForYouFeed.php`
- `resources/views/livewire/discovery/for-you-feed.blade.php`

**Requirements**:
1. **Personalized Feed**:
   - Same card layout as Nearby Feed
   - "Why you're seeing this" explanations
   - Examples: "Based on your interest in Basketball", "Popular in your area"

2. **Empty State**:
   - Show when no recommendations
   - Suggest completing profile (interests, location)
   - Link to profile edit page

**Livewire Component Structure**:
```php
class ForYouFeed extends Component
{
    public function render()
    {
        $feed = app(FeedService::class)->getForYouFeed(auth()->user());
        
        return view('livewire.discovery.for-you-feed', [
            'items' => $feed
        ]);
    }
}
```

---

### **Task 3: Map View UI** (Priority: P0)

**Route**: `/map`

**Files to Create**:
- `app/Livewire/Discovery/MapView.php`
- `resources/views/livewire/discovery/map-view.blade.php`

**Requirements**:
1. **Google Maps Integration**:
   - Full-screen map (minus navbar)
   - User's current location as center
   - Custom markers for Posts vs Events

2. **Custom Markers**:
   - **Posts**: Pink/purple gradient pin with pulse animation
   - **Events**: Blue/cyan solid pin
   - **Converted Events**: Purple pin with star badge

3. **Marker Click Behavior**:
   - Show preview card overlay
   - Card shows: title, time, distance, quick actions
   - "View Details" button → navigate to detail page

4. **Map Controls**:
   - Zoom controls
   - "Center on Me" button
   - Distance radius overlay (visual circle)
   - Filter toggle (Posts / Events / Both)

**Livewire Component Structure**:
```php
class MapView extends Component
{
    public $userLat;
    public $userLng;
    public $radius = 10;
    public $contentType = 'all';
    
    public function mount()
    {
        $this->userLat = auth()->user()->latitude;
        $this->userLng = auth()->user()->longitude;
    }
    
    public function getMapData()
    {
        return app(FeedService::class)->getMapData(
            auth()->user(),
            $this->radius,
            $this->contentType
        );
    }
    
    public function render()
    {
        return view('livewire.discovery.map-view', [
            'mapData' => $this->getMapData()
        ]);
    }
}
```

**Google Maps Setup**:
- Use existing Google Maps API key from `.env`
- Apply dark theme (galaxy aesthetic)
- Custom marker icons (SVG or PNG)

---

### **Task 4: Post Card Component** (Priority: P0)

**Files to Create**:
- `resources/views/components/post-card.blade.php`

**Requirements**:
1. **Reusable Blade Component**:
   - Accepts `$post` prop
   - Shows all post details
   - Reaction buttons
   - Expiration timer

2. **Styling**:
   - Glass card with gradient border
   - Compact design (smaller than event cards)
   - Reaction buttons with gradient
   - Countdown timer with pulse animation

**Component Structure**:
```blade
@props(['post'])

<div class="relative p-4 glass-card lg:rounded-xl border-l-4 border-pink-500">
    {{-- Expiration Timer --}}
    <div class="absolute top-4 right-4">
        <span class="text-xs text-gray-400">
            Expires in {{ $post->timeUntilExpiration() }}
        </span>
    </div>
    
    {{-- Content --}}
    <h3 class="text-lg font-bold mb-2">{{ $post->title }}</h3>
    <p class="text-gray-400 text-sm mb-4">{{ $post->description }}</p>
    
    {{-- Location & Time --}}
    <div class="flex items-center gap-4 text-sm text-gray-400 mb-4">
        <span>📍 {{ $post->location_name }}</span>
        <span>🕐 {{ $post->time_hint }}</span>
    </div>
    
    {{-- Reactions --}}
    <div class="flex items-center gap-2">
        <button wire:click="reactToPost({{ $post->id }}, 'im_down')" 
                class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg text-sm">
            👍 I'm down ({{ $post->imDownCount() }})
        </button>
        <button wire:click="reactToPost({{ $post->id }}, 'join_me')" 
                class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg text-sm">
            🙋 Join me ({{ $post->joinMeCount() }})
        </button>
    </div>
</div>
```

---

## 🔗 Dependencies

**Wait for Agent C** (Days 1-2):
- Post model with relationships
- PostReaction model
- Database seeded with test posts

**Wait for Agent B** (Days 3-4):
- FeedService with `getNearbyFeed()`, `getForYouFeed()`, `getMapData()`
- PostService with `reactToPost()`

---

## ✅ Definition of Done

- [ ] Nearby Feed displays Posts and Events with correct styling
- [ ] For You Feed shows personalized recommendations
- [ ] Map View displays custom markers for Posts vs Events
- [ ] Post Card component is reusable and styled correctly
- [ ] Reaction buttons work and update counts in real-time
- [ ] Expiration timers count down correctly
- [ ] All views are mobile-responsive (edge-to-edge on mobile)
- [ ] Galaxy theme applied consistently
- [ ] Infinite scroll works smoothly
- [ ] Map markers are clickable with preview cards

---

## 📚 Key Files to Reference

1. `resources/views/livewire/activities/activity-detail.blade.php` - Galaxy theme example
2. `resources/views/layouts/app.blade.php` - Global CSS
3. `context-engine/domain-contexts/ui-design-standards.md` - UI guidelines
4. `app/Services/FeedService.php` - Agent B's service (wait for this)
5. `app/Models/Post.php` - Agent C's model (wait for this)

---

## 🚀 Start Date

**Day 5** (after Agent C completes models and Agent B completes services)

Good luck! 🎨

