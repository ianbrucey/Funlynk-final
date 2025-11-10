<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'conversion_date' => 'datetime',
            'is_public' => 'boolean',
            'requires_approval' => 'boolean',
            'is_paid' => 'boolean',
            'price_cents' => 'integer',
            'max_attendees' => 'integer',
            'current_attendees' => 'integer',
            'tags' => 'array',
            'images' => 'array',
        ];
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function postOrigin(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'originated_from_post_id');
    }
}
