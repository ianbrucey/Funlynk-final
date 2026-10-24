<?php

namespace App\Livewire\Groups;

use App\Jobs\NotifyGroupAskEvent;
use App\Models\Group;
use App\Models\GroupAsk;
use App\Models\GroupAskEntry;
use App\Services\GroupAskService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The Funds tab on a group page: asks list, ask detail, ledger and admin actions (spec 015).
 * All rules live in GroupAskService; this component only gathers input and shows results.
 */
class GroupFunds extends Component
{
    use WithFileUploads;

    public Group $group;

    public ?string $openAskId = null;

    public string $sheet = '';

    public ?string $sheetTargetId = null;

    public string $title = '';

    public string $why = '';

    public string $target = '';

    public string $deadline = '';

    public string $paymentUrl = '';

    public string $amount = '';

    public string $counterparty = '';

    public string $description = '';

    public string $note = '';

    public string $reason = '';

    public string $outcome = 'completed';

    public mixed $itemPhoto = null;

    public mixed $receiptPhoto = null;

    public function mount(Group $group): void
    {
        Gate::authorize('viewFunds', $group);
        $this->group = $group;
    }

    public function openAsk(string $id): void
    {
        $this->openAskId = $this->findAsk($id)->id;
        $this->closeSheet();
    }

    public function back(): void
    {
        $this->openAskId = null;
        $this->closeSheet();
    }

    public function openSheet(string $sheet, ?string $targetId = null): void
    {
        $this->resetErrorBag();
        $this->sheet = $sheet;
        $this->sheetTargetId = $targetId;
    }

    public function closeSheet(): void
    {
        $this->resetErrorBag();
        $this->reset(['sheet', 'sheetTargetId', 'title', 'why', 'target', 'deadline', 'paymentUrl', 'amount', 'counterparty', 'description', 'note', 'reason', 'itemPhoto', 'receiptPhoto']);
        $this->outcome = 'completed';
    }

    public function propose(GroupAskService $service): void
    {
        $this->run(function () use ($service) {
            $ask = $service->propose(auth()->user(), $this->group, [
                'title' => $this->title, 'why' => $this->why, 'target_cents' => $this->toCents($this->target),
                'deadline' => $this->deadline ?: null, 'payment_url' => $this->paymentUrl,
            ]);
            NotifyGroupAskEvent::dispatch($ask->id, NotifyGroupAskEvent::PROPOSED, (string) auth()->id());
        }, 'Sent for approval.');
    }

    public function approve(GroupAskService $service, string $id): void
    {
        $this->run(function () use ($service, $id) {
            $ask = $service->approve(auth()->user(), $this->findAsk($id));
            NotifyGroupAskEvent::dispatch($ask->id, NotifyGroupAskEvent::OPENED, (string) auth()->id());
        }, 'Ask is open.');
    }

    public function decline(GroupAskService $service): void
    {
        $this->run(function () use ($service) {
            $ask = $service->decline(auth()->user(), $this->findAsk((string) $this->sheetTargetId), $this->reason);
            NotifyGroupAskEvent::dispatch($ask->id, NotifyGroupAskEvent::DECLINED, (string) auth()->id());
        }, 'Declined.');
    }

    public function recordReceived(GroupAskService $service): void
    {
        $this->run(fn () => $service->recordReceived(auth()->user(), $this->findAsk((string) $this->openAskId), $this->toCents($this->amount), $this->counterparty, $this->note), 'Money recorded.');
    }

