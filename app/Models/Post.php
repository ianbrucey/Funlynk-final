<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Searchable;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Post extends Model
{
    use HasFactory, HasSpatial, HasUuids, Searchable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'location_coordinates' => Point::class,
            'tags' => 'array',
            'approximate_time' => 'datetime',
            'expires_at' => 'datetime',
            'conversion_suggested_at' => 'datetime',
            'view_count' => 'integer',
            'reaction_count' => 'integer',
        ];
    }

    /**
     * Scope for active (non-expired, non-converted) posts
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired posts
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now())
            ->orWhere('status', 'expired');
    }

    /**
     * Scope for posts near a user location
     */
    public function scopeNearUser(Builder $query, float $lat, float $lng, int $radiusMeters = 10000): Builder
    {
        $point = new Point($lat, $lng, 4326);
        return $query->whereDistance('location_coordinates', $point, '<=', $radiusMeters);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    public function conversion(): HasOne
    {
        return $this->hasOne(PostConversion::class, 'post_id');
    }

    public function convertedActivity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'converted_to_activity_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Helper Methods
    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === 'expired';
    }

    public function canConvert(): bool
    {
        return $this->reaction_count >= 5 && $this->status === 'active';
    }

    public function shouldAutoConvert(): bool
    {
        return $this->reaction_count >= 10 && $this->status === 'active';
    }

    public function timeUntilExpiration(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans();
    }

    public function imDownCount(): int
    {
        return $this->reactions()->where('reaction_type', 'im_down')->count();
    }

    public function joinMeCount(): int
    {
        return $this->reactions()->where('reaction_type', 'join_me')->count();
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
            'tags' => $this->tags,
            'location_name' => $this->location_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->timestamp,
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
        return 'posts_index';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'active' && $this->expires_at > now();
    }
}
