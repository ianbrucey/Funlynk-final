<?php

namespace App\Livewire\Profile;

use App\Livewire\Concerns\HandlesHostFollow;
use App\Models\User;
use App\Services\FollowService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Followers / following lists for a profile (spec 011).
 * Visibility follows the profile content rule; deactivated accounts are left out.
 */
class FollowList extends Component
{
    use HandlesHostFollow;

    private const PAGE_SIZE = 20;

    public User $profile;

    public string $tab = 'followers';

    public string $search = '';

    public int $limit = self::PAGE_SIZE;

    public function mount(string $username, string $tab = 'followers'): void
    {
        $this->profile = User::where('username', $username)->firstOrFail();
        $this->tab = $tab === 'following' ? 'following' : 'followers';
    }

    public function updatedSearch(): void
    {
        $this->limit = self::PAGE_SIZE;
    }

    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    protected function afterHostFollowChange(string $hostId, bool $following): void
    {
        // Rows are rebuilt from the database on every render.
    }

    /**
     * Active users on one side of the profile's follows, newest follow first.
     *
     * @return Builder<User>
     */
    protected function listQuery(string $tab): Builder
    {
        $profileColumn = $tab === 'followers' ? 'following_id' : 'follower_id';
        $otherColumn = $tab === 'followers' ? 'follower_id' : 'following_id';

        return User::query()
            ->join('follows', 'follows.'.$otherColumn, '=', 'users.id')
            ->where('follows.'.$profileColumn, $this->profile->id)
            ->where('users.is_active', true)
            ->select('users.id', 'users.username', 'users.display_name', 'users.profile_image_url', 'users.location_name');
    }

    public function render(): View
    {
        $viewer = Auth::user();
        $canView = $this->profile->allowsProfileContentFor($viewer);

        $people = collect();
        $hasMore = false;
        $counts = ['followers' => null, 'following' => null];
        $followedIds = [];

        if ($canView) {
            $counts = [
                'followers' => $this->listQuery('followers')->count(),
                'following' => $this->listQuery('following')->count(),
            ];

            $query = $this->listQuery($this->tab);
            $term = trim($this->search);

            if ($term !== '') {
                $like = '%'.addcslashes($term, '\\%_').'%';
                $query->where(function (Builder $q) use ($like) {
                    $q->whereRaw('users.display_name ILIKE ?', [$like])
                        ->orWhereRaw('users.username ILIKE ?', [$like]);
                });
            }

            $rows = $query->orderByDesc('follows.created_at')->limit($this->limit + 1)->get();
            $hasMore = $rows->count() > $this->limit;
            $people = $rows->take($this->limit);

            if ($viewer) {
                $followedIds = app(FollowService::class)->followedIds($viewer, $people->pluck('id')->map(fn ($id) => (string) $id)->all());
            }
        }

        return view('livewire.profile.follow-list', [
            'canView' => $canView,
            'people' => $people,
            'hasMore' => $hasMore,
            'counts' => $counts,
            'followedIds' => $followedIds,
            'isOwner' => $viewer !== null && $viewer->id === $this->profile->id,
        ])->layout('layouts.app', ['title' => '@'.$this->profile->username]);
    }
}
