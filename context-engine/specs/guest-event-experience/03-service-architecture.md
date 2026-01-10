# Service Architecture - Guest Event Experience

## Overview
Three core services handle guest engagement, context preservation, and social sharing. Designed for testability, reusability, and clear separation of concerns.

## Service Layer Design

### 1. GuestEngagementService

Handles guest interactions before account creation: interests, bookmarks, and conversion tracking.

```php
// app/Services/GuestEngagementService.php

namespace App\Services;

use App\Models\Activity;
use App\Models\EventInterest;
use App\Models\GuestBookmark;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class GuestEngagementService
{
    /**
     * Record guest interest in an event
     * 
     * @param Activity $activity
     * @param string $email
     * @param array $metadata ['source', 'utm_campaign', 'utm_source', 'utm_medium']
     * @return EventInterest
     */
    public function recordInterest(Activity $activity, string $email, array $metadata = []): EventInterest
    {
        return EventInterest::updateOrCreate(
            [
                'activity_id' => $activity->id,
                'email' => $email,
            ],
            [
                'source' => $metadata['source'] ?? null,
                'utm_campaign' => $metadata['utm_campaign'] ?? null,
                'utm_source' => $metadata['utm_source'] ?? null,
                'utm_medium' => $metadata['utm_medium'] ?? null,
            ]
        );
    }
    
    /**
     * Create or retrieve guest bookmark
     * 
     * @param Activity $activity
     * @param string|null $guestToken
     * @param string|null $source
     * @return array ['bookmark' => GuestBookmark, 'token' => string]
     */
    public function createBookmark(Activity $activity, ?string $guestToken = null, ?string $source = null): array
    {
        if (!$guestToken) {
            $guestToken = $this->generateGuestToken();
        }
        
        $bookmark = GuestBookmark::firstOrCreate(
            [
                'activity_id' => $activity->id,
                'guest_token' => $guestToken,
            ],
            [
                'source' => $source,
            ]
        );
        
        return [
            'bookmark' => $bookmark,
            'token' => $guestToken,
        ];
    }
    
    /**
     * Get all bookmarks for a guest token
     * 
     * @param string $guestToken
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getGuestBookmarks(string $guestToken)
    {
        return GuestBookmark::with('activity')
            ->where('guest_token', $guestToken)
            ->whereHas('activity', function ($query) {
                $query->where('start_time', '>', now());
            })
            ->latest()
            ->get();
    }
    
    /**
     * Migrate guest data to user account after signup
     * 
     * @param User $user
     * @param string $email
     * @param string|null $guestToken
     * @return array ['interests' => int, 'bookmarks' => int]
     */
    public function migrateGuestData(User $user, string $email, ?string $guestToken = null): array
    {
        $interestsUpdated = EventInterest::where('email', $email)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);
        
        $bookmarksUpdated = 0;
        if ($guestToken) {
            $bookmarksUpdated = GuestBookmark::where('guest_token', $guestToken)
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);
        }
        
        return [
            'interests' => $interestsUpdated,
            'bookmarks' => $bookmarksUpdated,
        ];
    }
    
    /**
     * Mark interest as converted to RSVP
     * 
     * @param EventInterest $interest
     * @param int $rsvpId
     * @return EventInterest
     */
    public function convertInterestToRsvp(EventInterest $interest, int $rsvpId): EventInterest
    {
        $interest->update([
            'converted_to_rsvp_at' => now(),
            'rsvp_id' => $rsvpId,
        ]);
        
        return $interest;
    }
    
    /**
     * Generate unique guest token for cookie
     * 
     * @return string
     */
    protected function generateGuestToken(): string
    {
        return Str::random(64);
    }
    
    /**
     * Check if email has already expressed interest
     * 
     * @param Activity $activity
     * @param string $email
     * @return bool
     */
    public function hasExpressedInterest(Activity $activity, string $email): bool
    {
        return EventInterest::where('activity_id', $activity->id)
            ->where('email', $email)
            ->exists();
    }
}
```

---

### 2. ContextPreservationService

