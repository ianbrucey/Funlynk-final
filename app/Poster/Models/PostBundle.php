<?php

namespace App\Poster\Models;

use App\Poster\Enums\PlatformPostStatus;
use App\Poster\Enums\PostBundleState;
use App\Poster\Exceptions\InvalidStateTransitionException;
use Database\Factories\Poster\PostBundleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One unit of content: a caption plus its source assets, fanned out to one
 * BundlePlatformPost per target platform.
 *
 * State machine (see PostBundleState): draft -> pending_approval -> approved
 * -> posting -> posted | partially_failed | failed. The approval transition
 * records who approved and when; the posting pipeline refuses any bundle
 * that is not in `approved`.
 *
 * @property int $id
 * @property string|null $title
 * @property string $caption
 * @property string $source
 * @property string|null $drive_folder_id
 * @property list<string>|null $drive_file_ids
 * @property list<array<string, mixed>>|null $assets
 * @property PostBundleState $state
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property string|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $posting_started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $last_error
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PostBundle extends Model
{
    /** @use HasFactory<PostBundleFactory> */
    use HasFactory;

    protected $table = 'poster_post_bundles';

    /**
     * Create a new factory instance for the model.
     *
     * @return PostBundleFactory
     */
    protected static function newFactory()
    {
        return PostBundleFactory::new();
    }

    protected $fillable = [
        'title',
        'caption',
        'source',
        'drive_folder_id',
        'drive_file_ids',
        'assets',
        'state',
        'submitted_at',
        'approved_by',
        'approved_at',
        'posting_started_at',
        'completed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'drive_file_ids' => 'array',
            'assets' => 'array',
            'state' => PostBundleState::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'posting_started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<BundlePlatformPost, $this>
     */
    public function platformPosts(): HasMany
    {
        return $this->hasMany(BundlePlatformPost::class, 'bundle_id');
    }

    // --- Asset shape helpers (assets: [{kind: image|video, name, ...}]) ---

    public function hasMedia(): bool
    {
        return $this->hasImage() || $this->hasVideo();
    }

    public function hasImage(): bool
    {
        return $this->imageCount() > 0;
    }

    public function hasVideo(): bool
    {
        return collect($this->assets ?? [])->contains('kind', 'video');
    }

    public function imageCount(): int
    {
        return collect($this->assets ?? [])->where('kind', 'image')->count();
    }

    // --- State machine ---

    public function submitForApproval(): void
    {
        $this->transitionTo(PostBundleState::PendingApproval);
        $this->update(['submitted_at' => now()]);
    }

    /**
     * Record a human approval. $approvedBy is a label for the approver
     * (e.g. "ian") — approval is an explicit, attributable act.
     */
    public function approve(string $approvedBy): void
    {
        $this->transitionTo(PostBundleState::Approved);
        $this->update([
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function sendBackToDraft(): void
    {
        $this->transitionTo(PostBundleState::Draft);
        $this->update([
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function beginPosting(): void
    {
        $this->transitionTo(PostBundleState::Posting);
        $this->update(['posting_started_at' => now()]);
    }

    public function completePosting(): void
    {
        $posts = $this->platformPosts()->get();
        $posted = $posts->where('status', PlatformPostStatus::Posted)->count();
        $failed = $posts->where('status', PlatformPostStatus::Failed)->count();

        $final = match (true) {
            $posts->isNotEmpty() && $posted === $posts->count() => PostBundleState::Posted,
            $posted > 0 && $failed > 0 => PostBundleState::PartiallyFailed,
            default => PostBundleState::Failed,
        };

        $this->transitionTo($final);
        $this->update(['completed_at' => now()]);
    }

    /**
     * @throws InvalidStateTransitionException
     */
    public function transitionTo(PostBundleState $target): void
    {
        if (! $this->state->canTransitionTo($target)) {
            throw new InvalidStateTransitionException(
                "Bundle #{$this->id} cannot move from {$this->state->value} to {$target->value}."
            );
        }

        $this->update(['state' => $target]);
    }
}