    public function recordPurchase(GroupAskService $service): void
    {
        $this->validate(['itemPhoto' => 'nullable|image|max:6144', 'receiptPhoto' => 'nullable|image|max:6144'], [
            'itemPhoto.max' => 'Photos must be under 6 MB.', 'receiptPhoto.max' => 'Photos must be under 6 MB.',
            'itemPhoto.image' => 'Upload an image.', 'receiptPhoto.image' => 'Upload an image.',
        ]);

        $this->run(function () use ($service) {
            $entry = $service->recordPurchase(auth()->user(), $this->findAsk((string) $this->openAskId), $this->toCents($this->amount), $this->description, $this->itemPhoto, $this->receiptPhoto, $this->note);
            NotifyGroupAskEvent::dispatch((string) $this->openAskId, NotifyGroupAskEvent::PURCHASE, (string) auth()->id(), $entry->id);
        }, 'Purchase recorded.');
    }

    public function voidEntry(GroupAskService $service): void
    {
        $this->run(function () use ($service) {
            $entry = GroupAskEntry::query()->with('ask.group')->findOrFail($this->sheetTargetId);
            abort_unless($entry->ask->group_id === $this->group->id, 404);
            $service->voidEntry(auth()->user(), $entry, $this->reason);
        }, 'Entry voided.');
    }

    public function closeAsk(GroupAskService $service): void
    {
        $this->run(fn () => $service->close(auth()->user(), $this->findAsk((string) $this->openAskId), $this->outcome, $this->note), 'Ask closed.');
    }

    public function chipIn(GroupAskService $service, string $id): void
    {
        $this->run(fn () => $service->chipIn(auth()->user(), $this->findAsk($id)), 'Thanks. Marked as self-reported.', false);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $user = auth()->user();
        $isAdmin = $user ? Gate::forUser($user)->allows('manageFunds', $this->group) : false;
        $isMember = $user ? Gate::forUser($user)->allows('proposeAsk', $this->group) : false;

        $asks = GroupAsk::query()
            ->where('group_id', $this->group->id)
            ->where(function ($q) use ($isAdmin, $user) {
                $q->where('status', '!=', GroupAsk::STATUS_PROPOSED)
                    ->when($isAdmin, fn ($q) => $q->orWhere('status', GroupAsk::STATUS_PROPOSED))
                    ->when(! $isAdmin && $user, fn ($q) => $q->orWhere(fn ($q) => $q->where('created_by', $user->id)));
            })
            ->with('author:id,display_name,username')
            ->withSum(['entries as received_cents' => fn ($q) => $q->where('kind', 'in')->whereNull('voided_at')], 'amount_cents')
            ->withSum(['entries as spent_cents' => fn ($q) => $q->where('kind', 'out')->whereNull('voided_at')], 'amount_cents')
            ->withCount('chips')
            ->latest()
            ->get();

        $open = $this->openAskId ? $asks->firstWhere('id', $this->openAskId) : null;

        return view('livewire.groups.group-funds', [
            'asks' => $asks,
            'open' => $open,
            'entries' => $open ? $open->entries()->with('recorder:id,display_name,username')->oldest()->get() : collect(),
            'iChipped' => $open && $user ? $open->chips()->where('user_id', $user->id)->exists() : false,
            'isAdmin' => $isAdmin,
            'isMember' => $isMember,
        ]);
    }

    private function findAsk(string $id): GroupAsk
    {
        $ask = GroupAsk::query()->where('group_id', $this->group->id)->findOrFail($id);
        $user = auth()->user();

        if ($ask->isProposed() && ! ($user && ($ask->created_by === $user->id || Gate::forUser($user)->allows('manageFunds', $this->group)))) {
            abort(404);
        }

        return $ask;
    }

    private function toCents(string $value): int
    {
        $clean = str_replace([',', '$', ' '], '', $value);

        return is_numeric($clean) ? (int) round(((float) $clean) * 100) : 0;
    }

    private function run(callable $action, string $success, bool $closeSheet = true): void
    {
        try {
            $action();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        } catch (AuthorizationException $e) {
            $this->addError('ask', $e->getMessage() ?: 'You can\'t do that.');

            return;
        }

        if ($closeSheet) {
            $this->closeSheet();
        }

        $this->dispatch('notify', ['type' => 'success', 'message' => $success]);
    }
}