Manages session-based intent tracking and post-authentication redirects.

```php
// app/Services/ContextPreservationService.php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Facades\Session;

class ContextPreservationService
{
    /**
     * Capture user intent to interact with an activity
     * 
     * @param Activity $activity
     * @param string $intentType 'rsvp', 'view', 'interested'
     * @param array $metadata ['source', 'referral_code', 'utm_params']
     * @return void
     */
    public function captureIntent(Activity $activity, string $intentType, array $metadata = []): void
    {
        Session::put('intended_action', [
            'type' => $intentType,
            'activity_id' => $activity->id,
            'activity_slug' => $activity->slug,
            'source' => $metadata['source'] ?? null,
            'referral_code' => $metadata['referral_code'] ?? null,
            'utm_params' => $metadata['utm_params'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Get intended action from session
     * 
     * @return array|null
     */
    public function getIntendedAction(): ?array
    {
        return Session::get('intended_action');
    }
    
    /**
     * Clear intended action from session
     * 
     * @return void
     */
    public function clearIntendedAction(): void
    {
        Session::forget('intended_action');
    }
    
    /**
     * Get redirect URL based on intended action
     * 
     * @param array $action
     * @return string
     */
    public function getRedirectUrl(array $action): string
    {
        return match($action['type']) {
            'rsvp' => route('activities.checkout', $action['activity_id']),
            'view' => route('activities.show', $action['activity_id']),
            'interested' => route('activities.show', $action['activity_id']),
            default => route('dashboard')
        };
    }
    
    /**
     * Capture referral code from URL
     * 
     * @param string $referralCode
     * @return void
     */
    public function captureReferralCode(string $referralCode): void
    {
        Session::put('referral_code', $referralCode);
    }
    
    /**
     * Capture UTM parameters from URL
     * 
     * @param array $utmParams ['source', 'medium', 'campaign']
     * @return void
     */
    public function captureUtmParams(array $utmParams): void
    {
        Session::put('utm_params', $utmParams);
    }
    
    /**
     * Get all tracking metadata from session
     * 
     * @return array
     */
    public function getTrackingMetadata(): array
    {
        return [
            'referral_code' => Session::get('referral_code'),
            'utm_params' => Session::get('utm_params'),
            'intended_action' => Session::get('intended_action'),
        ];
    }
}
```

---

### 3. SocialShareService

Handles social media sharing, referral tracking, and share analytics.

```php
// app/Services/SocialShareService.php

namespace App\Services;

use App\Models\Activity;
use App\Models\SocialShare;
use App\Models\User;
use Illuminate\Support\Str;

class SocialShareService
{
    /**
     * Record a social share
     * 
     * @param Activity $activity
     * @param string $platform 'instagram', 'facebook', 'twitter', 'whatsapp', 'copy_link'
     * @param User|null $user
     * @return SocialShare
     */
    public function recordShare(Activity $activity, string $platform, ?User $user = null): SocialShare
    {
        return SocialShare::create([
            'activity_id' => $activity->id,
            'user_id' => $user?->id,
            'platform' => $platform,
            'referral_code' => $this->generateReferralCode(),
        ]);
    }
    
    /**
     * Generate shareable URL with referral tracking
     * 
     * @param Activity $activity
     * @param string $platform
     * @param User|null $user
     * @return string
     */
    public function generateShareUrl(Activity $activity, string $platform, ?User $user = null): string
    {
        $share = $this->recordShare($activity, $platform, $user);
        
        $baseUrl = route('events.public', $activity->slug);
        
        $params = [
            'ref' => $share->referral_code,
            'utm_source' => $platform,
            'utm_medium' => 'social',
            'utm_campaign' => 'event_share',
        ];
        
        return $baseUrl . '?' . http_build_query($params);
    }
    
    /**
     * Track click on shared link
     * 
     * @param string $referralCode
     * @return void
     */
    public function trackClick(string $referralCode): void
    {
        SocialShare::where('referral_code', $referralCode)
            ->increment('clicks');
    }
    
    /**
     * Track conversion from shared link
     * 
     * @param string $referralCode
     * @return void
     */
    public function trackConversion(string $referralCode): void
    {
        SocialShare::where('referral_code', $referralCode)
            ->increment('conversions');
    }
    
    /**
     * Get share analytics for an activity
     * 
     * @param Activity $activity
     * @return array
     */
    public function getShareAnalytics(Activity $activity): array
    {
        $shares = SocialShare::where('activity_id', $activity->id)->get();
        
        return [
            'total_shares' => $shares->count(),
            'total_clicks' => $shares->sum('clicks'),
            'total_conversions' => $shares->sum('conversions'),
            'by_platform' => $shares->groupBy('platform')->map(function ($platformShares) {
                return [
                    'shares' => $platformShares->count(),
                    'clicks' => $platformShares->sum('clicks'),
                    'conversions' => $platformShares->sum('conversions'),
                ];
            }),
        ];
    }
    
    /**
     * Generate unique referral code
     * 
     * @return string
     */
    protected function generateReferralCode(): string
    {
        do {
            $code = 'SH' . Str::random(8);
        } while (SocialShare::where('referral_code', $code)->exists());
        
        return $code;
    }
}
```

