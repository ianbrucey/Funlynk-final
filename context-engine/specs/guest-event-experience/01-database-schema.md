# Database Schema - Guest Event Experience

## Overview
Three new tables to track guest engagement before account creation: interests, bookmarks, and social shares. Designed for eventual migration to user accounts after signup.

## New Tables

### 1. `event_interests`
Tracks when guests express interest in an event via email capture.

```sql
CREATE TABLE event_interests (
    id BIGSERIAL PRIMARY KEY,
    activity_id BIGINT NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    email VARCHAR(255) NOT NULL,
    source VARCHAR(50) NULL,  -- 'instagram', 'facebook', 'twitter', 'direct'
    utm_campaign VARCHAR(100) NULL,
    utm_source VARCHAR(100) NULL,
    utm_medium VARCHAR(100) NULL,
    converted_to_rsvp_at TIMESTAMP NULL,
    rsvp_id BIGINT NULL REFERENCES rsvps(id) ON DELETE SET NULL,
    reminded_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    INDEX idx_activity_id (activity_id),
    INDEX idx_email (email),
    INDEX idx_user_id (user_id),
    INDEX idx_converted (converted_to_rsvp_at),
    INDEX idx_source (source),
    UNIQUE KEY unique_activity_email (activity_id, email)
);
```

**Purpose**: 
- Capture guest interest before account creation
- Enable email reminders and nurture sequences
- Track conversion from interest → RSVP
- Attribute traffic sources for analytics

**Lifecycle**:
1. Guest clicks "Interested" → Record created with email only
2. Guest creates account → `user_id` populated
3. Guest purchases ticket → `converted_to_rsvp_at` and `rsvp_id` populated
4. Cleanup job deletes unconverted records >90 days old

---

### 2. `guest_bookmarks`
Cookie-based bookmarks for guests who want to save events without providing email.

```sql
CREATE TABLE guest_bookmarks (
    id BIGSERIAL PRIMARY KEY,
    activity_id BIGINT NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE CASCADE,
    guest_token VARCHAR(64) NOT NULL,  -- Cookie-based identifier
    source VARCHAR(50) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    INDEX idx_activity_id (activity_id),
    INDEX idx_guest_token (guest_token),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    UNIQUE KEY unique_activity_guest (activity_id, guest_token)
);
```

**Purpose**:
- Allow guests to save events without email commitment
- Lower friction than email capture
- Migrate bookmarks to user account after signup

**Lifecycle**:
1. Guest clicks "Save" → Record created with `guest_token` from cookie
2. Guest creates account → `user_id` populated, `guest_token` retained for migration
3. Cleanup job deletes records >30 days old without `user_id`

**Cookie Structure**:
```php
// Set on first visit
Cookie::make('funlynk_guest_token', Str::random(64), 43200); // 30 days
```

---

### 3. `social_shares`
Tracks when events are shared to social media platforms.

```sql
CREATE TABLE social_shares (
    id BIGSERIAL PRIMARY KEY,
    activity_id BIGINT NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    platform VARCHAR(50) NOT NULL,  -- 'instagram', 'facebook', 'twitter', 'whatsapp', 'copy_link'
    referral_code VARCHAR(20) NULL,  -- For tracking conversions from this share
    clicks INT NOT NULL DEFAULT 0,
    conversions INT NOT NULL DEFAULT 0,  -- RSVPs attributed to this share
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    INDEX idx_activity_id (activity_id),
    INDEX idx_user_id (user_id),
    INDEX idx_platform (platform),
    INDEX idx_referral_code (referral_code),
    INDEX idx_created_at (created_at)
);
```

**Purpose**:
- Track viral growth and sharing behavior
- Attribute conversions to specific shares
- Enable future referral incentive programs
- Measure platform effectiveness (Instagram vs. Facebook)

**Referral Code Structure**:
```php
// Generated on share
$referralCode = 'SH' . Str::random(8); // e.g., "SHa7b3c9d2"

// Appended to shared URL
route('events.public', ['activity' => $activity->slug, 'ref' => $referralCode])
```

---

## Modified Tables

### `activities` (Existing)
Add `slug` column for SEO-friendly public URLs.

```sql
ALTER TABLE activities 
ADD COLUMN slug VARCHAR(255) NULL UNIQUE,
ADD INDEX idx_slug (slug);
```

**Migration Strategy**:
```php
// Generate slugs for existing activities
Activity::whereNull('slug')->each(function ($activity) {
    $activity->slug = Str::slug($activity->title) . '-' . Str::random(6);
    $activity->save();
});

// Make slug NOT NULL after backfill
Schema::table('activities', function (Blueprint $table) {
    $table->string('slug')->nullable(false)->change();
});
```

---

## Relationships

