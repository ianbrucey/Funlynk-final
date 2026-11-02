<?php

namespace App\Poster\Models;

use App\Poster\Enums\Platform;
use Database\Factories\Poster\PostingAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only log of every publish attempt against a platform post —
 * including refusals (e.g. NotConfiguredException) so the report Ian sees
 * reflects what actually happened, attempt by attempt.
 *
 * @property int $id
 * @property int $bundle_platform_post_id
 * @property Platform $platform
 * @property int $attempt_number
 * @property string $status
 * @property string|null $error
 * @property array<string, mixed>|null $request_meta
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PostingAttempt extends Model
{
    /** @use HasFactory<PostingAttemptFactory> */
    use HasFactory;

    protected $table = 'poster_posting_attempts';

    /**
     * Create a new factory instance for the model.
     *
     * @return PostingAttemptFactory
     */
    protected static function newFactory()
    {
        return PostingAttemptFactory::new();
    }

    public const STATUS_STARTED = 'started';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'bundle_platform_post_id',
        'platform',
        'attempt_number',
        'status',
        'error',
        'request_meta',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'request_meta' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BundlePlatformPost, $this>
     */
    public function platformPost(): BelongsTo
    {
        return $this->belongsTo(BundlePlatformPost::class, 'bundle_platform_post_id');
    }
}
