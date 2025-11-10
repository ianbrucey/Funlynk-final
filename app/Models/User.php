<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\HasName as FilamentHasName;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentHasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;

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
            'password' => 'hashed',
            'interests' => 'array',
            'location_coordinates' => 'array',
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

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
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

    public function getFilamentName(): string
    {
        return $this->display_name ?: ($this->username ?: (string) $this->email);
    }
}
