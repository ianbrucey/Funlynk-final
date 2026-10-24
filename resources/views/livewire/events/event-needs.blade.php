<div id="needs" class="scroll-mt-6"
     x-data="{
        sheet: null, qty: 1, name: '', note: '',
        show(type, data = {}) { this.sheet = { type, ...data }; this.qty = data.qty ?? 1; this.name = data.name ?? ''; this.note = data.note ?? ''; },
        step(d) { this.qty = Math.max(1, Math.min(99, this.qty + d)); },
        done() { this.sheet = null; },
     }"
     x-on:keydown.escape.window="sheet = null">
    <div class="relative glass-card p-5 lg:p-6 lg:rounded-xl">
        <div class="top-accent"></div>

        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-white">What we need</h2>
                @if($needs->isNotEmpty())<p class="mt-0.5 text-xs text-gray-400">{{ $covered }} of {{ $total }} covered</p>@endif
            </div>
            @if($canManage && $isOpen)
                <button type="button" x-on:click="show('add')" class="min-h-[44px] rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 px-4 text-sm font-semibold text-white">+ Add item</button>
            @endif
        </div>

        @if($needs->isNotEmpty())
            <div class="my-3 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full bg-gradient-to-r from-cyan-400 to-purple-500" style="width: {{ $total > 0 ? round(100 * $covered / $total) : 0 }}%"></div></div>
        @endif

        @forelse($needs as $need)
            @php
                $status = $need->status();
                $left = max(0, $need->quantity - $need->claimedQuantity());
                $mine = $viewerId ? $need->claims->firstWhere('user_id', $viewerId) : null;
                $badge = ['open' => ['Open', 'text-amber-300 border-amber-300/40 bg-amber-300/10'], 'partial' => ['Partly covered', 'text-purple-300 border-purple-400/40 bg-purple-400/10'], 'covered' => ['Covered', 'text-green-300 border-green-500/40 bg-green-500/10'], 'brought' => ['Brought', 'text-cyan-300 border-cyan-400/40 bg-cyan-400/10']][$status];
            @endphp
            <div wire:key="need-{{ $need->id }}" class="flex items-center gap-3 border-t border-white/10 py-2.5">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5 text-sm font-semibold text-white">
                        {{ $need->name }}
                        @if($need->quantity > 1)<span class="font-normal text-gray-400">· need {{ $need->quantity }}</span>@endif
                        <span class="rounded-md border px-2 py-0.5 text-[0.62rem] font-bold uppercase tracking-wide {{ $badge[1] }}">{{ $badge[0] }}</span>
                    </div>
                    @if($need->note)<p class="mt-0.5 text-xs text-gray-400">{{ $need->note }}</p>@endif
                    @if($need->claims->isNotEmpty())
                        <p class="mt-1 text-xs text-cyan-300">
                            {{ $need->claims->map(fn ($c) => ($c->user_id === $viewerId ? 'You' : ($c->user->display_name ?? $c->user->username)).($need->quantity > 1 ? ' ×'.$c->quantity : ''))->implode(', ') }}
                        </p>
                    @endif
                    @if($canManage && $isOpen)
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <button type="button" x-on:click="show('edit', @js(['id' => $need->id, 'name' => $need->name, 'note' => $need->note ?? '', 'qty' => $need->quantity]))" class="min-h-[44px] rounded-lg border border-white/10 bg-white/5 px-3 text-xs text-slate-300">Edit</button>
                            <button type="button" x-on:click="show('assign', @js(['id' => $need->id, 'name' => $need->name]))" class="min-h-[44px] rounded-lg border border-white/10 bg-white/5 px-3 text-xs text-slate-300">Assign</button>
                            @if($need->claims->isNotEmpty())
                                <button type="button" wire:click="toggleBrought('{{ $need->id }}')" class="min-h-[44px] rounded-lg border border-white/10 bg-white/5 px-3 text-xs text-slate-300">{{ $status === 'brought' ? 'Undo brought' : 'Mark brought' }}</button>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="flex flex-col items-end gap-1.5">
                    @if($mine)
                        <button type="button" x-on:click="show('release', @js(['id' => $need->id, 'name' => $need->name]))" class="min-h-[44px] whitespace-nowrap rounded-xl border border-purple-500/50 bg-purple-500/20 px-3 text-xs font-semibold text-white">✓ You{{ $need->quantity > 1 ? ' ×'.$mine->quantity : '' }} · Release</button>
                        @if($mine->reminded_at && ! $mine->confirmed_at && $isOpen)
                            <button type="button" wire:click="confirm('{{ $need->id }}')" class="min-h-[44px] whitespace-nowrap rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 px-3 text-xs font-semibold text-white">Still bringing it</button>
                        @endif
                    @elseif($canClaim && $isOpen && $left > 0)
                        <button type="button" x-on:click="show('claim', @js(['id' => $need->id, 'name' => $need->name, 'left' => $left, 'need' => $need->quantity]))" class="min-h-[44px] whitespace-nowrap rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 px-3 text-xs font-semibold text-white">I'll bring it</button>
                    @elseif(! $canClaim)
                        <span class="max-w-[110px] text-right text-xs text-gray-400">RSVP to claim</span>
                    @else
                        <span class="rounded-xl border border-white/10 px-3 py-2.5 text-xs text-gray-400">{{ $left > 0 ? 'Closed' : 'Covered' }}</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-3 py-8 text-center">
                <div class="text-3xl" aria-hidden="true">🧰</div>
                <h3 class="mt-2 text-sm font-semibold text-white">Nothing listed yet</h3>
                <p class="mt-1 text-xs text-gray-400">{{ $canManage ? 'List what the event needs so people can sign up to bring it.' : 'The host has not listed what is needed yet.' }}</p>
                @if($canManage && $isOpen)<button type="button" x-on:click="show('add')" class="mt-3 min-h-[44px] rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 px-5 text-sm font-semibold text-white">Add the first item</button>@endif
            </div>
        @endforelse
    </div>

    <div x-show="sheet" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 p-3 md:items-center" x-on:click.self="sheet = null" role="dialog" aria-modal="true">
        <div class="w-full max-w-sm rounded-2xl border border-white/15 bg-[#14102b] p-4">
            <template x-if="sheet && sheet.type === 'claim'"><div>
                <h4 class="font-semibold text-white" x-text="'Bring ' + sheet.name"></h4>
                <p class="mb-2 mt-1 text-sm text-slate-300" x-text="sheet.need > 1 ? sheet.left + ' still needed. How many can you bring?' : 'You will be listed as bringing it. We will check with you the day before.'"></p>
                <div x-show="sheet.need > 1" class="mb-2 flex items-center gap-3"><button type="button" x-on:click="step(-1)" aria-label="Fewer" class="h-11 w-11 rounded-xl border border-white/15 bg-white/5 text-lg text-white">−</button><b class="min-w-[28px] text-center text-white" x-text="qty"></b><button type="button" x-on:click="step(1)" aria-label="More" class="h-11 w-11 rounded-xl border border-white/15 bg-white/5 text-lg text-white">+</button></div>
                <div class="mt-3 flex gap-2"><button type="button" x-on:click="done()" class="min-h-[44px] flex-1 rounded-xl border border-white/15 bg-slate-800/60 text-sm font-semibold text-white">Cancel</button>
                <button type="button" x-on:click="$wire.claim(sheet.id, qty); done()" class="min-h-[44px] flex-1 rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 text-sm font-semibold text-white">Confirm</button></div>
            </div></template>
            <template x-if="sheet && sheet.type === 'release'"><div>
                <h4 class="font-semibold text-white" x-text="'Release ' + sheet.name + '?'"></h4>
                <p class="mb-3 mt-1 text-sm text-slate-300">It goes back to open so someone else can bring it.</p>
                <div class="flex gap-2"><button type="button" x-on:click="done()" class="min-h-[44px] flex-1 rounded-xl border border-white/15 bg-slate-800/60 text-sm font-semibold text-white">Keep</button>
                <button type="button" x-on:click="$wire.release(sheet.id); done()" class="min-h-[44px] flex-1 rounded-xl border border-red-400/50 text-sm font-semibold text-red-300">Release</button></div>
            </div></template>
            <template x-if="sheet && (sheet.type === 'add' || sheet.type === 'edit')"><div>
                <h4 class="font-semibold text-white" x-text="sheet.type === 'add' ? 'Add an item' : 'Edit item'"></h4>
                <label for="need-name" class="mt-2 block text-xs text-gray-400">What is needed</label>
                <input id="need-name" name="name" x-model="name" maxlength="80" placeholder="Folding tables" class="mt-1 min-h-[44px] w-full rounded-xl border border-white/10 bg-black/35 px-3 text-sm text-white placeholder-gray-500 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/25">
                <label for="need-note" class="mt-2 block text-xs text-gray-400">Note (optional)</label>
                <textarea id="need-note" name="note" x-model="note" maxlength="200" rows="2" class="mt-1 w-full rounded-xl border border-white/10 bg-black/35 px-3 py-2 text-sm text-white focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/25"></textarea>
                <p class="mt-2 text-xs text-gray-400">How many</p>
                <div class="mt-1 flex items-center gap-3"><button type="button" x-on:click="step(-1)" aria-label="Fewer" class="h-11 w-11 rounded-xl border border-white/15 bg-white/5 text-lg text-white">−</button><b class="min-w-[28px] text-center text-white" x-text="qty"></b><button type="button" x-on:click="step(1)" aria-label="More" class="h-11 w-11 rounded-xl border border-white/15 bg-white/5 text-lg text-white">+</button></div>
                <div class="mt-3 flex gap-2"><button type="button" x-on:click="done()" class="min-h-[44px] flex-1 rounded-xl border border-white/15 bg-slate-800/60 text-sm font-semibold text-white">Cancel</button>
                <button type="button" x-on:click="$wire.saveNeed(sheet.id ?? null, name, note, qty); done()" class="min-h-[44px] flex-1 rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 text-sm font-semibold text-white">Save</button></div>
                <button type="button" x-show="sheet.type === 'edit'" x-on:click="$wire.removeNeed(sheet.id); done()" class="mt-2 min-h-[44px] w-full rounded-xl border border-red-400/50 text-sm font-semibold text-red-300">Remove item</button>
                @if($sources->isNotEmpty())
                    <div x-show="sheet.type === 'add'" class="mt-3 border-t border-white/10 pt-3"><p class="text-xs text-gray-400">Or copy the list from a previous event</p>
                        @foreach($sources as $source)
                            <button type="button" wire:key="src-{{ $source->id }}" x-on:click="$wire.copyFrom('{{ $source->id }}'); done()" class="mt-1.5 flex min-h-[44px] w-full items-center rounded-xl border border-white/10 bg-white/5 px-3 text-left text-sm text-slate-200">{{ $source->title }} <span class="ml-auto text-xs text-gray-400">{{ $source->start_time?->format('M j') }}</span></button>
                        @endforeach
                    </div>
                @endif
            </div></template>
            <template x-if="sheet && sheet.type === 'assign'"><div>
                <h4 class="font-semibold text-white" x-text="'Assign ' + sheet.name"></h4>
                <p class="mb-2 mt-1 text-sm text-slate-300">Pick someone who is going.</p>
                @forelse($going as $person)
                    <button type="button" wire:key="go-{{ $person->id }}" x-on:click="$wire.assign(sheet.id, '{{ $person->id }}'); done()" class="mt-1.5 flex min-h-[44px] w-full items-center rounded-xl border border-white/10 bg-white/5 px-3 text-left text-sm text-slate-200">{{ $person->display_name ?? $person->username }}</button>
                @empty
                    <p class="text-sm text-gray-400">Nobody has RSVP'd yet.</p>
                @endforelse
                <button type="button" x-on:click="done()" class="mt-3 min-h-[44px] w-full rounded-xl border border-white/15 bg-slate-800/60 text-sm font-semibold text-white">Close</button>
            </div></template>
        </div>
    </div>
</div>
