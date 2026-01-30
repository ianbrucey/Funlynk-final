<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Group extends Model
{
    use HasFactory, HasSpatial, HasUuids, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            if (empty($group->slug)) {
                $group->slug = static::generateUniqueSlug($group->name);
            }
        });

        static::updating(function ($group) {
            // Regenerate slug if name changed and slug wasn't manually set
            if ($group->isDirty('name') && ! $group->isDirty('slug')) {
                $group->slug = static::generateUniqueSlug($group->name, $group->id);
            }
        });
    }

    public static function generateUniqueSlug(string $name, ?string $excludeId = null): string
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $originalSlug = $slug;
        $count = 2;

        while (static::where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'avatar_url',
        'cover_image_url',
        'privacy',
        'auto_approve_members',
        'post_permission',
        'event_permission',
        'created_by',
        'location_name',
        'location_coordinates',
        'emoji',
        'schedule_text',
        'meetup_label',
    ];

    protected function casts(): array
    {
        return [
            'auto_approve_members' => 'boolean',
            'location_coordinates' => Point::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->using(GroupMember::class)
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(GroupJoinRequest::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function recurringSchedules(): HasMany
    {
        return $this->hasMany(RecurringSchedule::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'group_tag')->withTimestamps();
    }

    public function admins(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'admin');
    }

    /**
     * Check if a user can create posts in this group
     */
    public function canCreatePost(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $membership = $this->memberships()->where('user_id', $user->id)->first();
        if (!$membership) {
            return false;
        }

        if ($this->post_permission === 'everyone') {
            return true;
        }

        return $membership->role === 'admin';
    }

    /**
     * Check if a user can create events in this group
     */
    public function canCreateEvent(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $membership = $this->memberships()->where('user_id', $user->id)->first();
        if (!$membership) {
            return false;
        }

        if ($this->event_permission === 'everyone') {
            return true;
        }

        return $membership->role === 'admin';
    }

    /**
     * Check if a user is an admin of this group
     */
    public function isAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->memberships()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();
    }
}
