@php
    $viewer = auth()->user();
    $uid = $this->getId();
    $subject = $conversation?->conversationable;
@endphp
<div class="flex h-full min-h-0 flex-col overflow-hidden rounded-2xl border border-white/10 bg-slate-900/50 backdrop-blur-lg"
     x-data="{
        draft: '', pending: [], sheet: null, sending: false, notifySheet: false, searchOpen: false, ids: [], people: @js($mentionable), pick: { open: false, hits: [], hi: 0 },
        thread() { return this.$refs.thread; },
        toEnd() { this.$nextTick(() => { const t = this.thread(); if (t) t.scrollTop = t.scrollHeight; }); },
        jump(id, retried = false) { const el = document.getElementById('msg-{{ $uid }}-' + id); if (!el) { if (! retried) $wire.jumpTo(id).then(ok => { if (ok) this.$nextTick(() => setTimeout(() => this.jump(id, true), 60)); }); return; } el.scrollIntoView({ block: 'center', behavior: 'smooth' }); el.classList.add('bg-cyan-400/20'); setTimeout(() => el.classList.remove('bg-cyan-400/20'), 1200); },
        async openHit(id) { const ok = await $wire.jumpTo(id); if (! ok) return; this.searchOpen = false; this.$nextTick(() => setTimeout(() => this.jump(id), 60)); },
        closeSearch() { this.searchOpen = false; $wire.clearSearch(); },
        grow(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 110) + 'px'; },
        scan(el) {
            const m = el.value.slice(0, el.selectionStart).match(/(^|\s)@([\w.]*)$/);
            if (!m) { this.pick.open = false; return; }
            const q = m[2].toLowerCase();
            this.pick.hits = this.people.filter(p => p.name.toLowerCase().includes(q) || p.handle.toLowerCase().startsWith(q)).slice(0, 5);
            this.pick.hi = 0; this.pick.open = this.pick.hits.length > 0;
        },
        choose(p) {
            const el = this.$refs.box, pos = el.selectionStart, before = el.value.slice(0, pos).replace(/@([\w.]*)$/, '@' + p.handle + ' ');
            this.draft = before + el.value.slice(pos); if (!this.ids.includes(p.id)) this.ids.push(p.id); this.pick.open = false;
            this.$nextTick(() => { el.focus(); el.setSelectionRange(before.length, before.length); this.grow(el); });
        },
        async send() {
            const text = this.draft.trim(); if (!text || this.sending) return;
            this.sending = true; this.pending.push({ text, failed: false }); this.draft = ''; this.$nextTick(() => this.grow(this.$refs.box)); this.toEnd();
            $wire.newMessage = text; $wire.mentionIds = this.ids; this.ids = [];
            try { await $wire.sendMessage(); } catch (e) { $wire.newMessage = ''; this.pending.at(-1).failed = true; this.sending = false; return; }
            if ($wire.newMessage !== '') { this.draft = text; $wire.newMessage = ''; this.pending.pop(); } else { this.pending.pop(); }
            this.sending = false; this.toEnd();
        },
     }"
     x-init="toEnd(); @if($focusId) setTimeout(() => jump('{{ $focusId }}'), 80); @endif"
     x-on:message-received.window="toEnd()"
     x-on:keydown.escape.window="sheet = null; if (searchOpen) closeSearch()">

    @if($showHeader)
        <div class="flex-none border-b border-white/10 bg-gradient-to-r from-slate-800/50 to-slate-900/50 px-4 py-3">
            @if($conversation && $conversation->type === 'private')
                @php $otherUser = $conversation->participants->where('id', '!=', auth()->id())->first(); @endphp
                <div class="flex items-center gap-3">
                    <x-fl.avatar :name="$otherUser->display_name ?? $otherUser->username" :src="$otherUser->profile_image_url" class="h-10 w-10 border-2 border-white/20" />
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-semibold text-white">{{ $otherUser->display_name ?? $otherUser->username }}</h3>
                        <p class="text-xs text-gray-400">{{ '@'.$otherUser->username }}</p>
                    </div>
                </div>
            @else
                <h3 class="truncate text-base font-semibold text-white">{{ $subject?->title ?? 'Group Chat' }}</h3>
                <p class="text-xs text-gray-400">{{ $conversation ? $conversation->participants->count() : 0 }} {{ \Illuminate\Support\Str::plural('person', $conversation ? $conversation->participants->count() : 0) }} in this chat</p>
            @endif
        </div>
    @endif

    @if($conversation)
        <div class="flex flex-none items-center justify-between gap-2 border-b border-white/10 px-4 py-1">
            <span class="text-xs text-gray-400">@if($canSend){{ ['all' => 'All messages', 'mentions' => 'Mentions and replies only', 'muted' => 'Muted'][$notifyLevel] ?? '' }}@endif</span>
            <div class="flex items-center gap-2">
                <button type="button" x-on:click="searchOpen = true; $nextTick(() => $refs.search.focus())" aria-label="Search this chat"
                        class="min-h-[44px] min-w-[44px] rounded-xl border border-white/15 text-base text-white">🔍</button>
                @if($canSend)
                    <button type="button" x-on:click="notifySheet = true" aria-label="Notification settings: {{ $notifyLevel }}"
                            class="min-h-[44px] min-w-[44px] rounded-xl border text-base {{ $notifyLevel === 'muted' ? 'border-red-400/50 text-red-300' : ($notifyLevel === 'mentions' ? 'border-cyan-400 text-white' : 'border-white/15 text-white') }}">{{ $notifyLevel === 'muted' ? '🔕' : '🔔' }}</button>
                @endif
            </div>
        </div>

        <div x-show="searchOpen" x-cloak class="flex flex-none gap-2 border-b border-white/10 bg-slate-950/70 px-3 py-2">
            <input x-ref="search" type="search" name="chat-search" id="chat-search-{{ $uid }}" wire:model.live.debounce.300ms="searchQuery" maxlength="100" placeholder="Search this chat" aria-label="Search messages"
                   class="min-h-[44px] flex-1 rounded-xl border border-white/20 bg-white/5 px-3 text-sm text-white focus:border-cyan-400 focus:outline-none">
            <button type="button" x-on:click="closeSearch()" aria-label="Close search" class="min-h-[44px] min-w-[44px] rounded-xl border border-white/15 text-white">✕</button>
        </div>
    @endif

    @if($pinned)
        <button type="button" x-on:click="jump('{{ $pinned['id'] }}')" class="flex min-h-[44px] flex-none items-center gap-2 border-b border-cyan-400/25 bg-cyan-400/10 px-4 py-2 text-left text-xs text-cyan-200">
            <span aria-hidden="true">📌</span><span class="flex-1 truncate">{{ $pinned['body'] }}</span>
        </button>
    @endif

    <div x-show="searchOpen" x-cloak class="min-h-0 flex-1 overflow-y-auto px-3 py-3">
        @if(mb_strlen(trim($searchQuery)) < \App\Services\ChatService::SEARCH_MIN_LENGTH)
            <div class="px-4 py-10 text-center text-sm text-gray-400"><div class="mb-1 text-3xl" aria-hidden="true">🔍</div>Type at least 2 characters to search this chat.</div>
        @else
            <div wire:loading wire:target="searchQuery" class="space-y-2">
                @foreach(range(1, 3) as $i)<div class="h-14 animate-pulse rounded-2xl bg-white/10"></div>@endforeach
            </div>
            <div wire:loading.remove wire:target="searchQuery">
                @forelse($searchResults as $hit)
                    <button type="button" wire:key="hit-{{ $hit['id'] }}" x-on:click="openHit('{{ $hit['id'] }}')"
                            class="mb-2 block min-h-[56px] w-full rounded-2xl border border-white/10 bg-white/5 px-3 py-2.5 text-left text-sm leading-snug text-gray-100 hover:border-cyan-400/50">
                        <span class="flex justify-between text-xs text-gray-400"><span>{{ $hit['user'] }}</span><span>{{ $hit['date'] }}</span></span>
                        <span class="break-words">{{ $hit['snippet']['before'] }}<mark class="rounded bg-yellow-300/40 px-0.5 text-white">{{ $hit['snippet']['match'] }}</mark>{{ $hit['snippet']['after'] }}</span>
                    </button>
                @empty
                    <div class="px-4 py-10 text-center text-sm text-gray-400"><div class="mb-1 text-3xl" aria-hidden="true">🫥</div>No messages match "{{ $searchQuery }}".</div>
                @endforelse
                @if($searchHasMore)
                    <button type="button" wire:click="showMoreResults" class="min-h-[44px] w-full rounded-xl border border-white/15 bg-slate-800/60 text-sm text-white">Show more</button>
                @endif
            </div>
        @endif
    </div>

    <div x-ref="thread" x-show="! searchOpen" class="min-h-0 flex-1 overflow-y-auto px-3 py-3" role="log" aria-live="polite">
        @if($hasEarlier)
            <button type="button" wire:click="loadEarlier" wire:loading.attr="disabled" wire:target="loadEarlier"
                    class="mx-auto mb-3 block min-h-[44px] rounded-full border border-white/10 bg-slate-800/60 px-5 text-xs text-slate-300 hover:border-cyan-400/40">
                <span wire:loading.remove wire:target="loadEarlier">Load earlier messages</span><span wire:loading wire:target="loadEarlier">Loading…</span>
            </button>
        @endif

        @php $prev = null; @endphp
        @forelse($messages as $message)
            @php
                $at = \Illuminate\Support\Carbon::parse($message['created_at'])->setTimezone(config('app.timezone'));
                $newDay = ! $prev || $prev['at']->toDateString() !== $at->toDateString();
                $first = $newDay || $prev['user_id'] !== $message['user_id'] || $at->diffInMinutes($prev['at']) > 5 || $firstUnreadId === $message['id'];
                $name = $message['user']['display_name'];
                $bodyHtml = e($message['body']);
                foreach ($message['mentions'] ?? [] as $mention) {
                    $bodyHtml = preg_replace('/(^|\s)@'.preg_quote(e($mention['handle']), '/').'(?![\w.])/iu', '$1<span class="font-semibold '.($message['is_mine'] ? 'underline' : 'text-cyan-300').'">@'.e($mention['handle']).'</span>', $bodyHtml);
                }
                $dayLabel = $at->isToday() ? 'Today' : ($at->isYesterday() ? 'Yesterday' : $at->format('M j'));
            @endphp
            @if($newDay)
                <div class="my-3 flex items-center gap-3 text-[0.7rem] text-gray-400"><span class="h-px flex-1 bg-white/10"></span>{{ $dayLabel }}<span class="h-px flex-1 bg-white/10"></span></div>
            @endif
            @if($firstUnreadId === $message['id'])
                <div class="my-3 flex items-center gap-3 text-[0.7rem] text-fuchsia-300"><span class="h-px flex-1 bg-fuchsia-300/40"></span>New messages<span class="h-px flex-1 bg-fuchsia-300/40"></span></div>
            @endif
            <div wire:key="m-{{ $message['id'] }}" id="msg-{{ $uid }}-{{ $message['id'] }}" class="flex gap-2 rounded-lg transition-colors duration-700 {{ $first ? 'mt-3' : 'mt-0.5' }} {{ $message['is_mine'] ? 'flex-row-reverse' : '' }}">
                @unless($message['is_mine'])
                    <div class="w-8 flex-none">@if($first)<x-fl.avatar :name="$name" :src="$message['user']['profile_image_url']" class="h-8 w-8 text-sm" />@endif</div>
                @endunless
                <div class="flex max-w-[82%] flex-col {{ $message['is_mine'] ? 'items-end' : 'items-start' }}">
                    @if($first && ! $message['is_mine'])<div class="mb-0.5 px-1 text-xs text-gray-400">{{ $name }}</div>@endif
                    @if($message['reply_to'])
                        <button type="button" x-on:click="jump('{{ $message['reply_to']['id'] }}')" class="mb-1 max-w-full rounded-lg border-l-2 border-cyan-400 bg-white/5 px-2.5 py-1.5 text-left text-xs text-gray-400">
                            <span class="block font-semibold text-cyan-300">{{ $message['reply_to']['user']['display_name'] }}</span><span class="block truncate">{{ $message['reply_to']['body'] }}</span>
                        </button>
                    @endif
                    <button type="button" x-on:click="sheet = '{{ $message['id'] }}'" aria-label="Message options"
                            class="break-words rounded-2xl px-3 py-2 text-left text-sm leading-snug {{ $message['is_mine'] ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'border border-white/10 bg-slate-700/60 text-gray-100' }}">{!! $bodyHtml !!}</button>
                    @if(! empty($message['reactions']))
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach($message['reactions'] as $emoji => $info)
                                <button type="button" wire:click="reactToMessage('{{ $message['id'] }}', '{{ $emoji }}')" wire:key="rx-{{ $message['id'] }}-{{ $emoji }}"
                                        class="min-h-[28px] rounded-full border px-2 text-xs {{ $info['mine'] ? 'border-cyan-400 bg-cyan-400/15' : 'border-white/10 bg-white/5' }}">{{ $emoji }} {{ $info['count'] }}</button>
                            @endforeach
                        </div>
                    @endif
                    <div class="mt-0.5 px-1 text-[0.65rem] text-gray-500">{{ $at->format('g:i A') }}</div>
                </div>
            </div>
            @php $prev = ['user_id' => $message['user_id'], 'at' => $at]; @endphp
        @empty
            <div class="px-6 py-14 text-center"><div class="text-4xl" aria-hidden="true">💬</div><p class="mt-2 text-sm font-semibold text-white">No messages yet</p><p class="text-xs text-gray-400">Say hi and start planning.</p></div>
        @endforelse

        <template x-for="(p, i) in pending" :key="i">
            <div class="mt-1 flex flex-row-reverse"><div class="flex max-w-[82%] flex-col items-end">
                <div class="break-words rounded-2xl px-3 py-2 text-sm text-white" :class="p.failed ? 'border border-red-400/50 bg-red-400/20' : 'bg-gradient-to-r from-pink-500 to-purple-500 opacity-60'" x-text="p.text"></div>
                <div class="mt-0.5 px-1 text-[0.65rem]" :class="p.failed ? 'text-red-300' : 'text-gray-500'" x-text="p.failed ? 'Not sent' : 'Sending…'"></div>
            </div></div>
        </template>
    </div>

    @if($anchorId)
        <div class="flex flex-none justify-center border-t border-white/10 px-3 py-2" x-show="! searchOpen">
            <button type="button" x-on:click="$wire.backToLatest().then(() => toEnd())" wire:loading.attr="disabled" wire:target="backToLatest"
                    class="min-h-[44px] rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 px-5 text-sm font-semibold text-white">↓ Back to latest</button>
        </div>
    @endif

    @if($canSend)
        <div class="flex-none border-t border-white/10 bg-slate-900/60 px-2.5 pb-2.5 pt-2">
            @if($replyingTo)
                <div class="flex items-center gap-2 px-1.5 pb-1 text-xs text-gray-400">↩ Replying to <span class="font-semibold text-cyan-300">{{ $replyingTo['user']['display_name'] }}</span>
                    <span class="min-w-0 flex-1 truncate">{{ $replyingTo['body'] }}</span>
                    <button type="button" wire:click="cancelReply" aria-label="Cancel reply" class="min-h-[44px] min-w-[44px] text-gray-400 hover:text-white">✕</button></div>
            @endif
            @error('newMessage')<p class="px-1.5 pb-1 text-xs text-red-300">{{ $message }}</p>@enderror
            <div class="relative flex items-end gap-2">
                <div x-show="pick.open" x-cloak class="absolute inset-x-0 bottom-14 z-10 overflow-hidden rounded-2xl border border-white/15 bg-[#14102b]" role="listbox">
                    <template x-for="(p, i) in pick.hits" :key="p.id">
                        <button type="button" role="option" x-on:click="choose(p)" class="flex min-h-[48px] w-full items-center gap-3 px-3 text-left text-sm text-gray-100" :class="i === pick.hi ? 'bg-cyan-400/15' : ''">
                            <span class="grid h-7 w-7 place-items-center rounded-full bg-gradient-to-br from-cyan-500 to-purple-500 text-xs font-bold" x-text="p.name[0]"></span><span x-text="p.name"></span>
                        </button>
                    </template>
                </div>
                <textarea x-ref="box" x-model="draft" x-on:input="grow($el); scan($el)" x-on:keydown.arrow-down="if (pick.open) { $event.preventDefault(); pick.hi = (pick.hi + 1) % pick.hits.length; }" x-on:keydown.arrow-up="if (pick.open) { $event.preventDefault(); pick.hi = (pick.hi + pick.hits.length - 1) % pick.hits.length; }" x-on:keydown.escape="pick.open = false" x-on:keydown.enter="if (pick.open) { $event.preventDefault(); choose(pick.hits[pick.hi]); } else if (! $event.shiftKey) { $event.preventDefault(); send(); }" rows="1" maxlength="2000"
                          name="message" aria-label="Message" placeholder="Message the group"
                          class="min-h-[44px] max-h-[110px] flex-1 resize-none rounded-2xl border border-white/10 bg-black/35 px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/25"></textarea>
                <button type="button" x-on:click="send()" x-bind:disabled="! draft.trim()" aria-label="Send"
                        class="h-11 w-11 flex-none rounded-2xl bg-gradient-to-r from-pink-500 to-purple-500 text-white transition disabled:opacity-40">
                    <svg class="mx-auto h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </div>
        </div>
    @else
        <div class="flex-none border-t border-white/10 px-4 py-3 text-center text-sm text-slate-300">
            @if($conversation && $conversation->group_id === null && $subject instanceof \App\Models\Post && $subject->group_id)
                Only members of {{ $subject->group?->name ?? 'this group' }} can send messages here.
                @if($subject->group) <a href="{{ route('groups.show', $subject->group->slug) }}" wire:navigate class="mt-2 flex min-h-[44px] items-center justify-center rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 font-semibold text-white">Go to group</a> @endif
            @else
                You can read this chat but not send messages.
            @endif
        </div>
    @endif

    @if($canSend)
        <div x-show="notifySheet" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/55 md:items-center md:p-4" x-on:click.self="notifySheet = false" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-t-2xl border border-white/15 bg-[#14102b] p-4 md:rounded-2xl">
                <h3 class="text-sm font-semibold text-white">Notifications for this chat</h3>
                <p class="mb-2 mt-1 text-xs text-gray-400">{{ ($conversation?->participants->count() ?? 0) > \App\Services\ChatNotifier::LARGE_CHAT_THRESHOLD ? 'This chat is large, so it starts on Mentions and replies only.' : 'You can change this any time.' }}</p>
                @foreach(['all' => ['All messages', 'A summary at most every 15 minutes'], 'mentions' => ['Mentions and replies only', 'Plus when a plan is pinned'], 'muted' => ['Muted', 'No notifications from this chat']] as $level => [$label, $hint])
                    <button type="button" wire:key="lvl-{{ $level }}" x-on:click="$wire.setNotifyLevel('{{ $level }}'); notifySheet = false"
                            class="mt-1.5 flex min-h-[56px] w-full items-center gap-3 rounded-xl border px-3 text-left {{ $notifyLevel === $level ? 'border-cyan-400 bg-cyan-400/10' : 'border-white/10 bg-white/5' }}">
                        <span class="flex-1"><span class="block text-sm text-white">{{ $label }}</span><span class="block text-xs text-gray-400">{{ $hint }}</span></span>
                        @if($notifyLevel === $level)<span class="text-cyan-300">✓</span>@endif
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <div x-show="sheet" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/55 p-0 md:items-center md:p-4" x-on:click.self="sheet = null" role="dialog" aria-modal="true">
        <div class="w-full max-w-md rounded-t-2xl border border-white/15 bg-[#14102b] p-3.5 md:rounded-2xl">
            @if($canSend)
                <div class="mb-2 flex gap-1.5">
                    @foreach($reactionChoices as $emoji)
                        <button type="button" x-on:click="$wire.reactToMessage(sheet, '{{ $emoji }}'); sheet = null" class="min-h-[48px] flex-1 rounded-xl border border-white/10 bg-white/5 text-xl">{{ $emoji }}</button>
                    @endforeach
                </div>
                <button type="button" x-on:click="$wire.replyTo(sheet); sheet = null; $nextTick(() => $refs.box.focus())" class="flex min-h-[44px] w-full items-center rounded-xl px-3 text-left text-sm text-gray-100 hover:bg-white/5">↩ Reply</button>
            @endif
            @if($canPin)
                <button type="button" x-on:click="$wire.pinMessage(sheet === '{{ $pinnedMessageId }}' ? null : sheet); sheet = null" class="flex min-h-[44px] w-full items-center rounded-xl px-3 text-left text-sm text-gray-100 hover:bg-white/5">📌 Pin or unpin message</button>
            @endif
            <button type="button" x-on:click="sheet = null" class="mt-1 flex min-h-[44px] w-full items-center rounded-xl px-3 text-left text-sm text-gray-400 hover:bg-white/5">Close</button>
        </div>
    </div>
</div>
