<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Post extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'approximate_time' => 'datetime',
            'expires_at' => 'datetime',
            'conversion_triggered_at' => 'datetime',
            'view_count' => 'integer',
            'reaction_count' => 'integer',
        ];
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

    public function evolvedActivity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'evolved_to_event_id');
    }
}
