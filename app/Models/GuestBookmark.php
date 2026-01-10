<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestBookmark extends Model
{
    protected $guarded = [];

    /**
     * Get the activity this bookmark is for
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the user (if converted from guest)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if bookmark has been migrated to user account
     */
    public function isMigrated(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Scope to get guest bookmarks (not migrated)
     */
    public function scopeGuest($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope to get migrated bookmarks
     */
    public function scopeMigrated($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope to get bookmarks for a specific guest token
     */
    public function scopeForGuestToken($query, string $token)
    {
        return $query->where('guest_token', $token);
    }
}
