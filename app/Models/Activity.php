<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Activity extends Model
{
    use HasFactory;
    use HasSpatial;
    use HasUuids;
    use Searchable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($activity) {
            if (empty($activity->slug)) {
                $activity->slug = static::generateUniqueSlug($activity->title, $activity->start_time);
            }
        });

        static::updating(function ($activity) {
            // Regenerate slug if title changed and slug wasn't manually set
            if ($activity->isDirty('title') && ! $activity->isDirty('slug')) {
                $activity->slug = static::generateUniqueSlug($activity->title, $activity->start_time, $activity->id);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'location_coordinates' => \MatanYadaev\EloquentSpatial\Objects\Point::class,
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'conversion_date' => 'datetime',
            'recurrence_date' => 'date',
            'is_public' => 'boolean',
            'requires_approval' => 'boolean',
            'price_cents' => 'integer',
            'max_attendees' => 'integer',
            'current_attendees' => 'integer',
            'images' => 'array',
            'edit_locked_at' => 'datetime',
            'original_values' => 'array',
        ];
    }

    /**
     * Backward-compatible accessor for is_paid
     * Returns true if payment_type is 'online' or 'at_door'
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->payment_type !== 'free';
    }

    /**
     * Check if this activity requires online payment before RSVP
     */
    public function getRequiresOnlinePaymentAttribute(): bool
    {
        return $this->payment_type === 'online';
    }

    /**
     * Get the type identifier for timeline differentiation
     */
    public function getTypeAttribute(): string
    {
        return 'event';
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function conversation(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Conversation::class, 'conversationable');
    }

    public function postOrigin(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'originated_from_post_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function recurringSchedule(): BelongsTo
    {
        return $this->belongsTo(RecurringSchedule::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'activity_tag');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ActivityInvitation::class);
    }

    public function editLogs(): HasMany
    {
        return $this->hasMany(ActivityEditLog::class);
    }

    public function refundWindows(): HasMany
    {
        return $this->hasMany(ActivityRefundWindow::class);
    }

    public function activeRefundWindow()
    {
        return $this->hasOne(ActivityRefundWindow::class)
            ->where('status', ActivityRefundWindow::STATUS_ACTIVE)
            ->where('expires_at', '>', now());
    }

    // Guest Engagement Relationships
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

    // Edit Protection Helpers
    public function isEditLocked(): bool
    {
        return $this->edit_locked_at !== null;
    }

    public function hasPaidAttendees(): bool
    {
        return $this->rsvps()->where('is_paid', true)->exists();
    }

    public function getPaidAttendeeCount(): int
    {
        return $this->rsvps()->where('is_paid', true)->count();
    }

    public function lockEditing(): void
    {
        if ($this->isEditLocked()) {
            return;
        }

        $this->update([
            'edit_locked_at' => now(),
            'original_values' => [
                'title' => $this->title,
                'start_time' => $this->start_time?->toISOString(),
                'end_time' => $this->end_time?->toISOString(),
                'location_name' => $this->location_name,
                'location_address' => $this->location_address,
                'price_cents' => $this->price_cents,
            ],
        ]);
    }

    // Guest Engagement Helpers
    public function getInterestedCountAttribute(): int
    {
        return $this->interests()->whereNull('converted_to_rsvp_at')->count();
    }

    public function getShareCountAttribute(): int
    {
        return $this->socialShares()->count();
    }

    public function getBookmarkCountAttribute(): int
    {
        return $this->guestBookmarks()->count();
    }

    // Slug Generation
    public static function generateUniqueSlug(string $title, ?\Carbon\Carbon $startTime = null, ?string $excludeId = null): string
    {
        $baseSlug = Str::slug($title);

        // Add date suffix for better uniqueness and SEO (e.g., "yoga-class-2025-01-08")
        if ($startTime) {
            $baseSlug .= '-'.$startTime->format('Y-m-d');
        }

        $slug = $baseSlug;
        $counter = 1;

        $query = static::where('slug', $slug);

        // Exclude current activity when updating
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;

            $query = static::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    // Scopes
    public function scopeConvertedFromPost($query)
    {
        return $query->whereNotNull('originated_from_post_id');
    }

    // Accessor for latitude (for map view)
    public function getLatitudeAttribute(): ?float
    {
        return $this->location_coordinates?->latitude;
    }

    // Accessor for longitude (for map view)
    public function getLongitudeAttribute(): ?float
    {
        return $this->location_coordinates?->longitude;
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        $array = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'tags' => $this->tags->pluck('name')->toArray(),
            'location_name' => $this->location_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'start_time' => $this->start_time?->timestamp,
            'created_at' => $this->created_at->timestamp,
        ];

        // Add _geo field for Meilisearch native geo filtering
        if ($this->latitude && $this->longitude) {
            $array['_geo'] = [
                'lat' => $this->latitude,
                'lng' => $this->longitude,
            ];
        }

        return $array;
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'activities_index';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published' && $this->start_time > now();
    }
}
