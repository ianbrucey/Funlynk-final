# Routes Architecture - Guest Event Experience

## Overview
Dual-route system: public routes for guest discovery, authenticated routes for full event management. Designed to maximize conversion while maintaining security.

## URL Structure

### Public Routes (No Authentication)

```php
// routes/web.php

// Public event viewing
Route::get('/e/{activity:slug}', PublicEventView::class)
    ->name('events.public');

// Guest engagement endpoints
Route::post('/e/{activity:slug}/interested', [GuestEngagementController::class, 'interested'])
    ->name('events.interested')
    ->middleware('throttle:10,1'); // 10 requests per minute

Route::post('/e/{activity:slug}/bookmark', [GuestEngagementController::class, 'bookmark'])
    ->name('events.bookmark')
    ->middleware('throttle:20,1');

Route::post('/e/{activity:slug}/share', [GuestEngagementController::class, 'share'])
    ->name('events.share')
    ->middleware('throttle:30,1');

// Social media share preview (for crawlers)
Route::get('/e/{activity:slug}/preview', [GuestEngagementController::class, 'preview'])
    ->name('events.preview');
```

**Design Decisions**:
- **Short URL**: `/e/` instead of `/events/` for social media character limits
- **Slug-based**: SEO-friendly, human-readable URLs
- **Throttling**: Prevent spam on engagement endpoints
- **No auth middleware**: Explicitly public

---

### Authenticated Routes (Existing)

```php
// routes/web.php

Route::middleware(['auth', 'onboarding.complete'])->group(function () {
    // Full event management (host view)
    Route::get('/activities/{activity}', ActivityDetail::class)
        ->name('activities.show');
    
    Route::get('/activities/{activity}/edit', ActivityEdit::class)
        ->name('activities.edit');
    
    // Ticket purchase
    Route::get('/activities/{activity}/checkout', CheckoutForm::class)
        ->name('activities.checkout');
    
    // RSVP management
    Route::post('/activities/{activity}/rsvp', [RsvpController::class, 'store'])
        ->name('activities.rsvp');
    
    Route::delete('/activities/{activity}/rsvp', [RsvpController::class, 'destroy'])
        ->name('activities.rsvp.cancel');
});
```

**Design Decisions**:
- **Separate namespace**: `/activities/` for authenticated, `/e/` for public
- **ID-based**: Authenticated routes use numeric IDs (more secure)
- **Full features**: Edit, checkout, RSVP management require auth

---

## Route Resolution Logic

### Slug Generation & Uniqueness

```php
// app/Models/Activity.php

protected static function boot()
{
    parent::boot();
    
    static::creating(function ($activity) {
        if (empty($activity->slug)) {
            $activity->slug = static::generateUniqueSlug($activity->title);
        }
    });
    
    static::updating(function ($activity) {
        if ($activity->isDirty('title') && !$activity->isDirty('slug')) {
            // Optionally regenerate slug on title change
            // $activity->slug = static::generateUniqueSlug($activity->title);
        }
    });
}

public static function generateUniqueSlug(string $title): string
{
    $baseSlug = Str::slug($title);
    $slug = $baseSlug;
    $counter = 1;
    
    while (static::where('slug', $slug)->exists()) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}
```

**Slug Rules**:
- Generated from title on creation
- Unique constraint enforced at DB level
- Collision handling: append counter (`-1`, `-2`, etc.)
- Optional: Regenerate on title change (decide based on SEO impact)

---

### Route Model Binding

```php
// app/Providers/RouteServiceProvider.php

public function boot()
{
    // Existing ID-based binding
    Route::bind('activity', function ($value) {
        return Activity::findOrFail($value);
    });
    
    // New slug-based binding for public routes
    Route::bind('activity:slug', function ($value) {
        return Activity::where('slug', $value)->firstOrFail();
    });
}
```

**Binding Strategy**:
- `/e/{activity:slug}` → Resolves by slug
- `/activities/{activity}` → Resolves by ID
- 404 if not found (both cases)

---

## Context Preservation Flow

### Session-Based Intent Tracking

```php
// app/Http/Middleware/CaptureIntendedAction.php

class CaptureIntendedAction
{
    public function handle(Request $request, Closure $next)
    {
        // Capture referral parameters
        if ($request->has('ref')) {
            Session::put('referral_code', $request->get('ref'));
        }
        
        // Capture UTM parameters
        if ($request->has('utm_source')) {
            Session::put('utm_params', [
                'source' => $request->get('utm_source'),
                'medium' => $request->get('utm_medium'),
                'campaign' => $request->get('utm_campaign'),
            ]);
        }
        
        return $next($request);
    }
}
```

**Apply to public routes**:
```php
Route::get('/e/{activity:slug}', PublicEventView::class)
    ->middleware('capture.intent')
    ->name('events.public');
```

---

### Post-Authentication Redirect

```php
// app/Http/Controllers/Auth/AuthenticatedSessionController.php

public function store(LoginRequest $request)
{
    $request->authenticate();
    $request->session()->regenerate();
    
    // Check for intended action
    if (Session::has('intended_action')) {
        $action = Session::pull('intended_action');
        
        return match($action['type']) {
            'rsvp' => redirect()->route('activities.checkout', $action['activity_id']),
            'view' => redirect()->route('activities.show', $action['activity_id']),
            default => redirect()->intended(route('dashboard'))
        };
    }
    
    return redirect()->intended(route('dashboard'));
}
```