---

## Service Integration Examples

### Example 1: Guest Clicks "Interested"

```php
// app/Http/Livewire/InterestedButton.php

public function expressInterest()
{
    $this->validate(['email' => 'required|email']);
    
    // Record interest
    $interest = app(GuestEngagementService::class)->recordInterest(
        $this->activity,
        $this->email,
        [
            'source' => Session::get('utm_params.source'),
            'utm_campaign' => Session::get('utm_params.campaign'),
        ]
    );
    
    // Send confirmation email
    app(NotificationService::class)->sendInterestConfirmation($interest);
    
    $this->emit('interestRecorded');
}
```

### Example 2: User Signs Up After Viewing Event

```php
// app/Http/Controllers/Auth/RegisteredUserController.php

public function store(Request $request)
{
    // Create user
    $user = User::create([...]);
    
    // Migrate guest data
    $guestToken = Cookie::get('funlynk_guest_token');
    $migrated = app(GuestEngagementService::class)->migrateGuestData(
        $user,
        $request->email,
        $guestToken
    );
    
    // Get intended action
    $contextService = app(ContextPreservationService::class);
    $intendedAction = $contextService->getIntendedAction();
    
    // Redirect to intended destination
    if ($intendedAction) {
        $redirectUrl = $contextService->getRedirectUrl($intendedAction);
        $contextService->clearIntendedAction();
        return redirect($redirectUrl);
    }
    
    return redirect()->route('dashboard');
}
```

### Example 3: User Shares Event

```php
// app/Http/Livewire/ShareButton.php

public function share(string $platform)
{
    $shareUrl = app(SocialShareService::class)->generateShareUrl(
        $this->activity,
        $platform,
        Auth::user()
    );
    
    // Return URL for frontend to open share dialog
    $this->emit('shareUrlGenerated', [
        'platform' => $platform,
        'url' => $shareUrl,
    ]);
}
```

---

## Testing Services

```php
// tests/Unit/GuestEngagementServiceTest.php

test('records guest interest', function () {
    $activity = Activity::factory()->create();
    $service = app(GuestEngagementService::class);
    
    $interest = $service->recordInterest($activity, 'test@example.com', [
        'source' => 'instagram',
    ]);
    
    expect($interest)->toBeInstanceOf(EventInterest::class);
    expect($interest->email)->toBe('test@example.com');
    expect($interest->source)->toBe('instagram');
});

test('migrates guest data after signup', function () {
    $activity = Activity::factory()->create();
    $service = app(GuestEngagementService::class);
    
    // Create guest interest
    $service->recordInterest($activity, 'test@example.com');
    
    // User signs up
    $user = User::factory()->create(['email' => 'test@example.com']);
    
    // Migrate data
    $result = $service->migrateGuestData($user, 'test@example.com');
    
    expect($result['interests'])->toBe(1);
    expect(EventInterest::where('user_id', $user->id)->count())->toBe(1);
});
```