### Activity Model
```php
class Activity extends Model
{
    // Existing relationships...
    
    // New relationships
    public function interests(): HasMany
    {
        return $this->hasMany(EventInterest::class);
    }
    
    public function guestBookmarks(): HasMany
    {
        return $this->hasMany(GuestBookmark::class);
    }
    
    public function socialShares(): HasMany
    {
        return $this->hasMany(SocialShare::class);
    }
    
    // Computed properties
    public function getInterestedCountAttribute(): int
    {
        return $this->interests()->whereNull('converted_to_rsvp_at')->count();
    }
    
    public function getShareCountAttribute(): int
    {
        return $this->socialShares()->count();
    }
}
```

### User Model
```php
class User extends Model
{
    // Existing relationships...
    
    // New relationships
    public function eventInterests(): HasMany
    {
        return $this->hasMany(EventInterest::class);
    }
    
    public function guestBookmarks(): HasMany
    {
        return $this->hasMany(GuestBookmark::class);
    }
    
    public function socialShares(): HasMany
    {
        return $this->hasMany(SocialShare::class);
    }
}
```

---

## Data Flow Examples

### Example 1: Guest Interest → Signup → RSVP

```sql
-- Step 1: Guest clicks "Interested" (no account)
INSERT INTO event_interests (activity_id, email, source, utm_campaign)
VALUES (123, 'jane@example.com', 'instagram', 'sunset_yoga_dec');

-- Step 2: Guest creates account
UPDATE event_interests 
SET user_id = 456 
WHERE email = 'jane@example.com' AND user_id IS NULL;

-- Step 3: Guest purchases ticket
UPDATE event_interests 
SET converted_to_rsvp_at = NOW(), rsvp_id = 789
WHERE activity_id = 123 AND user_id = 456;
```

### Example 2: Guest Bookmark → Signup → Migration

```sql
-- Step 1: Guest bookmarks event (cookie-based)
INSERT INTO guest_bookmarks (activity_id, guest_token, source)
VALUES (123, 'abc123def456...', 'instagram');

-- Step 2: Guest creates account
UPDATE guest_bookmarks 
SET user_id = 456 
WHERE guest_token = 'abc123def456...' AND user_id IS NULL;

-- Step 3: User can now see bookmarks in their account
SELECT a.* FROM activities a
JOIN guest_bookmarks gb ON a.id = gb.activity_id
WHERE gb.user_id = 456;
```

### Example 3: Social Share → Referral Tracking

```sql
-- Step 1: User shares event to Instagram
INSERT INTO social_shares (activity_id, user_id, platform, referral_code)
VALUES (123, 456, 'instagram', 'SHa7b3c9d2');

-- Step 2: Friend clicks shared link with ?ref=SHa7b3c9d2
-- (Tracked in session/cookie)

-- Step 3: Friend creates RSVP
UPDATE social_shares 
SET clicks = clicks + 1, conversions = conversions + 1
WHERE referral_code = 'SHa7b3c9d2';
```

---

## Indexes & Performance

### Query Patterns

**Most Common Queries**:
1. Get all interests for an activity: `WHERE activity_id = ?`
2. Check if email already interested: `WHERE activity_id = ? AND email = ?`
3. Get user's bookmarks: `WHERE user_id = ?`
4. Track referral conversions: `WHERE referral_code = ?`
5. Cleanup old records: `WHERE created_at < ? AND user_id IS NULL`

**Index Strategy**:
- Primary lookups: `activity_id`, `email`, `user_id`, `referral_code`
- Unique constraints: `(activity_id, email)`, `(activity_id, guest_token)`
- Cleanup queries: `created_at`, `converted_to_rsvp_at`

---

## Data Retention & Cleanup

### Scheduled Job: `CleanupGuestDataJob`

```php
// Run daily at 2 AM
Schedule::job(new CleanupGuestDataJob)->dailyAt('02:00');
```

**Cleanup Rules**:
1. **Event Interests**: Delete unconverted records >90 days old
2. **Guest Bookmarks**: Delete records >30 days old without `user_id`
3. **Social Shares**: Keep indefinitely (analytics value)

```sql
-- Cleanup unconverted interests
DELETE FROM event_interests 
WHERE converted_to_rsvp_at IS NULL 
  AND user_id IS NULL 
  AND created_at < NOW() - INTERVAL '90 days';

-- Cleanup orphaned bookmarks
DELETE FROM guest_bookmarks 
WHERE user_id IS NULL 
  AND created_at < NOW() - INTERVAL '30 days';
```

---

## Privacy & GDPR Compliance

### Data Subject Rights

**Right to Access**: Users can view their interests/bookmarks via account dashboard

**Right to Deletion**: 
- Guests can request deletion via email (manual process)
- Users can delete via account settings
- Cascade deletes when user account deleted

**Data Minimization**:
- Only collect email (no names, phones, addresses)
- Guest tokens are random, not personally identifiable
- UTM parameters for analytics only

### Cookie Consent

```blade
{{-- Cookie banner for guest_token --}}
<x-cookie-consent>
    We use cookies to save your bookmarked events. 
    <a href="/privacy">Learn more</a>
</x-cookie-consent>
```