**Intent Structure**:
```php
Session::put('intended_action', [
    'type' => 'rsvp',              // 'rsvp', 'view', 'interested'
    'activity_id' => 123,
    'activity_slug' => 'sunset-yoga',
    'source' => 'instagram',
    'referral_code' => 'SHa7b3c9d2',
    'timestamp' => now()->toIso8601String()
]);
```

---

## Canonical URL Strategy

### Problem: Duplicate Content (SEO)

Two URLs for the same event:
- `/e/sunset-yoga-beach-party` (public)
- `/activities/123` (authenticated)

### Solution: Canonical Tags

```blade
{{-- resources/views/livewire/public-event-view.blade.php --}}
<head>
    <link rel="canonical" href="{{ route('events.public', $activity->slug) }}">
</head>

{{-- resources/views/livewire/activity-detail.blade.php --}}
<head>
    @if($activity->is_public)
        <link rel="canonical" href="{{ route('events.public', $activity->slug) }}">
    @else
        <link rel="canonical" href="{{ route('activities.show', $activity->id) }}">
    @endif
</head>
```

**SEO Strategy**:
- Public slug URL is canonical for public events
- Authenticated ID URL is canonical for private events
- Google consolidates ranking signals to canonical URL

---

## Redirects & Backwards Compatibility

### Old URL → New URL Redirects

```php
// routes/web.php

// Redirect old authenticated URLs to public URLs for public events
Route::get('/activities/{activity}', function (Activity $activity) {
    if (!Auth::check() && $activity->is_public) {
        return redirect()->route('events.public', $activity->slug, 301);
    }
    
    return app(ActivityDetail::class)($activity);
})->name('activities.show');
```

**Redirect Rules**:
1. Guest visits `/activities/123` for public event → 301 redirect to `/e/slug`
2. Authenticated user visits `/activities/123` → Show full authenticated view
3. Guest visits `/activities/123` for private event → 404 (policy blocks)

---

## Social Media Share URLs

### URL Structure for Sharing

```php
// app/Services/SocialShareService.php

public function generateShareUrl(Activity $activity, string $platform, ?User $user = null): string
{
    $baseUrl = route('events.public', $activity->slug);
    
    // Add referral code if user is sharing
    if ($user) {
        $referralCode = $this->generateReferralCode($activity, $user, $platform);
        $baseUrl .= '?ref=' . $referralCode;
    }
    
    // Add platform-specific UTM parameters
    $utmParams = [
        'utm_source' => $platform,
        'utm_medium' => 'social',
        'utm_campaign' => 'event_share',
    ];
    
    return $baseUrl . '&' . http_build_query($utmParams);
}
```

**Example URLs**:
```
Instagram: /e/sunset-yoga?ref=SHa7b3c9d2&utm_source=instagram&utm_medium=social&utm_campaign=event_share
Facebook:  /e/sunset-yoga?ref=SHa7b3c9d2&utm_source=facebook&utm_medium=social&utm_campaign=event_share
Twitter:   /e/sunset-yoga?ref=SHa7b3c9d2&utm_source=twitter&utm_medium=social&utm_campaign=event_share
```

---

## API Routes (Future)

### Mobile App Support

```php
// routes/api.php

Route::prefix('v1')->group(function () {
    // Public event API (no auth)
    Route::get('/events/{slug}', [Api\EventController::class, 'show']);
    Route::get('/events', [Api\EventController::class, 'index']);
    
    // Authenticated event API
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/events/{slug}/interested', [Api\EventController::class, 'interested']);
        Route::post('/events/{slug}/rsvp', [Api\EventController::class, 'rsvp']);
    });
});
```

**Design Decisions**:
- Same slug-based URLs for consistency
- Sanctum for API authentication
- JSON responses instead of Livewire components

---

## Security Considerations

### Rate Limiting

```php
// app/Providers/RouteServiceProvider.php

protected function configureRateLimiting()
{
    RateLimiter::for('guest-engagement', function (Request $request) {
        return Limit::perMinute(10)->by($request->ip());
    });
    
    RateLimiter::for('social-share', function (Request $request) {
        return Limit::perMinute(30)->by($request->ip());
    });
}
```

**Limits**:
- **Interested**: 10/min per IP (prevent spam)
- **Bookmark**: 20/min per IP (more lenient)
- **Share**: 30/min per IP (encourage sharing)

---

### CSRF Protection

```php
// Public routes still require CSRF for POST requests
Route::post('/e/{activity:slug}/interested', [GuestEngagementController::class, 'interested'])
    ->middleware('throttle:guest-engagement');
    // CSRF middleware applied by default
```

**Exception**: API routes use Sanctum tokens instead of CSRF

---

## Testing Routes

### Feature Tests

```php
// tests/Feature/GuestEventRoutesTest.php

test('guest can view public event', function () {
    $activity = Activity::factory()->public()->create();
    
    $response = $this->get(route('events.public', $activity->slug));
    
    $response->assertOk();
    $response->assertSee($activity->title);
});

test('guest redirected to login for private event', function () {
    $activity = Activity::factory()->private()->create();
    
    $response = $this->get(route('events.public', $activity->slug));
    
    $response->assertForbidden();
});

test('context preserved after authentication', function () {
    $activity = Activity::factory()->public()->create();
    
    // Visit public page
    $this->get(route('events.public', $activity->slug));
    
    // Click "Get Tickets" (sets session)
    Session::put('intended_action', [
        'type' => 'rsvp',
        'activity_id' => $activity->id,
    ]);
    
    // Login
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Should redirect to checkout
    $response = $this->post(route('login'), [/* credentials */]);
    $response->assertRedirect(route('activities.checkout', $activity->id));
});
```

