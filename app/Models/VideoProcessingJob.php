<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProcessingJob extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * Job type constants
     */
    public const TYPE_ANALYZE = 'analyze';
    public const TYPE_THUMBNAIL = 'thumbnail';
    public const TYPE_TRANSCODE = 'transcode';

    /**
     * Status constants
     */
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRYING = 'retrying';

    protected function casts(): array
    {
        return [
            'progress_percent' => 'integer',
            'attempt_number' => 'integer',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the video this job is for.
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Check if job is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if job failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark job as started.
     */
    public function markAsStarted(string $workerId = null): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
            'worker_id' => $workerId,
        ]);
    }

    /**
     * Mark job as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress_percent' => 100,
        ]);
    }

    /**
     * Mark job as failed.
     */
    public function markAsFailed(string $errorMessage, string $errorCode = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
        ]);
    }

    /**
     * Update progress.
     */
    public function updateProgress(int $percent): void
    {
        $this->update(['progress_percent' => min(100, max(0, $percent))]);
    }

    /**
     * Scope for pending jobs.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_QUEUED);
    }

    /**
     * Scope for active jobs (processing or retrying).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PROCESSING, self::STATUS_RETRYING]);
    }
}
