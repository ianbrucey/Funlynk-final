<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * Video status constants
     */
    public const STATUS_PENDING_UPLOAD = 'pending_upload';
    public const STATUS_UPLOADING = 'uploading';
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DELETED = 'deleted';

    /**
     * Moderation status constants
     */
    public const MODERATION_PENDING = 'pending';
    public const MODERATION_APPROVED = 'approved';
    public const MODERATION_REJECTED = 'rejected';
    public const MODERATION_FLAGGED = 'flagged';

    /**
     * Visibility constants
     */
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_FOLLOWERS_ONLY = 'followers_only';

    /**
     * Max constraints
     */
    public const MAX_DURATION_SECONDS = 60;
    public const MAX_FILE_SIZE_BYTES = 104857600; // 100MB

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'file_size_bytes' => 'integer',
            'processing_attempts' => 'integer',
            'available_qualities' => 'array',
            'view_count' => 'integer',
            'like_count' => 'integer',
            'processing_started_at' => 'datetime',
            'processing_completed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the parent videoable model (Post, Activity, User, Group).
     */
    public function videoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the video.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * Get the upload tokens for this video.
     */
    public function uploadTokens(): HasMany
    {
        return $this->hasMany(VideoUploadToken::class);
    }

    /**
     * Get the processing jobs for this video.
     */
    public function processingJobs(): HasMany
    {
        return $this->hasMany(VideoProcessingJob::class);
    }

    /**
     * Get the views for this video.
     */
    public function views(): HasMany
    {
        return $this->hasMany(VideoView::class);
    }

    /**
     * Check if video is ready for streaming.
     */
    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    /**
     * Check if video is currently processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if video processing failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if video is visible to the public.
     */
    public function isPublic(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC
            && $this->moderation_status === self::MODERATION_APPROVED;
    }

    /**
     * Get the full S3 URL for the HLS manifest.
     */
    public function getHlsUrlAttribute(): ?string
    {
        if (!$this->hls_path) {
            return null;
        }

        return \Storage::disk($this->storage_provider)->url($this->hls_path);
    }

    /**
     * Get the full S3 URL for the thumbnail.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }

        return \Storage::disk($this->storage_provider)->url($this->thumbnail_path);
    }

    /**
     * Increment view count (use sparingly, prefer async job).
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Scope for ready videos only.
     */
    public function scopeReady($query)
    {
        return $query->where('status', self::STATUS_READY);
    }

    /**
     * Scope for public videos only.
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC)
            ->where('moderation_status', self::MODERATION_APPROVED);
    }

    /**
     * Scope for videos by type.
     */
    public function scopeForType($query, string $type)
    {
        $modelClass = match ($type) {
            'post' => Post::class,
            'activity' => Activity::class,
            'user' => User::class,
            'group' => Group::class,
            default => $type,
        };

        return $query->where('videoable_type', $modelClass);
    }
}
