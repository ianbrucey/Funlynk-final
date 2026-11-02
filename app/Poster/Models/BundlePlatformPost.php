<?php

namespace App\Poster\Models;

use App\Poster\Enums\Platform;
use App\Poster\Enums\PlatformPostStatus;
use Database\Factories\Poster\BundlePlatformPostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One bundle's post to one platform: the adapted copy, the normalized asset
 * references, and the outcome (platform post id/url or error).
 *
 * @property int $id
 * @property int $bundle_id
 * @property int|null $platform_account_id
 * @property Platform $platform
 * @property string|null $adapted_caption
 * @property list<array<string, mixed>>|null $normalized_assets
 * @property PlatformPostStatus $status
 * @property string|null $platform_post_id
 * @property string|null $platform_post_url
 * @property string|null $error
 * @property \Illuminate\Support\Carbon|null $attempted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BundlePlatformPost extends Model
{
    /** @use HasFactory<BundlePlatformPostFactory> */
    use HasFactory;

    protected $table = 'poster_bundle_platform_posts';

    /**
     * Create a new factory instance for the model.
     *
     * @return BundlePlatformPostFactory
     */
    protected static function newFactory()
    {
        return BundlePlatformPostFactory::new();
    }

    protected $fillable = [
        'bundle_id',
        'platform_account_id',
        'platform',
        'adapted_caption',
        'normalized_assets',
        'status',
        'platform_post_id',
        'platform_post_url',
        'error',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'normalized_assets' => 'array',
            'status' => PlatformPostStatus::class,
            'attempted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PostBundle, $this>
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(PostBundle::class, 'bundle_id');
    }

    /**
     * @return BelongsTo<PlatformAccount, $this>
     */
    public function platformAccount(): BelongsTo
    {
        return $this->belongsTo(PlatformAccount::class);
    }

    /**
     * @return HasMany<PostingAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(PostingAttempt::class);
    }
}
