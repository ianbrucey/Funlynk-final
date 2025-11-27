<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\HasName as FilamentHasName;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;

class User extends Authenticatable implements FilamentHasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'username',
        'display_name',
        'password',
        'email_verified_at',
        'onboarding_completed_at',
        'bio',
        'profile_image_url',
        'location_name',
        'location_coordinates',
        'interests',
        'is_host',
        'stripe_account_id',
        'stripe_onboarding_complete',
        'follower_count',
        'following_count',
        'activity_count',
        'is_verified',
        'is_active',
        'privacy_level',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'password' => 'hashed',
            'interests' => 'array',
            'location_coordinates' => \MatanYadaev\EloquentSpatial\Objects\Point::class,
            'is_host' => 'boolean',
            'stripe_onboarding_complete' => 'boolean',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'follower_count' => 'integer',
            'following_count' => 'integer',
            'activity_count' => 'integer',
        ];
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function activitiesHosted(): HasMany
    {
        return $this->hasMany(Activity::class, 'host_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function postReactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    public function conversations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['role', 'is_muted', 'last_read_at'])
            ->withTimestamps();
    }


    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function flares(): HasMany
    {
        return $this->hasMany(Flare::class);
    }

    public function reportsAuthored(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id');
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id');
    }

    public function followerEdges(): HasMany
    {
        return $this->hasMany(Follow::class, 'following_id');
    }


    public function followingEdges(): HasMany
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    public function stripeAccount(): HasOne
    {
        return $this->hasOne(StripeAccount::class);
    }

    public function getFilamentName(): string
    {
        return $this->display_name ?: ($this->username ?: (string) $this->email);
    }

    /**
     * Get the latitude from the location_coordinates Point
     */
    public function getLatitudeAttribute(): ?float
    {
        return $this->location_coordinates?->latitude;
    }

    /**
     * Get the longitude from the location_coordinates Point
     */
    public function getLongitudeAttribute(): ?float
    {
        return $this->location_coordinates?->longitude;
    }

    /**
     * Check if user has completed onboarding
     */
    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    /**
     * Mark onboarding as complete
     */
    public function markOnboardingComplete(): void
    {
        $this->onboarding_completed_at = now();
        $this->save();
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        $array = [
            'id' => $this->id,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'bio' => $this->bio,
            'interests' => $this->interests ?? [],
            'location_name' => $this->location_name,
            'follower_count' => $this->follower_count ?? 0,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->timestamp,
        ];

        // Add _geo field for Meilisearch native geo filtering
        if ($this->location_coordinates) {
            $array['_geo'] = [
                'lat' => $this->location_coordinates->latitude,
                'lng' => $this->location_coordinates->longitude,
            ];
        }

        return $array;
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'users_index';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->is_active && $this->onboarding_completed_at !== null;
    }
}
