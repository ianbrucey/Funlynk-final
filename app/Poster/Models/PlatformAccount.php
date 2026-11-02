<?php

namespace App\Poster\Models;

use App\Poster\Enums\Platform;
use Database\Factories\Poster\PlatformAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A connected (or to-be-connected) platform account.
 *
 * Token fields are encrypted at rest and nullable: in Phase 1 the table is
 * modeled but empty — no accounts or credentials exist yet. Tokens arrive
 * later via the secure handover flow (see README), never through git.
 *
 * @property int $id
 * @property Platform $platform
 * @property string|null $label
 * @property string|null $handle
 * @property string|null $external_id
 * @property array<string, mixed>|null $external_meta
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property \Illuminate\Support\Carbon|null $token_expires_at
 * @property list<string>|null $scopes
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PlatformAccount extends Model
{
    /** @use HasFactory<PlatformAccountFactory> */
    use HasFactory;

    protected $table = 'poster_platform_accounts';

    /**
     * Create a new factory instance for the model.
     *
     * @return PlatformAccountFactory
     */
    protected static function newFactory()
    {
        return PlatformAccountFactory::new();
    }

    public const STATUS_PENDING_SETUP = 'pending_setup';

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'platform',
        'label',
        'handle',
        'external_id',
        'external_meta',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'status',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'external_meta' => 'array',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'scopes' => 'array',
        ];
    }

    /**
     * @return HasMany<BundlePlatformPost, $this>
     */
    public function platformPosts(): HasMany
    {
        return $this->hasMany(BundlePlatformPost::class);
    }

    public function hasUsableToken(): bool
    {
        return $this->status === self::STATUS_CONNECTED && filled($this->access_token);
    }
}
