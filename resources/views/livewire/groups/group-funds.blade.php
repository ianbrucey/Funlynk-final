<div class="space-y-4" wire:key="funds-root">
    <p class="text-xs text-gray-400 glass-card p-3">
        Payments happen outside FunLynk. Admins record the amounts, and FunLynk holds no money.
    </p>

    @error('ask')
        <div class="glass-card p-3 text-sm text-red-300" role="alert">{{ $message }}</div>
    @enderror

    @if (! $open)
        @if ($isMember)
            <button wire:click="openSheet('propose')" class="btn btn-primary min-h-[44px] w-full sm:w-auto">Propose an ask</button>
        @endif

        @forelse (['proposed' => 'Waiting for approval', 'open' => 'Open', 'closed' => 'Closed'] as $key => $label)
            @php
                $group_asks = $asks->filter(fn ($a) => $key === 'closed' ? in_array($a->status, ['completed', 'cancelled', 'declined']) : $a->status === $key);
            @endphp
            @if ($group_asks->isNotEmpty())
                <h3 class="text-sm font-semibold text-gray-300">{{ $label }}</h3>
                @foreach ($group_asks as $ask)
                    <button wire:key="ask-{{ $ask->id }}" wire:click="openAsk('{{ $ask->id }}')" class="glass-card p-4 w-full text-left block">
                        <div class="flex items-center justify-between gap-2">
                            <b class="text-white">{{ $ask->title }}</b>
                            <span class="text-xs px-2 py-1 rounded-full bg-white/10 text-gray-200">{{ $ask->status === 'proposed' ? 'Waiting for approval' : ucfirst($ask->status) }}</span>
                        </div>
                        <div class="text-sm text-gray-300 mt-1">
                            {{ \App\Services\GroupAskService::formatMoney((int) $ask->received_cents) }} of {{ \App\Services\GroupAskService::formatMoney($ask->target_cents) }}
                            · {{ \App\Services\GroupAskService::formatMoney((int) $ask->spent_cents) }} spent
                        </div>
                        <div class="text-xs text-gray-400">Updated {{ $ask->updated_at->diffForHumans() }}</div>
                    </button>
                @endforeach
            @endif
        @empty
        @endforelse

        @if ($asks->isEmpty())
            <div class="glass-card p-6 text-center text-gray-300">
                {{ $isMember ? 'No asks yet. Propose one and an admin will review it.' : 'No asks yet.' }}
            </div>
        @endif
    @else
        @php $money = fn ($c) => \App\Services\GroupAskService::formatMoney((int) $c); @endphp
        <button wire:click="back" class="btn btn-ghost min-h-[44px]">← Funds</button>

        <div class="glass-card p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-lg font-semibold text-white">{{ $open->title }}</h3>
                <span class="text-xs px-2 py-1 rounded-full bg-white/10 text-gray-200">{{ $open->status === 'proposed' ? 'Waiting for approval' : ucfirst($open->status) }}</span>
            </div>
            <div class="text-xs text-gray-400">Asked by {{ $open->author?->display_name ?? $open->author?->username ?? 'a member' }}@if ($open->deadline) · needed by {{ $open->deadline->format('M j') }}@endif</div>
            <p class="text-sm text-gray-200 whitespace-pre-line">{{ $open->why }}</p>

            <div class="grid grid-cols-3 gap-2 text-center">
                <div><b class="block text-white">{{ $money($open->received_cents) }}</b><span class="text-xs text-gray-400">Received</span></div>
                <div><b class="block text-white">{{ $money($open->spent_cents) }}</b><span class="text-xs text-gray-400">Spent</span></div>
                <div><b class="block text-white">{{ $money($open->received_cents - $open->spent_cents) }}</b><span class="text-xs text-gray-400">Left</span></div>
            </div>
            <div class="text-xs text-gray-400">Target {{ $money($open->target_cents) }} · Last updated {{ $open->updated_at->diffForHumans() }}</div>

            @if ($open->isOpen())
                <a href="{{ $open->payment_url }}" target="_blank" rel="noopener noreferrer nofollow" class="btn btn-primary min-h-[44px] w-full">Chip in on {{ parse_url($open->payment_url, PHP_URL_HOST) }}</a>
                @if ($isMember)
                    <button wire:click="chipIn('{{ $open->id }}')" @disabled($iChipped) class="btn btn-outline min-h-[44px] w-full">
                        {{ $iChipped ? 'You said you chipped in' : 'I chipped in' }}
                    </button>
                @endif
                <div class="text-xs text-gray-400">{{ $open->chips_count }} member(s) say they chipped in (self-reported, not in the totals).</div>
            @endif

            @if ($open->status === 'declined')
                <div class="text-sm text-red-300">Declined: {{ $open->decline_reason }}</div>
            @elseif ($open->closing_note)
                <div class="text-sm text-gray-200">Closing note: {{ $open->closing_note }}</div>
            @endif

            @if ($isAdmin && $open->isProposed())
                <div class="flex gap-2">
                    <button wire:click="approve('{{ $open->id }}')" class="btn btn-primary min-h-[44px] flex-1">Approve</button>
                    <button wire:click="openSheet('decline', '{{ $open->id }}')" class="btn btn-outline min-h-[44px] flex-1">Decline</button>
                </div>
            @endif
            @if ($isAdmin && $open->isOpen())
                <div class="flex flex-wrap gap-2">
                    <button wire:click="openSheet('received')" class="btn btn-outline min-h-[44px] flex-1">Record money</button>
                    <button wire:click="openSheet('purchase')" class="btn btn-outline min-h-[44px] flex-1">Record purchase</button>
                    <button wire:click="openSheet('close')" class="btn btn-ghost min-h-[44px] flex-1">Close ask</button>
                </div>
            @endif
        </div>

        <h3 class="text-sm font-semibold text-gray-300">Ledger</h3>
        @forelse ($entries as $entry)
            <div wire:key="entry-{{ $entry->id }}" class="glass-card p-3 text-sm {{ $entry->isVoided() ? 'opacity-60' : '' }}">
                <div class="flex justify-between gap-2 {{ $entry->isVoided() ? 'line-through' : '' }}">
                    <span class="text-white">{{ $entry->kind === 'in' ? 'Received' : 'Spent' }}: {{ $entry->description ?: ($entry->counterparty ?: 'Contribution') }}</span>
                    <b class="text-white">{{ $money($entry->amount_cents) }}</b>
                </div>
                <div class="text-xs text-gray-400">{{ $entry->created_at->format('M j') }} · recorded by {{ $entry->recorder?->display_name ?? $entry->recorder?->username ?? 'an admin' }}</div>
                @if ($entry->receipt_photo_path)
                    <div class="flex gap-2 mt-2">
                        <img src="{{ \App\Services\ImageProcessor::url($entry->item_photo_path) }}" alt="Item" class="w-20 h-20 object-cover rounded-lg" loading="lazy">
                        <img src="{{ \App\Services\ImageProcessor::url($entry->receipt_photo_path) }}" alt="Receipt" class="w-20 h-20 object-cover rounded-lg" loading="lazy">
                    </div>
                @endif
                @if ($entry->isVoided())
                    <div class="text-xs text-amber-300 mt-1">Voided: {{ $entry->void_reason }}</div>
                @elseif ($isAdmin && $open->isOpen())
                    <button wire:click="openSheet('void', '{{ $entry->id }}')" class="text-xs text-gray-400 underline mt-1 min-h-[44px]">Void</button>
                @endif
            </div>
        @empty
            <div class="glass-card p-4 text-center text-sm text-gray-300">Nothing recorded yet.</div>
        @endforelse
    @endif

    @if ($sheet !== '')
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60" wire:key="sheet-{{ $sheet }}">
            <div class="glass-card w-full sm:max-w-md p-4 space-y-3 max-h-[90vh] overflow-y-auto rounded-b-none sm:rounded-b-2xl">
                <div class="flex justify-between items-center">
                    <h3 class="text-white font-semibold">
                        {{ ['propose' => 'Propose an ask', 'decline' => 'Decline this ask', 'received' => 'Record money received', 'purchase' => 'Record a purchase', 'void' => 'Void this entry', 'close' => 'Close this ask'][$sheet] ?? '' }}
                    </h3>
                    <button wire:click="closeSheet" class="min-h-[44px] px-3 text-gray-300" aria-label="Close">✕</button>
                </div>

                @if ($sheet === 'propose')
                    <input wire:model="title" maxlength="80" placeholder="What is it for?" class="input input-bordered w-full">
                    <textarea wire:model="why" maxlength="1000" rows="3" placeholder="Why does the group need it?" class="textarea textarea-bordered w-full"></textarea>
                    <input wire:model="target" inputmode="decimal" placeholder="Target amount ($)" class="input input-bordered w-full">
                    <input wire:model="deadline" type="date" class="input input-bordered w-full">
                    <input wire:model="paymentUrl" placeholder="Payment link (Venmo, Cash App, PayPal...)" class="input input-bordered w-full">
                    @foreach (['title', 'why', 'target', 'deadline', 'payment_url'] as $f) @error($f)<p class="text-xs text-red-300">{{ $message }}</p>@enderror @endforeach
                    <button wire:click="propose" class="btn btn-primary w-full min-h-[44px]">Send for approval</button>
                @elseif ($sheet === 'decline' || $sheet === 'void')
                    <textarea wire:model="reason" maxlength="300" rows="3" placeholder="Reason (required)" class="textarea textarea-bordered w-full"></textarea>
                    @error('reason')<p class="text-xs text-red-300">{{ $message }}</p>@enderror
                    @error('entry')<p class="text-xs text-red-300">{{ $message }}</p>@enderror
                    <button wire:click="{{ $sheet === 'decline' ? 'decline' : 'voidEntry' }}" class="btn btn-primary w-full min-h-[44px]">Confirm</button>
                @elseif ($sheet === 'received')
                    <input wire:model="amount" inputmode="decimal" placeholder="Amount ($)" class="input input-bordered w-full">
                    <input wire:model="counterparty" maxlength="80" placeholder="Who gave it (optional)" class="input input-bordered w-full">
                    <input wire:model="note" maxlength="200" placeholder="Note (optional)" class="input input-bordered w-full">
                    @error('amount')<p class="text-xs text-red-300">{{ $message }}</p>@enderror
                    <button wire:click="recordReceived" class="btn btn-primary w-full min-h-[44px]">Save</button>
                @elseif ($sheet === 'purchase')
                    <input wire:model="amount" inputmode="decimal" placeholder="Amount ($)" class="input input-bordered w-full">
                    <input wire:model="description" maxlength="120" placeholder="What was bought" class="input input-bordered w-full">
                    <label class="text-xs text-gray-300">Photo of the item <input type="file" wire:model="itemPhoto" accept="image/*" class="block w-full text-sm"></label>
                    <label class="text-xs text-gray-300">Photo of the receipt <input type="file" wire:model="receiptPhoto" accept="image/*" class="block w-full text-sm"></label>
                    <p class="text-xs text-amber-300">Cover card numbers and home addresses on the receipt.</p>
                    @foreach (['amount', 'purchase', 'itemPhoto', 'receiptPhoto'] as $f) @error($f)<p class="text-xs text-red-300">{{ $message }}</p>@enderror @endforeach
                    <button wire:click="recordPurchase" wire:loading.attr="disabled" class="btn btn-primary w-full min-h-[44px]">Save</button>
                @elseif ($sheet === 'close')
                    <select wire:model="outcome" class="select select-bordered w-full">
                        <option value="completed">Completed (needs a purchase with receipt)</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <textarea wire:model="note" maxlength="500" rows="3" placeholder="What happens to any money left over?" class="textarea textarea-bordered w-full"></textarea>
                    @foreach (['note', 'outcome'] as $f) @error($f)<p class="text-xs text-red-300">{{ $message }}</p>@enderror @endforeach
                    <button wire:click="closeAsk" class="btn btn-primary w-full min-h-[44px]">Close ask</button>
                @endif
            </div>
        </div>
    @endif
</div>
