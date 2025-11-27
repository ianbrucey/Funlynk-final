<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Scout\Searchable;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Activity extends Model
{
    use HasFactory;
    use HasUuids;
    use HasSpatial;
    use Searchable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'location_coordinates' => \MatanYadaev\EloquentSpatial\Objects\Point::class,
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'conversion_date' => 'datetime',
            'is_public' => 'boolean',
            'requires_approval' => 'boolean',
            'is_paid' => 'boolean',
            'price_cents' => 'integer',
            'max_attendees' => 'integer',
            'current_attendees' => 'integer',
            'images' => 'array',
        ];
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

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'activity_tag');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
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
            'tags' => $this->tags?->pluck('name')->toArray() ?? [],
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
