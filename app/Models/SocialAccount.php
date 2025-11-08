<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    /** @use HasFactory<\Database\Factories\SocialAccountFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_email',
        'name',
        'nickname',
        'avatar_url',
        'token',
        'refresh_token',
        'token_expires_at',
        'meta',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
