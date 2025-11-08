<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
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
}
