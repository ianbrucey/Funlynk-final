<?php

namespace App\Livewire\Events;

use App\Models\Activity;
use App\Models\EventNeed;
use App\Models\EventNeedClaim;
use App\Models\Rsvp;
use App\Models\User;
use App\Services\EventNeedService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * "What we need" card on the event page (spec 014).
 */
class EventNeeds extends Component
{
    public Activity $activity;

    public function mount(Activity $activity): void
    {
        Gate::authorize('view', $activity);

        $this->activity = $activity;
    }

    public function claim(string $needId, int $quantity = 1): void
    {
        $user = $this->actor();
        $need = $this->need($needId);
        $this->authorizeFor('claimNeeds');

        $this->attempt(fn () => app(EventNeedService::class)->claim($need, $user, $quantity), 'You are bringing '.$need->name);
    }

    public function release(string $needId): void
    {
        $claim = $this->myClaim($needId);
        $name = $claim->need->name;

        $this->attempt(fn () => app(EventNeedService::class)->release($claim), 'Released '.$name);
    }

    public function confirm(string $needId): void
    {
        $claim = $this->myClaim($needId);

        $this->attempt(fn () => app(EventNeedService::class)->confirm($claim), 'Thanks, you are still on for '.$claim->need->name);
    }

    public function saveNeed(?string $needId, string $name, string $note, int $quantity): void
    {
        $this->actor();
        $this->authorizeFor('manageNeeds');
        $service = app(EventNeedService::class);

        if ($needId === null) {
            $this->attempt(fn () => $service->addNeed($this->activity, Auth::user(), $name, $note, $quantity), 'Item added');

            return;
        }

        $need = $this->need($needId);
        $this->attempt(fn () => $service->updateNeed($need, $name, $note, $quantity), 'Saved');
    }

    public function removeNeed(string $needId): void
    {
        $this->actor();
        $this->authorizeFor('manageNeeds');
        $need = $this->need($needId);

        $this->attempt(fn () => app(EventNeedService::class)->removeNeed($need), 'Item removed');
    }

    public function assign(string $needId, string $userId): void
    {
        $this->actor();
        $this->authorizeFor('manageNeeds');
        $need = $this->need($needId);
        $target = User::query()->findOrFail($userId);

        $this->attempt(fn () => app(EventNeedService::class)->assign($need, $target), 'Assigned to '.($target->display_name ?? $target->username));
    }

    public function toggleBrought(string $needId): void
    {
        $this->actor();
        $this->authorizeFor('manageNeeds');
        $need = $this->need($needId)->load('claims');
        $brought = $need->status() !== 'brought';

        $this->attempt(fn () => app(EventNeedService::class)->markBrought($need, $brought), $brought ? 'Marked as brought' : 'Marked as not brought');
    }

    public function copyFrom(string $sourceId): void
    {
        $this->actor();
        $this->authorizeFor('manageNeeds');
        $source = Activity::query()->findOrFail($sourceId);
        Gate::authorize('manageNeeds', $source);

        $count = 0;
        $this->attempt(function () use ($source, &$count) {
            $count = app(EventNeedService::class)->copyFrom($this->activity, $source, Auth::user());
        }, 'Copied items from '.$source->title);
    }

    protected function actor(): User
    {
        $user = Auth::user();
        abort_if($user === null, 401);

        return $user;
    }

    protected function authorizeFor(string $ability): void
    {
        Gate::authorize($ability, $this->activity);
    }

    protected function need(string $needId): EventNeed
    {
        return EventNeed::query()->where('activity_id', $this->activity->id)->findOrFail($needId);
    }

    protected function myClaim(string $needId): EventNeedClaim
    {
        $user = $this->actor();

        return EventNeedClaim::query()
            ->with('need.activity')
            ->where('user_id', $user->id)
            ->whereHas('need', fn ($q) => $q->where('activity_id', $this->activity->id)->where('id', $needId))
            ->firstOrFail();
    }

    protected function attempt(callable $action, string $success): void
    {
        try {
            $action();
            $this->dispatch('show-toast', message: $success, type: 'success');
        } catch (\DomainException $e) {
            $this->dispatch('show-toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function render(): View
    {
        $user = Auth::user();
        $needs = EventNeed::query()->where('activity_id', $this->activity->id)->with('claims.user')->orderBy('sort_order')->get();
        $canManage = $user !== null && Gate::forUser($user)->allows('manageNeeds', $this->activity);
        $canClaim = $user !== null && Gate::forUser($user)->allows('claimNeeds', $this->activity);
        $service = app(EventNeedService::class);

        $going = collect();
        $sources = collect();

        if ($canManage) {
            $going = User::query()
                ->whereIn('id', Rsvp::query()->where('activity_id', $this->activity->id)->where('status', 'attending')->select('user_id'))
                ->orderBy('display_name')->limit(50)->get(['id', 'username', 'display_name']);

            $sources = Activity::query()
                ->where('id', '!=', $this->activity->id)
                ->where(fn ($q) => $q->where('host_id', $user->id)->when($this->activity->group_id, fn ($qq) => $qq->orWhere('group_id', $this->activity->group_id)))
                ->whereHas('needs')
                ->latest('start_time')->limit(10)->get(['id', 'title', 'start_time', 'host_id', 'group_id'])
                ->filter(fn (Activity $a) => Gate::forUser($user)->allows('manageNeeds', $a))->values();
        }

        $total = (int) $needs->sum('quantity');
        $covered = (int) $needs->sum(fn (EventNeed $n) => min($n->claimedQuantity(), $n->quantity));

        return view('livewire.events.event-needs', [
            'needs' => $needs,
            'canManage' => $canManage,
            'canClaim' => $canClaim,
            'isOpen' => $service->isOpen($this->activity),
            'viewerId' => $user?->id,
            'going' => $going,
            'sources' => $sources,
            'total' => $total,
            'covered' => $covered,
        ]);
    }
}
